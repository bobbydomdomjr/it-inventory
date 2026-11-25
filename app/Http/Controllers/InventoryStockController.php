<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Models\InventoryItem;
use App\Models\InventoryItemLocation;
use App\Models\Location;
use App\Models\PurchaseRequest;
use App\Models\StockCountItem;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\StockTransfer;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\PurchaseRequestItem;
use App\Notifications\SystemAlert;
use App\Services\StockLedgerService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryStockController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('consumables.view'), 403);
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'in:receive,issue,return,adjust,damaged,lost,transfer,transfer_in,transfer_out'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $query = StockMovement::query()
            ->with(['item.category', 'performer', 'location', 'toLocation', 'batch'])
            ->whereHas('item');
        if (! empty($data['q'])) {
            $term = trim($data['q']);
            $query->whereHas('item', fn ($item) => $item->where('name', 'like', '%' . $term . '%')->orWhere('sku', 'like', '%' . $term . '%'));
        }
        if (! empty($data['type'])) $query->where('type', $data['type']);
        if (! empty($data['from'])) $query->whereDate('created_at', '>=', $data['from']);
        if (! empty($data['to'])) $query->whereDate('created_at', '<=', $data['to']);
        $movements = $query->latest('created_at')->paginate(50)->withQueryString();
        return view('stock-ledger.index', compact('movements', 'data'));
    }

    public function movement(Request $request, InventoryItem $item, StockLedgerService $ledger)
    {
        abort_unless($request->user()->hasPermission('consumables.manage'), 403);
        $data = $request->validate([
            'type' => ['required', 'in:receive,issue,return,adjust,damaged,lost'],
            'quantity' => ['required', 'integer', $request->input('type') === 'adjust' ? 'min:0' : 'min:1', 'max:1000000'],
            'location_id' => ['nullable', 'integer'],
            'batch_id' => ['nullable', 'integer'],
            'batch_no' => ['nullable', 'string', 'max:100'],
            'expiry_date' => ['nullable', 'date'],
            'reservation_id' => ['nullable', 'integer'],
            'reference' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! empty($data['location_id'])) Location::query()->findOrFail($data['location_id']);
        $before = (int) $item->quantity_on_hand;
        $reservedBefore = $ledger->totalReservedQuantity($item->id);
        $beforeAvailable = max(0, $before - $reservedBefore);
        $movement = $ledger->move(
            $item->id,
            $data['type'],
            (int) $data['quantity'],
            $request->user()->id,
            $data['reference'] ?? null,
            $data['remarks'] ?? null,
            $data['batch_no'] ?? null,
            $data['expiry_date'] ?? null,
            isset($data['location_id']) ? (int) $data['location_id'] : null,
            null,
            isset($data['batch_id']) ? (int) $data['batch_id'] : null,
            isset($data['reservation_id']) ? (int) $data['reservation_id'] : null,
        );

        $this->sendThresholdAlert($movement, $beforeAvailable);
        Audit::record('stock_' . $data['type'], 'consumables', InventoryItem::class, $item->id, ['quantity_on_hand' => $before], [
            'quantity' => $data['quantity'], 'resulting_balance' => $movement->quantity_on_hand,
            'location_id' => $data['location_id'] ?? $item->location_id,
            'batch_no' => $data['batch_no'] ?? null,
        ]);

        return back()->with('success', 'Stock ' . str_replace('_', ' ', $data['type']) . ' movement recorded.');
    }

    public function transfer(Request $request, InventoryItem $item, StockLedgerService $ledger)
    {
        abort_unless($request->user()->hasPermission('consumables.manage'), 403);
        $data = $request->validate([
            'from_location_id' => ['required', 'integer', 'different:to_location_id'],
            'to_location_id' => ['required', 'integer', 'different:from_location_id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);
        Location::query()->findOrFail($data['from_location_id']);
        Location::query()->findOrFail($data['to_location_id']);
        $reference = 'TRF-' . now()->format('ymd') . '-' . Str::upper(Str::random(6));

        DB::transaction(function () use ($request, $item, $data, $ledger, $reference): void {
            $locked = InventoryItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $transfer = StockTransfer::create([
                'office_id' => $locked->office_id,
                'reference_no' => $reference,
                'inventory_item_id' => $locked->id,
                'quantity' => (int) $data['quantity'],
                'from_location_id' => (int) $data['from_location_id'],
                'to_location_id' => (int) $data['to_location_id'],
                'requested_by' => $request->user()->id,
                'status' => 'completed',
                'remarks' => $data['remarks'] ?? null,
                'transferred_at' => now(),
            ]);
            $ledger->move(
                $locked->id, 'transfer', (int) $data['quantity'], $request->user()->id,
                $transfer->reference_no, $transfer->remarks, null, null,
                (int) $data['from_location_id'], (int) $data['to_location_id']
            );
        }, 3);

        Audit::record('stock_transferred', 'consumables', InventoryItem::class, $item->id, [], [
            'reference' => $reference, 'quantity' => $data['quantity'],
            'from_location_id' => $data['from_location_id'], 'to_location_id' => $data['to_location_id'],
        ]);
        return back()->with('success', 'Stock transfer ' . $reference . ' completed. Total on-hand quantity is unchanged.');
    }

    public function reserve(Request $request, InventoryItem $item, StockLedgerService $ledger)
    {
        abort_unless($request->user()->hasPermission('consumables.manage'), 403);
        $data = $request->validate([
            'location_id' => ['nullable', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'reserved_for' => ['nullable', 'string', 'max:180'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);
        $locationId = isset($data['location_id']) ? (int) $data['location_id'] : $item->location_id;
        if ($locationId) Location::query()->findOrFail($locationId);

        $beforeAvailable = max(0, (int) $item->quantity_on_hand - $ledger->totalReservedQuantity($item->id));
        $reservation = DB::transaction(function () use ($request, $item, $data, $ledger, $locationId) {
            $locked = InventoryItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $available = $ledger->availableQuantity($locked, $locationId);
            abort_if((int) $data['quantity'] > $available, 422, 'The requested reservation exceeds available stock (' . $available . ').');
            return StockReservation::create([
                'office_id' => $locked->office_id,
                'inventory_item_id' => $locked->id,
                'location_id' => $locationId,
                'quantity' => (int) $data['quantity'],
                'fulfilled_qty' => 0,
                'status' => 'active',
                'reserved_for' => $data['reserved_for'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'reserved_by' => $request->user()->id,
                'expires_at' => $data['expires_at'] ?? null,
            ]);
        }, 3);

        $this->sendThresholdAlert($item->fresh(), $beforeAvailable);
        Audit::record('stock_reserved', 'consumables', StockReservation::class, $reservation->id);
        return back()->with('success', 'Stock reserved. Available quantity has been reduced by the reserved amount.');
    }

    public function issueReservation(Request $request, StockReservation $reservation, StockLedgerService $ledger)
    {
        abort_unless($request->user()->hasPermission('consumables.manage'), 403);
        abort_unless($reservation->status === 'active', 422, 'This reservation is not active.');
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'reference' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);
        $item = $reservation->item;
        $before = (int) $item->quantity_on_hand;
        $beforeAvailable = max(0, $before - $ledger->totalReservedQuantity($item->id));
        $movement = $ledger->move($item->id, 'issue', (int) $data['quantity'], $request->user()->id,
            $data['reference'] ?? null, $data['remarks'] ?? 'Issued against stock reservation.',
            null, null, $reservation->location_id, null, null, $reservation->id);
        $this->sendThresholdAlert($movement, $beforeAvailable);
        Audit::record('stock_reservation_issued', 'consumables', StockReservation::class, $reservation->id, [], ['quantity' => $data['quantity']]);
        return back()->with('success', 'Reserved stock issued.');
    }

    public function releaseReservation(Request $request, StockReservation $reservation)
    {
        abort_unless($request->user()->hasPermission('consumables.manage'), 403);
        $item = $reservation->item;
        $beforeAvailable = max(0, (int) $item->quantity_on_hand - app(StockLedgerService::class)->totalReservedQuantity($item->id));
        DB::transaction(function () use ($reservation): void {
            $locked = StockReservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'active', 422, 'Only active reservations can be released.');
            $locked->update(['status' => 'released', 'released_at' => now()]);
        }, 3);
        $this->sendThresholdAlert($item->fresh(), $beforeAvailable);
        Audit::record('stock_reservation_released', 'consumables', StockReservation::class, $reservation->id);
        return back()->with('success', 'Reservation released. The remaining quantity is available for issue.');
    }

    public function createReplenishmentRequest(Request $request, StockLedgerService $ledger)
    {
        abort_unless($request->user()->hasPermission('consumables.manage') && $request->user()->hasPermission('procurements.manage'), 403);
        $data = $request->validate(['ids' => ['nullable', 'array', 'max:500'], 'ids.*' => ['integer', 'distinct']]);

        [$purchaseRequest, $lineCount] = DB::transaction(function () use ($request, $data, $ledger) {
            $query = InventoryItem::query()->orderBy('id')->lockForUpdate();
            if (! empty($data['ids'])) $query->whereIn('id', $data['ids']);
            $items = $query->get();
            if (! empty($data['ids']) && $items->count() !== count($data['ids'])) {
                abort(404, 'One or more selected stock items are unavailable in your office.');
            }

            $lines = [];
            foreach ($items as $item) {
                $threshold = max((int) $item->reorder_level, (int) $item->min_level);
                $available = max(0, (int) $item->quantity_on_hand - $ledger->totalReservedQuantity($item->id));
                $required = max(0, $threshold - $available);
                if ($required > 0) $lines[] = ['item' => $item, 'qty' => $required];
            }
            if (! $lines) {
                throw ValidationException::withMessages(['ids' => 'No selected stock items currently require replenishment.']);
            }

            $year = now()->format('Y');
            $key = 'purchase-request:' . $year;
            DB::table('number_sequences')->insertOrIgnore(['key' => $key, 'current_value' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $sequence = DB::table('number_sequences')->where('key', $key)->lockForUpdate()->first();
            $next = (int) $sequence->current_value + 1;
            DB::table('number_sequences')->where('id', $sequence->id)->update(['current_value' => $next, 'updated_at' => now()]);

            $purchaseRequest = PurchaseRequest::create([
                'pr_no' => sprintf('PR-%s-%05d', $year, $next),
                'requested_by' => $request->user()->id,
                'purpose' => 'Stock replenishment for inventory items at or below their minimum/reorder thresholds.',
                'status' => 'draft',
                'total_estimated' => collect($lines)->sum(fn ($line) => $line['qty'] * (float) $line['item']->unit_cost),
            ]);
            foreach ($lines as $line) {
                $purchaseRequest->items()->create([
                    'description' => $line['item']->name . ' (' . $line['item']->sku . ')',
                    'category_id' => $line['item']->category_id,
                    'qty' => $line['qty'],
                    'est_unit_cost' => $line['item']->unit_cost,
                ]);
            }
            return [$purchaseRequest, count($lines)];
        }, 3);

        Audit::record('stock_replenishment_request_created', 'purchase_requests', PurchaseRequest::class, $purchaseRequest->id, [], ['line_count' => $lineCount]);
        return redirect()->route('procurement.requests.show', $purchaseRequest)->with('success', 'Draft replenishment purchase request created. Review quantities and submit it for approval.');
    }

    public function varianceCsv(Request $request)
    {
        abort_unless($request->user()->hasPermission('consumables.view') && $request->user()->hasPermission('reports.export'), 403);
        $rows = StockCountItem::query()->with(['stockCount', 'item', 'location'])
            ->whereNotNull('variance')->where('variance', '!=', 0)->latest('updated_at')->get();
        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Count No.', 'Count Date', 'SKU', 'Item', 'Location', 'System Quantity', 'Physical Quantity', 'Variance', 'Remarks']);
            foreach ($rows as $row) {
                fputcsv($out, [$row->stockCount?->count_no, $row->stockCount?->counted_on?->format('Y-m-d'), $row->item?->sku, $row->item?->name, $row->location?->name ?? 'All locations', $row->system_qty, $row->counted_qty, $row->variance, $row->remarks]);
            }
            fclose($out);
        }, 'stock-variance-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function sendThresholdAlert(InventoryItem $item, int $beforeAvailable): void
    {
        $threshold = max((int) $item->reorder_level, (int) $item->min_level);
        $after = max(0, (int) $item->quantity_on_hand - app(StockLedgerService::class)->totalReservedQuantity($item->id));
        $title = null;
        $message = null;
        if ($after <= 0 && $beforeAvailable > 0) {
            $title = 'Out-of-stock alert';
            $message = $item->name . ' (' . $item->sku . ') is now out of stock.';
        } elseif ($after > 0 && $after <= $threshold && $beforeAvailable > $threshold) {
            $title = 'Low-stock alert';
            $message = $item->name . ' (' . $item->sku . ') has ' . $after . ' ' . $item->unit . ' remaining.';
        }
        if (! $title) return;
        try {
            User::query()->where('is_active', true)
                ->whereHas('roles.permissions', fn ($q) => $q->where('name', 'consumables.manage'))
                ->get()->each(fn (User $user) => $user->notify(new SystemAlert(
                    $title, $message, route('inventory.show', ['consumables', $item->id]), 'warning'
                )));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
