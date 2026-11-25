@extends('layouts.app')
@section('title','Software license catalog')
@section('breadcrumb','Software & Licenses')
@section('content')
<div class="page-heading">
    <div><div class="eyebrow">SOFTWARE ASSET MANAGEMENT</div><h1>Software license catalog</h1><p>Manage software products, entitlements, seat allocation, renewal history and expiry exposure.</p></div>
</div>

@if(auth()->user()->hasPermission('software.manage'))
<div class="panel mb-3">
    <div class="panel-header"><div><h2>Register software product</h2><p>Create a normalized software record before registering license entitlements.</p></div></div>
    <form method="POST" action="{{ route('software-catalog.store') }}" class="p-3">
        @csrf
        <div class="row g-3 align-items-end">
            <div class="col-md-4"><label class="form-label" for="software-name">Software name</label><input id="software-name" name="name" class="form-control" maxlength="190" required></div>
            <div class="col-md-2"><label class="form-label" for="software-version">Version</label><input id="software-version" name="version" class="form-control" maxlength="80"></div>
            <div class="col-md-3"><label class="form-label" for="software-publisher">Publisher</label><input id="software-publisher" name="publisher" class="form-control" maxlength="150"></div>
            <div class="col-md-3"><label class="form-label" for="software-category">Category</label><select id="software-category" name="category_id" class="form-select"><option value="">No category</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label" for="software-description">Description</label><textarea id="software-description" name="description" class="form-control" rows="2" maxlength="5000"></textarea></div>
            <div class="col-12 text-end"><button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Register software</button></div>
        </div>
    </form>
</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-2"><div><h2 class="h6 fw-bold mb-1">Registered software</h2><p class="small text-secondary mb-0">{{ $software->count() }} software product(s) · {{ $software->sum('licenses_count') }} license entitlement(s)</p></div></div>
<div class="row g-3">
@forelse($software as $product)
    <div class="col-12">
        <section class="panel">
            <div class="panel-header flex-wrap">
                <div class="d-flex align-items-center gap-3">
                    <div class="record-symbol"><i class="bi bi-window-stack"></i></div>
                    <div><h2>{{ $product->name }} @if($product->version)<span class="text-secondary fw-normal">{{ $product->version }}</span>@endif</h2><p>{{ $product->publisher ?: 'Publisher not specified' }} · {{ $product->category?->name ?: 'Uncategorized' }}</p></div>
                </div>
                @if(auth()->user()->hasPermission('software.manage'))
                <details><summary class="btn btn-sm btn-outline-secondary">Edit software details</summary>
                    <form method="POST" action="{{ route('software-catalog.update',$product) }}" class="p-3 border rounded mt-2 bg-white">
                        @csrf @method('PUT')
                        <div class="row g-2"><div class="col-md-4"><label class="form-label">Software name</label><input name="name" class="form-control" required value="{{ $product->name }}"></div><div class="col-md-2"><label class="form-label">Version</label><input name="version" class="form-control" value="{{ $product->version }}"></div><div class="col-md-3"><label class="form-label">Publisher</label><input name="publisher" class="form-control" value="{{ $product->publisher }}"></div><div class="col-md-3"><label class="form-label">Category</label><select name="category_id" class="form-select"><option value="">No category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($product->category_id===$category->id)>{{ $category->name }}</option>@endforeach</select></div><div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2">{{ $product->description }}</textarea></div><div class="col-12 text-end"><button class="btn btn-sm btn-primary">Save changes</button></div></div>
                    </form>
                </details>
                @endif
            </div>
            <div class="p-3">
                @forelse($product->licenses as $license)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                            <div><strong class="small text-dark">{{ ucfirst(str_replace('_',' ',$license->type)) }} entitlement #{{ $license->id }}</strong><div class="small text-secondary">{{ $license->supplier?->company_name ?: 'Supplier not recorded' }} · Purchased {{ $license->purchase_date?->format(config('app.date_format', 'd M Y')) ?: 'date not recorded' }} · Expires {{ $license->expiry_date?->format(config('app.date_format', 'd M Y')) ?: 'No expiry recorded' }}</div></div>
                            <div class="text-end"><span class="status-pill {{ $license->expiry_date && $license->expiry_date->isPast() ? 'defective' : 'active' }}">{{ $license->expiry_date && $license->expiry_date->isPast() ? 'Expired' : 'Current' }}</span><div class="small text-secondary mt-1">{{ $license->seats_assigned }} / {{ $license->seats_total }} seats assigned</div></div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 small text-secondary mb-3"><span>Cycle: {{ $license->subscription_cycle ?: 'Not specified' }}</span><span>·</span><span>Cost: {{ number_format((float)$license->cost,2) }}</span><span>·</span><span>Available: {{ $license->seats_available }}</span></div>
                        <div class="table-responsive"><table class="table table-sm align-middle mb-2"><thead><tr><th>Assignee</th><th>Type</th><th>Assigned</th><th>State</th><th></th></tr></thead><tbody>
                            @forelse($license->assignments as $allocation)
                                <tr><td>{{ $allocation->assignable?->full_name ?? $allocation->assignable?->asset_tag ?? 'Record unavailable' }}</td><td>{{ class_basename($allocation->assignable_type) }}</td><td>{{ $allocation->assigned_at?->format(config('app.date_format', 'd M Y')) }}</td><td><span class="status-pill {{ $allocation->revoked_at ? 'inactive' : 'active' }}">{{ $allocation->revoked_at ? 'Revoked' : 'Active' }}</span></td><td class="text-end">@if(!$allocation->revoked_at && auth()->user()->hasPermission('software.manage'))<form method="POST" action="{{ route('software-catalog.licenses.revoke',$allocation) }}" onsubmit="return confirm('Revoke this software license allocation?')">@csrf<button class="btn btn-sm btn-outline-danger">Revoke</button></form>@endif</td></tr>
                            @empty<tr><td colspan="5" class="text-secondary small py-2">No license allocations yet.</td></tr>@endforelse
                        </tbody></table></div>
                        @if(auth()->user()->hasPermission('software.manage'))
                        <div class="row g-3 mt-1">
                            <div class="col-lg-5"><div class="bg-light rounded p-3 h-100"><strong class="small d-block mb-2">Allocate a seat</strong><form method="POST" action="{{ route('software-catalog.licenses.assign',$license) }}">@csrf<div class="row g-2"><div class="col-12"><label class="form-label">Employee (optional)</label><select name="employee_id" class="form-select form-select-sm"><option value="">Select employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no }} — {{ $employee->full_name }}</option>@endforeach</select></div><div class="col-12"><label class="form-label">IT asset (optional)</label><select name="asset_id" class="form-select form-select-sm"><option value="">Select asset</option>@foreach($assets as $asset)<option value="{{ $asset->id }}">{{ $asset->asset_tag }} — {{ $asset->name }}</option>@endforeach</select><small class="form-text">Select one employee or one asset, not both.</small></div><div class="col-12"><label class="form-label">Assigned date</label><input type="date" name="assigned_at" value="{{ today()->toDateString() }}" class="form-control form-control-sm" required></div><div class="col-12 text-end"><button class="btn btn-sm btn-primary" @disabled($license->seats_available<1)>Assign seat</button></div></div></form></div></div>
                            <div class="col-lg-7"><div class="bg-light rounded p-3 h-100"><strong class="small d-block mb-2">Record license renewal</strong><form method="POST" action="{{ route('software-catalog.licenses.renew',$license) }}">@csrf<div class="row g-2"><div class="col-md-4"><label class="form-label">Renewed on</label><input type="date" name="renewed_on" class="form-control form-control-sm" value="{{ today()->toDateString() }}" required></div><div class="col-md-4"><label class="form-label">New expiry</label><input type="date" name="new_expiry" class="form-control form-control-sm" value="{{ today()->addYear()->toDateString() }}" required></div><div class="col-md-4"><label class="form-label">Renewal cost</label><input type="number" step="0.01" min="0" name="cost" class="form-control form-control-sm" value="{{ $license->cost }}" required></div><div class="col-12"><label class="form-label">Remarks</label><input name="remarks" class="form-control form-control-sm" maxlength="2000"></div><div class="col-12 text-end"><button class="btn btn-sm btn-outline-primary">Save renewal</button></div></div></form>
                                @if($license->renewals->isNotEmpty())<div class="mt-3"><span class="small fw-semibold">Renewal history</span><ul class="small text-secondary mb-0">@foreach($license->renewals->sortByDesc('renewed_on') as $renewal)<li>{{ $renewal->renewed_on?->format(config('app.date_format', 'd M Y')) }} → {{ $renewal->new_expiry?->format(config('app.date_format', 'd M Y')) }} · {{ number_format((float)$renewal->cost,2) }} · {{ $renewal->renewedBy?->name ?? 'System' }}</li>@endforeach</ul></div>@endif
                            </div></div>
                        </div>
                        @endif
                    </div>
                @empty
                    <div class="empty-state">No license entitlements registered for this software product.</div>
                @endforelse
                @if(auth()->user()->hasPermission('software.manage'))
                    <details class="mt-3"><summary class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Add license entitlement</summary>
                        <form method="POST" action="{{ route('software-catalog.licenses.store',$product) }}" class="mt-2 p-3 rounded border bg-white">@csrf<div class="row g-2"><div class="col-md-3"><label class="form-label">License type</label><select name="type" class="form-select form-select-sm" required><option value="subscription">Subscription</option><option value="perpetual">Perpetual</option><option value="volume">Volume</option><option value="open_source">Open source</option><option value="trial">Trial</option></select></div><div class="col-md-2"><label class="form-label">Total seats</label><input type="number" name="seats_total" min="1" class="form-control form-control-sm" value="1" required></div><div class="col-md-2"><label class="form-label">Purchase date</label><input type="date" name="purchase_date" class="form-control form-control-sm"></div><div class="col-md-2"><label class="form-label">Expiry date</label><input type="date" name="expiry_date" class="form-control form-control-sm"></div><div class="col-md-3"><label class="form-label">Total cost</label><input type="number" step="0.01" min="0" name="cost" class="form-control form-control-sm" value="0" required></div><div class="col-md-3"><label class="form-label">Subscription cycle</label><select name="subscription_cycle" class="form-select form-select-sm"><option value="">Not applicable</option><option value="monthly">Monthly</option><option value="quarterly">Quarterly</option><option value="annually">Annually</option><option value="biennial">Biennial</option><option value="one_time">One time</option><option value="other">Other</option></select></div><div class="col-md-3"><label class="form-label">Supplier</label><select name="supplier_id" class="form-select form-select-sm"><option value="">Not recorded</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->company_name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">License key (encrypted at rest)</label><input name="license_key" class="form-control form-control-sm" maxlength="5000" autocomplete="off"></div><div class="col-12 text-end"><button class="btn btn-sm btn-primary">Save entitlement</button></div></div></form>
                    </details>
                @endif
            </div>
        </section>
    </div>
@empty
    <div class="col-12"><div class="panel empty-state">No software products have been registered yet.</div></div>
@endforelse
</div>
@endsection
