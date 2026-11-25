@extends('layouts.app')
@section('title', 'Inventory Ledger')
@section('breadcrumb', 'Inventory Ledger')
@section('content')
<style>
.ledger-filter{padding:12px 16px;border-bottom:1px solid #e9edf2;background:#fff}.ledger-filter .form-label{font-size:.78rem;font-weight:600;color:#475569;margin-bottom:4px}.ledger-filter .form-control,.ledger-filter .form-select{height:36px;min-height:36px;font-size:.84rem;border:1px solid #d8dee6;border-radius:5px;box-shadow:none}.ledger-filter .form-control:focus,.ledger-filter .form-select:focus{border-color:#7aa7c7;box-shadow:0 0 0 .12rem rgba(39,125,180,.10)}.ledger-filter .filter-row{display:grid;gap:10px;align-items:end}.ledger-filter .filter-actions{display:flex;gap:6px}.ledger-filter .btn{height:36px;padding:0 13px;font-size:.82rem;white-space:nowrap}.ledger-table th{white-space:nowrap;font-size:.76rem}.ledger-table td{font-size:.8rem;vertical-align:middle}@media(max-width:900px){.ledger-filter .filter-row{grid-template-columns:repeat(2,minmax(0,1fr))!important}}@media(max-width:560px){.ledger-filter .filter-row{grid-template-columns:1fr!important}.ledger-filter .filter-actions{justify-content:flex-start}}
</style>
<div class="page-heading asset-register-heading">
    <div>
        <div class="eyebrow">INVENTORY CONTROL</div>
        <h1>Inventory Ledger</h1>
        <p>Search and review recorded stock receipts, issues, returns, transfers, adjustments, damages, and losses.</p>
    </div>
    <div class="heading-actions">
        <a href="{{ route('inventory.index', 'consumables') }}" class="btn btn-light border"><i class="bi bi-box-seam me-1"></i>Stock register</a>
        <a href="{{ route('stock-counts.index') }}" class="btn btn-light border"><i class="bi bi-clipboard2-check me-1"></i>Stock counts</a>
    </div>
</div>
<div class="panel asset-register-panel">
    <form method="GET" class="ledger-filter">
        <div class="filter-row" style="grid-template-columns:minmax(220px,2fr) minmax(150px,1fr) minmax(145px,1fr) minmax(145px,1fr) auto">
            <div><label class="form-label" for="ledger-q">Search item</label><input id="ledger-q" class="form-control" name="q" value="{{ $data['q'] ?? '' }}" placeholder="Item name or SKU"></div>
            <div><label class="form-label" for="ledger-type">Movement</label><select id="ledger-type" name="type" class="form-select"><option value="">All movements</option>@foreach(['receive'=>'Receive','issue'=>'Issue','return'=>'Return','adjust'=>'Adjustment','transfer_in'=>'Transfer in','transfer_out'=>'Transfer out','damaged'=>'Damaged','lost'=>'Lost'] as $value=>$label)<option value="{{ $value }}" @selected(($data['type'] ?? '')===$value)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="form-label" for="ledger-from">Date from</label><input id="ledger-from" type="date" name="from" class="form-control" value="{{ $data['from'] ?? '' }}"></div>
            <div><label class="form-label" for="ledger-to">Date to</label><input id="ledger-to" type="date" name="to" class="form-control" value="{{ $data['to'] ?? '' }}"></div>
            <div class="filter-actions"><button class="btn btn-primary"><i class="bi bi-search me-1"></i>Apply</button><a href="{{ route('stock-ledger.index') }}" class="btn btn-light border">Reset</a></div>
        </div>
    </form>
    <div class="px-3 py-2 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
        <div class="small text-secondary">Showing {{ $movements->firstItem() ?? 0 }}–{{ $movements->lastItem() ?? 0 }} of {{ $movements->total() }} movement records</div>
        <div class="small text-secondary">Latest movements first · Append-only ledger</div>
    </div>
    <div class="table-responsive">
        <table class="table data-table ledger-table align-middle mb-0">
            <thead><tr><th>Date / Time</th><th>SKU</th><th>Inventory item</th><th>Movement</th><th>Quantity</th><th>Batch / Lot</th><th>Location</th><th>Reference</th><th>Unit cost</th><th>Performed by</th></tr></thead>
            <tbody>
            @forelse($movements as $movement)
                <tr>
                    <td class="text-nowrap">{{ $movement->created_at?->format(config('app.date_format','d M Y').' '.config('app.time_format','H:i')) ?? '—' }}</td>
                    <td class="mono">{{ $movement->item?->sku ?? '—' }}</td>
                    <td><a href="{{ route('inventory.show',['consumables',$movement->stock_item_id]) }}" class="fw-semibold text-decoration-none">{{ $movement->item?->name ?? 'Unknown item' }}</a><div class="table-sub">{{ $movement->item?->category?->name ?? 'Uncategorized' }}</div></td>
                    <td><span class="status-pill {{ in_array($movement->type,['issue','damaged','lost','transfer_out'],true)?'warning':(str_contains($movement->type,'transfer')?'info':'success') }}">{{ str_replace('_',' ',$movement->type) }}</span></td>
                    <td class="mono fw-semibold {{ $movement->qty<0?'text-danger':'text-success' }}">{{ $movement->qty>0?'+':'' }}{{ number_format($movement->qty) }} {{ $movement->item?->unit }}</td>
                    <td>{{ $movement->batch_no ?? '—' }}@if($movement->expiry_date)<div class="table-sub">Exp: {{ $movement->expiry_date->format(config('app.date_format','d M Y')) }}</div>@endif</td>
                    <td>{{ $movement->location?->name ?? 'Unassigned' }}@if($movement->toLocation)<div class="table-sub">To: {{ $movement->toLocation->name }}</div>@endif</td>
                    <td>{{ $movement->reference_text ?? ($movement->reference_id ? class_basename($movement->reference_type).' #'.$movement->reference_id : '—') }}</td>
                    <td class="mono">{{ number_format((float)$movement->unit_cost,2) }}</td>
                    <td>{{ $movement->performer?->name ?? 'System' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="empty-state">No stock movement records match the selected filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($movements->hasPages())<div class="p-3 border-top">{{ $movements->links() }}</div>@endif
</div>
@endsection
