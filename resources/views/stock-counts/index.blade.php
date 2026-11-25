@extends('layouts.app')
@section('title','Stock Counts')
@section('breadcrumb','Stock Counts')
@section('content')
<div class="page-heading asset-register-heading">
    <div><div class="eyebrow">STOCK CONTROL</div><h1>Physical stock counts</h1><p>Record observed quantities, review variances, and reconcile balances through the stock ledger.</p></div>
    <div class="heading-actions">@if(auth()->user()->hasPermission('reports.export'))<a class="btn btn-light border" href="{{ route('stock-counts.variance.csv') }}"><i class="bi bi-filetype-csv me-1"></i>Export variances</a>@endif</div>
</div>
<div class="row g-3 align-items-start">
    @if(auth()->user()->hasPermission('consumables.manage'))
    <div class="col-xl-4">
        <div class="panel">
            <div class="panel-header"><div><h2>Start a stock count</h2><p>Creates a snapshot of current quantities for the selected location.</p></div></div>
            <form method="POST" action="{{ route('stock-counts.store') }}" class="p-3">@csrf
                <div class="mb-3"><label class="form-label">Location</label><select class="form-select" name="location_id"><option value="">All locations</option>@foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label">Count date</label><input class="form-control" type="date" name="counted_on" value="{{ today()->toDateString() }}" required></div>
                <div class="mb-3"><label class="form-label">Instructions / remarks</label><textarea class="form-control" name="remarks" rows="3" maxlength="2000" placeholder="Optional instructions for the counting team"></textarea></div>
                <button class="btn btn-primary w-100"><i class="bi bi-plus-lg me-2"></i>Create count checklist</button>
            </form>
        </div>
    </div>
    @endif
    <div class="col-xl-{{ auth()->user()->hasPermission('consumables.manage')?'8':'12' }}">
        <div class="panel asset-register-panel"><div class="panel-header"><div><h2>Stock count register</h2><p>Showing {{ $counts->firstItem() ?? 0 }} to {{ $counts->lastItem() ?? 0 }} of {{ $counts->total() }} count sessions</p></div></div>
            <div class="table-responsive"><table class="table data-table stock-count-table mb-0"><thead><tr><th>Count no.</th><th>Location</th><th>Count date</th><th>Counter</th><th>Status</th><th>Items</th><th></th></tr></thead><tbody>
            @forelse($counts as $count)<tr><td class="mono">{{ $count->count_no }}</td><td>{{ $count->location?->name ?? 'All locations' }}</td><td>{{ $count->counted_on?->format(config('app.date_format', 'd M Y')) ?? '—' }}</td><td>{{ $count->counter?->name ?? '—' }}</td><td><span class="status-pill {{ $count->status }}">{{ str_replace('_',' ',$count->status) }}</span></td><td>{{ $count->items()->count() }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('stock-counts.show',$count) }}">Open</a></td></tr>
            @empty<tr><td colspan="7" class="empty-state">No stock count sessions yet.</td></tr>@endforelse
            </tbody></table></div><div class="pagination-wrap">{{ $counts->links() }}</div>
        </div>
    </div>
</div>
@endsection
