<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryItemLocation;
use App\Models\Location;
use App\Models\NumberSequence;
use App\Models\StockCount;
use App\Services\StockLedgerService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockCountController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('consumables.view'), 403);

        $counts = StockCount::with(['location', 'counter', 'approver'])
            ->latest('created_at')->paginate(20);
        $locations = Location::orderBy('name')->get();

        return view('stock-counts.index', compact('counts', 'locations'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('consumables.manage'), 403);
        $data = $request->validate([
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'counted_on' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $count = DB::transaction(function () use ($data, $request) {
            $key = 'stock-count:' . now()->format('Y');
            DB::table('number_sequences')->insertOrIgnore([
                'key' => $key, 'current_value' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $sequence = DB::table('number_sequences')->where('key', $key)->lockForUpdate()->first();
            $next = ((int) $sequence->current_value) + 1;
            DB::table('number_sequences')->where('id', $sequence->id)->update(['current_value' => $next, 'updated_at' => now()]);

            $count = StockCount::create([
                'count_no' => sprintf('SC-%s-%05d', now()->format('Y'), $next),
                'location_id' => $data['location_id'] ?? null,
                'counted_on' => $data['counted_on'],
                'counted_by' => $request->user()->id,
                'status' => 'in_progress',
                'remarks' => $data['remarks'] ?? null,
            ]);

            $query = InventoryItemLocation::query()->with('item')->whereHas('item')->orderBy('inventory_item_id')->orderBy('id');
            if (! empty($data['location_id'])) {
                $query->where('location_id', $data['location_id']);
            }
            $query->chunkById(400, function ($balances) use ($count) {
                $now = now();
                $rows = $balances->map(fn (InventoryItemLocation $balance) => [
                    'stock_count_id' => $count->id,
                    'office_id' => $count->office_id,
                    'stock_item_id' => $balance->inventory_item_id,
                    'location_id' => $balance->location_id,
                    'system_qty' => (int) $balance->quantity_on_hand,
                    'counted_qty' => null,
                    'variance' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();
                if ($rows) DB::table('stock_count_items')->insert($rows);
            });

            return $count;
        }, 3);

        Audit::record('stock_count_created', 'stock_counts', StockCount::class, $count->id);

        return redirect()->route('stock-counts.show', $count)->with('success', 'Physical stock count created. Enter the observed quantities for each listed item.');
    }

    public function show(Request $request, StockCount $stockCount)
    {
        abort_unless($request->user()->hasPermission('consumables.view'), 403);
        $stockCount->load(['location', 'counter', 'approver', 'items.item.location', 'items.location']);

        return view('stock-counts.show', compact('stockCount'));
    }

    public function updateItems(Request $request, StockCount $stockCount)
    {
        abort_unless($request->user()->hasPermission('consumables.manage'), 403);
        abort_unless(in_array($stockCount->status, ['draft', 'in_progress'], true), 422, 'This stock count is no longer editable.');

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.counted_qty' => ['nullable', 'integer', 'min:0'],
            'items.*.remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($stockCount, $data) {
            $locked = StockCount::whereKey($stockCount->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, ['draft', 'in_progress'], true), 422, 'This stock count is no longer editable.');

            foreach ($data['items'] as $itemId => $line) {
                $countLine = $locked->items()->whereKey($itemId)->lockForUpdate()->firstOrFail();
                $counted = $line['counted_qty'] ?? null;
                $countLine->update([
                    'counted_qty' => $counted,
                    'variance' => $counted === null ? null : ((int) $counted - (int) $countLine->system_qty),
                    'remarks' => $line['remarks'] ?? null,
                ]);
            }
            $locked->update(['status' => 'in_progress']);
        }, 3);

        Audit::record('stock_count_items_updated', 'stock_counts', StockCount::class, $stockCount->id);

        return back()->with('success', 'Physical count quantities saved.');
    }

    public function submit(Request $request, StockCount $stockCount)
    {
        abort_unless($request->user()->hasPermission('consumables.manage'), 403);
        DB::transaction(function () use ($stockCount) {
            $locked = StockCount::whereKey($stockCount->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, ['draft', 'in_progress'], true), 422, 'Only an open stock count can be submitted.');
            $missing = $locked->items()->whereNull('counted_qty')->exists();
            if ($missing) {
                throw ValidationException::withMessages(['items' => 'Enter a physical count for every listed stock item before submitting.']);
            }
            $locked->update(['status' => 'pending_approval']);
        }, 3);

        Audit::record('stock_count_submitted', 'stock_counts', StockCount::class, $stockCount->id);

        return back()->with('success', 'Stock count submitted for reconciliation approval.');
    }

    public function approve(Request $request, StockCount $stockCount, StockLedgerService $ledger)
    {
        abort_unless($request->user()->hasPermission('approvals.approve'), 403);

        DB::transaction(function () use ($stockCount, $request, $ledger) {
            $locked = StockCount::whereKey($stockCount->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'pending_approval', 422, 'Only a submitted stock count can be reconciled.');
            abort_unless(
                (int) $locked->counted_by !== (int) $request->user()->id,
                403,
                'The person who performed the stock count cannot approve their own reconciliation.'
            );

            $lines = $locked->items()->with('item')->orderBy('id')->lockForUpdate()->get();
            if ($lines->contains(fn ($line) => $line->counted_qty === null)) {
                throw ValidationException::withMessages(['items' => 'The stock count contains unrecorded physical quantities.']);
            }

            foreach ($lines as $line) {
                if ((int) $line->counted_qty === (int) $line->system_qty) continue;
                $ledger->move(
                    (int) $line->stock_item_id,
                    'adjust',
                    (int) $line->counted_qty,
                    $request->user()->id,
                    $locked->count_no,
                    'Physical stock count reconciliation. ' . ($line->remarks ?? ''),
                    null, null, $line->location_id, null, null, null, false
                );
            }

            $locked->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
            ]);
        }, 3);

        Audit::record('stock_count_reconciled', 'stock_counts', StockCount::class, $stockCount->id);

        return redirect()->route('stock-counts.show', $stockCount)->with('success', 'Stock count approved. System balances were reconciled through the append-only stock ledger.');
    }

    public function reject(Request $request, StockCount $stockCount)
    {
        abort_unless($request->user()->hasPermission('approvals.approve'), 403);
        $data = $request->validate(['remarks' => ['required', 'string', 'max:2000']]);
        DB::transaction(function () use ($stockCount, $data, $request) {
            $locked = StockCount::whereKey($stockCount->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'pending_approval', 422, 'Only a submitted stock count can be rejected.');
            abort_unless(
                (int) $locked->counted_by !== (int) $request->user()->id,
                403,
                'The person who performed the stock count cannot reject their own reconciliation.'
            );
            $locked->update(['status' => 'rejected', 'remarks' => trim(($locked->remarks ? $locked->remarks . "\n" : '') . 'Reconciliation rejected: ' . $data['remarks'])]);
        }, 3);
        Audit::record('stock_count_rejected', 'stock_counts', StockCount::class, $stockCount->id);

        return back()->with('success', 'Stock count reconciliation rejected. No stock balances were changed.');
    }
}
