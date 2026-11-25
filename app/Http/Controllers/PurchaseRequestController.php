<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\InventoryItem;
use App\Models\NumberSequence;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use App\Models\ApprovalFlow;
use App\Models\Quotation;
use App\Models\ReceivingReport;
use App\Models\ReceivingReportItem;
use App\Models\SystemSetting;
use App\Services\AssetTagService;
use App\Services\StockLedgerService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseRequestController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('procurements.view'), 403);
        $query = PurchaseRequest::with(['requester', 'department', 'purchaseOrder']);
        if ($term = trim((string) $request->query('q'))) {
            $query->where(fn ($builder) => $builder->where('pr_no', 'like', '%'.$term.'%')->orWhere('purpose', 'like', '%'.$term.'%'));
        }
        $validStatuses = ['draft', 'submitted', 'pending_approval', 'approved', 'rejected', 'revision_requested', 'ordered', 'partially_received', 'completed', 'cancelled'];
        if ($request->filled('status')) {
            abort_unless(in_array($request->query('status'), $validStatuses, true), 422, 'Invalid purchase request status filter.');
            $query->where('status', $request->query('status'));
        }
        $requests = $query->latest()->paginate(20)->withQueryString();
        $departments = \App\Models\Department::orderBy('name')->get();
        $categories = AssetCategory::whereIn('type', ['asset', 'consumable'])->where('is_active', true)->orderBy('type')->orderBy('name')->get();

        return view('procurement-requests.index', compact('requests', 'departments', 'categories'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('procurements.manage'), 403);
        $data = $request->validate([
            'purpose' => ['required', 'string', 'max:5000'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.category_id' => ['nullable', 'integer', 'exists:asset_categories,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.est_unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $purchaseRequest = DB::transaction(function () use ($data, $request) {
            $sequence = $this->nextSequence('purchase-request:' . now()->format('Y'));
            $purchaseRequest = PurchaseRequest::create([
                'pr_no' => sprintf('PR-%s-%05d', now()->format('Y'), $sequence),
                'requested_by' => $request->user()->id,
                'department_id' => $data['department_id'] ?? null,
                'purpose' => $data['purpose'],
                'status' => 'draft',
                'total_estimated' => collect($data['items'])->sum(fn ($item) => (int) $item['qty'] * (float) $item['est_unit_cost']),
            ]);

            foreach ($data['items'] as $item) {
                if (! empty($item['category_id'])) {
                    AssetCategory::query()
                        ->whereIn('type', ['asset', 'consumable'])
                        ->where('is_active', true)
                        ->findOrFail($item['category_id']);
                }
                $purchaseRequest->items()->create($item);
            }

            return $purchaseRequest;
        }, 3);

        Audit::record('purchase_request_created', 'purchase_requests', PurchaseRequest::class, $purchaseRequest->id);

        return redirect()->route('procurement.requests.show', $purchaseRequest)->with('success', 'Purchase request created.');
    }

    public function show(Request $request, PurchaseRequest $purchaseRequest)
    {
        abort_unless($request->user()->hasPermission('procurements.view'), 403);
        $purchaseRequest->load([
            'requester', 'department', 'items.category', 'quotations.supplier',
            'purchaseOrder.supplier', 'purchaseOrder.receivingReports.inspector',
            'purchaseOrder.receivingReports.items.purchaseRequestItem', 'approvals.approver',
        ]);
        $suppliers = \App\Models\Supplier::orderBy('company_name')->get();
        $approvers = User::where('is_active', true)->orderBy('name')->get()->filter(fn (User $user) => $user->hasPermission('approvals.approve'))->values();
        $hasActiveFlow = ApprovalFlow::where('subject_type', PurchaseRequest::class)->where('is_active', true)->exists();
        $automaticAssetNumbering = (bool) data_get(SystemSetting::where('key', 'automatic_asset_numbering')->first()?->value, 'value', true);

        return view('procurement-requests.show', compact('purchaseRequest', 'suppliers', 'approvers', 'hasActiveFlow', 'automaticAssetNumbering'));
    }

    public function submit(Request $request, PurchaseRequest $purchaseRequest)
    {
        abort_unless($request->user()->hasPermission('procurements.manage'), 403);
        abort_unless(in_array($purchaseRequest->status, ['draft', 'revision_requested'], true), 422, 'This purchase request cannot be submitted in its current status.');
        abort_unless($purchaseRequest->items()->exists(), 422, 'Add at least one purchase request item.');
        $purchaseRequest->update(['status' => 'submitted']);
        Audit::record('purchase_request_submitted', 'purchase_requests', PurchaseRequest::class, $purchaseRequest->id);

        return back()->with('success', 'Purchase request submitted. Start the configured approval workflow to route it for review.');
    }

    public function storeQuotation(Request $request, PurchaseRequest $purchaseRequest)
    {
        abort_unless($request->user()->hasPermission('procurements.manage'), 403);
        abort_unless(in_array($purchaseRequest->status, ['submitted', 'pending_approval', 'approved'], true), 422, 'Quotations can only be added after the request is submitted.');
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'total' => ['required', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,csv'],
        ]);

        $path = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->storeAs('procurement/quotations/' . $purchaseRequest->id, Str::uuid() . '.' . $file->extension(), 'local');
        }

        $quotation = $purchaseRequest->quotations()->create([
            'supplier_id' => $data['supplier_id'],
            'total' => $data['total'],
            'remarks' => $data['remarks'] ?? null,
            'file_path' => $path,
            'is_selected' => false,
        ]);
        Audit::record('quotation_recorded', 'purchase_requests', Quotation::class, $quotation->id);

        return back()->with('success', 'Supplier quotation recorded.');
    }


    public function downloadQuotation(Request $request, PurchaseRequest $purchaseRequest, Quotation $quotation)
    {
        abort_unless($request->user()->hasPermission('procurements.view'), 403);
        abort_unless((int) $quotation->purchase_request_id === (int) $purchaseRequest->id, 404);
        abort_unless($quotation->file_path && Storage::disk('local')->exists($quotation->file_path), 404);

        return Storage::disk('local')->download($quotation->file_path, basename($quotation->file_path));
    }

    public function selectQuotation(Request $request, PurchaseRequest $purchaseRequest, Quotation $quotation)
    {
        abort_unless($request->user()->hasPermission('procurements.manage'), 403);
        abort_unless((int) $quotation->purchase_request_id === (int) $purchaseRequest->id, 404);
        abort_unless(in_array($purchaseRequest->status, ['submitted', 'approved'], true), 422, 'A quotation cannot be selected at this stage.');

        DB::transaction(function () use ($purchaseRequest, $quotation) {
            PurchaseRequest::whereKey($purchaseRequest->id)->lockForUpdate()->firstOrFail();
            $purchaseRequest->quotations()->update(['is_selected' => false]);
            $quotation->update(['is_selected' => true]);
        }, 3);
        Audit::record('quotation_selected', 'purchase_requests', Quotation::class, $quotation->id);

        return back()->with('success', 'Quotation selected for this purchase request.');
    }

    public function createPurchaseOrder(Request $request, PurchaseRequest $purchaseRequest)
    {
        abort_unless($request->user()->hasPermission('procurements.manage'), 403);
        $order = DB::transaction(function () use ($purchaseRequest) {
            $locked = PurchaseRequest::whereKey($purchaseRequest->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'approved', 422, 'The purchase request must complete approval before an order can be created.');
            abort_if($locked->purchaseOrder()->exists(), 422, 'A purchase order already exists for this request.');
            $selected = $locked->quotations()->where('is_selected', true)->first();
            abort_unless($selected, 422, 'Select a supplier quotation before creating the purchase order.');

            $sequence = $this->nextSequence('purchase-order:' . now()->format('Y'));
            $order = PurchaseOrder::create([
                'po_no' => sprintf('PO-%s-%05d', now()->format('Y'), $sequence),
                'purchase_request_id' => $locked->id,
                'supplier_id' => $selected->supplier_id,
                'ordered_at' => today(),
                'total' => $selected->total,
                'status' => 'ordered',
            ]);
            $locked->update(['status' => 'ordered']);

            return $order;
        }, 3);
        Audit::record('purchase_order_created', 'purchase_orders', PurchaseOrder::class, $order->id);

        return redirect()->route('procurement.requests.show', $purchaseRequest)->with('success', 'Purchase order generated: ' . $order->po_no . '.');
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_unless($request->user()->hasPermission('procurements.manage'), 403);
        $purchaseOrder->load('purchaseRequest.items');
        abort_unless(in_array($purchaseOrder->status, ['ordered', 'partially_received'], true), 422, 'This purchase order is not open for receiving.');
        $data = $request->validate([
            'received_at' => ['required', 'date'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'inspection_remarks' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.qty_received' => ['required', 'integer', 'min:0'],
            'items.*.registered_as' => ['required', 'in:asset,stock'],
            'items.*.batch_no' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['nullable', 'date'],
        ]);

        if (! collect($data['items'])->contains(fn ($line) => (int) $line['qty_received'] > 0)) {
            throw ValidationException::withMessages(['items' => 'Enter at least one quantity greater than zero.']);
        }

        $report = DB::transaction(function () use ($purchaseOrder, $data, $request) {
            $lockedOrder = PurchaseOrder::with('purchaseRequest.items')->whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();
            $sequence = $this->nextSequence('receiving-report:' . now()->format('Y'));
            $report = ReceivingReport::create([
                'rr_no' => sprintf('RR-%s-%05d', now()->format('Y'), $sequence),
                'purchase_order_id' => $lockedOrder->id,
                'received_at' => $data['received_at'],
                'location_id' => $data['location_id'] ?? null,
                'inspected_by' => null,
                'status' => 'pending_inspection',
                'inspection_remarks' => $data['inspection_remarks'] ?? null,
            ]);

            foreach ($data['items'] as $itemId => $line) {
                $requestItem = $lockedOrder->purchaseRequest->items()->whereKey($itemId)->firstOrFail();
                $receivedQuantity = (int) $line['qty_received'];
                if ($receivedQuantity === 0) {
                    continue;
                }
                $previouslyReserved = ReceivingReportItem::where('purchase_request_item_id', $requestItem->id)
                    ->whereHas('receivingReport', fn ($query) => $query->whereIn('status', ['accepted', 'pending_inspection']))
                    ->sum('qty_received');
                $outstanding = max(0, (int) $requestItem->qty - (int) $previouslyReserved);
                if ($receivedQuantity > $outstanding) {
                    throw ValidationException::withMessages(['items.' . $itemId . '.qty_received' => 'The received quantity exceeds the outstanding quantity of ' . $outstanding . '.']);
                }
                $category = $requestItem->category_id
                    ? AssetCategory::query()->where('is_active', true)->find($requestItem->category_id)
                    : null;
                if ($line['registered_as'] === 'asset' && (! $category || $category->type !== 'asset')) {
                    throw ValidationException::withMessages(['items.' . $itemId . '.registered_as' => 'Select an active asset category on the purchase request line before receiving this item as an asset.']);
                }
                if ($line['registered_as'] === 'stock' && $category && $category->type !== 'consumable') {
                    throw ValidationException::withMessages(['items.' . $itemId . '.registered_as' => 'Choose a consumable category, or remove the category, before receiving this item as stock.']);
                }
                if ($line['registered_as'] === 'stock' && ! empty($line['expiry_date']) && empty($line['batch_no'])) {
                    throw ValidationException::withMessages(['items.' . $itemId . '.batch_no' => 'Enter a batch or lot number when recording an expiration date.']);
                }
                $report->items()->create([
                    'purchase_request_item_id' => $requestItem->id,
                    'description' => $requestItem->description,
                    'qty_received' => $receivedQuantity,
                    'registered_as' => $line['registered_as'],
                    'batch_no' => $line['registered_as'] === 'stock' ? ($line['batch_no'] ?? null) : null,
                    'expiry_date' => $line['registered_as'] === 'stock' ? ($line['expiry_date'] ?? null) : null,
                ]);
            }

            return $report;
        }, 3);

        Audit::record('receiving_report_created', 'receiving_reports', ReceivingReport::class, $report->id);

        return redirect()->route('procurement.requests.show', $purchaseOrder->purchase_request_id)->with('success', 'Receiving report recorded. Complete inspection to register accepted assets or stock.');
    }

    public function inspect(Request $request, ReceivingReport $receivingReport, StockLedgerService $ledger, AssetTagService $assetTags)
    {
        abort_unless($request->user()->hasPermission('procurements.manage'), 403);
        $data = $request->validate([
            'inspection_result' => ['required', 'in:accepted,rejected'],
            'inspection_remarks' => ['nullable', 'string', 'max:2000'],
        ]);
        $automaticNumbering = (bool) data_get(SystemSetting::where('key', 'automatic_asset_numbering')->first()?->value, 'value', true);
        $manualTags = [];
        $assetCount = (int) $receivingReport->items()->where('registered_as', 'asset')->sum('qty_received');
        if ($data['inspection_result'] === 'accepted' && ! $automaticNumbering && $assetCount > 0) {
            $tagInput = $request->validate(['asset_tags' => ['required', 'string', 'max:200000']])['asset_tags'];
            $manualTags = collect(preg_split('/\r\n|\r|\n/', $tagInput))
                ->map(fn ($tag) => trim($tag))
                ->filter()
                ->values()
                ->all();
            if (count($manualTags) !== $assetCount) {
                throw ValidationException::withMessages(['asset_tags' => "Enter exactly {$assetCount} unique asset IDs, one per line, in the same order as the asset receipt lines."]);
            }
            foreach ($manualTags as $tag) {
                if (strlen($tag) > 80 || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._\/-]*$/', $tag)) {
                    throw ValidationException::withMessages(['asset_tags' => 'Each asset ID must be 1–80 characters and contain only letters, numbers, dots, underscores, slashes or hyphens.']);
                }
            }
            if (count(array_unique(array_map('strtolower', $manualTags))) !== count($manualTags)) {
                throw ValidationException::withMessages(['asset_tags' => 'Duplicate asset IDs were entered. Each asset must have a unique ID.']);
            }
            foreach (array_chunk($manualTags, 500) as $tagChunk) {
                if (Asset::whereIn('asset_tag', $tagChunk)->exists()) {
                    throw ValidationException::withMessages(['asset_tags' => 'One or more asset IDs are already registered. Review the list and try again.']);
                }
            }
        }
        $defaultUsefulLife = (int) data_get(SystemSetting::where('key', 'default_useful_life_months')->first()?->value, 'value', 48);
        $defaultDepreciationMethod = (string) data_get(SystemSetting::where('key', 'default_depreciation_method')->first()?->value, 'value', 'straight_line');
        $defaultSalvagePercent = (float) data_get(SystemSetting::where('key', 'default_salvage_percent')->first()?->value, 'value', 0);

        DB::transaction(function () use ($receivingReport, $request, $data, $ledger, $assetTags, $manualTags, $automaticNumbering, $defaultUsefulLife, $defaultDepreciationMethod, $defaultSalvagePercent) {
            $report = ReceivingReport::with(['purchaseOrder.purchaseRequest', 'items.purchaseRequestItem'])->whereKey($receivingReport->id)->lockForUpdate()->firstOrFail();
            abort_unless($report->status === 'pending_inspection', 422, 'This receiving report has already been inspected.');

            if ($data['inspection_result'] === 'rejected') {
                $report->update(['status' => 'rejected', 'inspected_by' => $request->user()->id, 'inspection_remarks' => $data['inspection_remarks'] ?? $report->inspection_remarks]);
                Audit::record('receiving_report_rejected', 'receiving_reports', ReceivingReport::class, $report->id);
                return;
            }

            $order = $report->purchaseOrder;
            $purchaseRequest = $order->purchaseRequest;
            $manualTagIndex = 0;
            foreach ($report->items as $line) {
                if ($line->registered_id || ! empty($line->registered_ids)) {
                    continue;
                }
                $requestItem = $line->purchaseRequestItem;
                abort_unless($requestItem, 422, 'A purchase request line was removed. The receiving report cannot be registered.');

                if ($line->registered_as === 'asset') {
                    $assetIds = [];
                    $category = $requestItem->category_id
                        ? AssetCategory::query()->where('type', 'asset')->where('is_active', true)->findOrFail($requestItem->category_id)
                        : null;
                    $categoryUsefulLife = $category?->useful_life_months ?: ($category?->default_useful_life_months ?: $defaultUsefulLife);
                    $categoryDepreciationMethod = $category && ! in_array($category->depreciation_method, [null, '', 'not_configured'], true)
                        ? $category->depreciation_method : $defaultDepreciationMethod;
                    $categorySalvagePercent = $category && (float) $category->salvage_percent > 0
                        ? (float) $category->salvage_percent : $defaultSalvagePercent;
                    for ($unit = 1; $unit <= (int) $line->qty_received; $unit++) {
                        $assetTag = $automaticNumbering ? $assetTags->next() : $manualTags[$manualTagIndex++];
                        $purchaseCost = (float) $requestItem->est_unit_cost;
                        $asset = Asset::create([
                            'asset_tag' => $assetTag,
                            'name' => $requestItem->description . ((int) $line->qty_received > 1 ? ' #' . $unit : ''),
                            'category_id' => $requestItem->category_id,
                            'purchase_date' => $report->received_at ?? today(),
                            'purchase_cost' => $purchaseCost,
                            'salvage_value' => round($purchaseCost * $categorySalvagePercent / 100, 2),
                            'useful_life_months' => $categoryUsefulLife,
                            'depreciation_method' => $categoryDepreciationMethod,
                            'supplier_id' => $order->supplier_id,
                            'purchase_order_id' => $order->id,
                            'department_id' => $purchaseRequest->department_id,
                            'location_id' => $report->location_id,
                            'condition' => 'New',
                            'status' => 'available',
                            'remarks' => 'Registered from receiving report ' . $report->rr_no,
                        ]);
                        app(\App\Services\AssetHistoryWriter::class)->record($asset, 'receiving_inspection_registered', [], $asset->getAttributes());
                        $assetIds[] = $asset->id;
                    }
                    $line->update(['registered_model' => Asset::class, 'registered_id' => $assetIds[0] ?? null, 'registered_ids' => $assetIds]);
                } else {
                    $sequence = $this->nextSequence('stock-sku:' . now()->format('Y'));
                    $consumableCategory = $requestItem->category_id
                        ? AssetCategory::query()->where('type', 'consumable')->where('is_active', true)->findOrFail($requestItem->category_id)
                        : null;
                    $systemMinimum = (int) data_get(SystemSetting::where('key', 'low_stock_default')->first()?->value, 'value', 5);
                    $minimumStock = (int) ($consumableCategory?->default_min_stock_level ?: $systemMinimum);
                    $inventoryItem = InventoryItem::create([
                        'sku' => sprintf('STK-%s-%05d', now()->format('Y'), $sequence),
                        'name' => $requestItem->description,
                        'category_id' => $consumableCategory?->id,
                        'unit' => 'unit',
                        'quantity_on_hand' => 0,
                        'reorder_level' => $minimumStock,
                        'min_level' => $minimumStock,
                        'unit_cost' => $requestItem->est_unit_cost,
                        'location_id' => $report->location_id,
                        'supplier_id' => $order->supplier_id,
                        'description' => 'Received from purchase order ' . $order->po_no,
                    ]);
                    $ledger->move(
                        $inventoryItem->id, 'receive', (int) $line->qty_received, $request->user()->id,
                        $report->rr_no, 'Accepted receipt of purchased consumable.',
                        $line->batch_no, $line->expiry_date?->toDateString(), $report->location_id
                    );
                    $line->update(['registered_model' => InventoryItem::class, 'registered_id' => $inventoryItem->id]);
                }
            }

            $report->update(['status' => 'accepted', 'accepted_at' => today(), 'inspected_by' => $request->user()->id, 'inspection_remarks' => $data['inspection_remarks'] ?? $report->inspection_remarks]);

            $ordered = (int) $purchaseRequest->items()->sum('qty');
            $accepted = (int) ReceivingReportItem::whereHas('receivingReport', fn ($query) => $query->where('status', 'accepted')->where('purchase_order_id', $order->id))->sum('qty_received');
            $complete = $accepted >= $ordered;
            $order->update(['status' => $complete ? 'received' : 'partially_received']);
            $purchaseRequest->update(['status' => $complete ? 'completed' : 'partially_received']);
        }, 3);

        Audit::record('receiving_report_inspected', 'receiving_reports', ReceivingReport::class, $receivingReport->id, [], ['result' => $data['inspection_result']]);

        return back()->with('success', $data['inspection_result'] === 'accepted' ? 'Inspection accepted. Assets and stock items were registered transactionally.' : 'Inspection rejected. No assets or stock were registered.');
    }

    private function nextSequence(string $key): int
    {
        DB::table('number_sequences')->insertOrIgnore([
            'key' => $key, 'current_value' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $sequence = DB::table('number_sequences')->where('key', $key)->lockForUpdate()->first();
        $next = ((int) $sequence->current_value) + 1;
        DB::table('number_sequences')->where('id', $sequence->id)->update(['current_value' => $next, 'updated_at' => now()]);

        return $next;
    }
}
