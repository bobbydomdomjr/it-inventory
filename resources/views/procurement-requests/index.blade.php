@extends('layouts.app')
@section('title','Purchase Requests')
@section('breadcrumb','Procurement / Purchase Requests')
@section('content')
<div class="page-heading">
    <div><div class="eyebrow">PROCUREMENT</div><h1>Purchase requests</h1><p>Manage purchase requisitions, supplier quotations, purchase orders, receiving and inspection.</p></div>
    @if(auth()->user()->hasPermission('procurements.manage'))<a href="#newPurchaseRequest" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>New purchase request</a>@endif
</div>
@if(auth()->user()->hasPermission('procurements.manage'))
<div class="panel mb-3" id="newPurchaseRequest">
    <div class="panel-header"><div><h2>Create purchase request</h2><p>Enter the business purpose and one or more requested items.</p></div></div>
    <form method="POST" action="{{ route('procurement.requests.store') }}" class="p-3" id="purchaseRequestForm">@csrf
        <div class="row g-3 mb-3">
            <div class="col-lg-4"><label class="form-label" for="prDepartment">Requesting department</label><select class="form-select" name="department_id" id="prDepartment"><option value="">Select department (optional)</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id')==$department->id)>{{ $department->name }}</option>@endforeach</select></div>
            <div class="col-lg-8"><label class="form-label" for="prPurpose">Purpose / justification</label><input class="form-control" name="purpose" id="prPurpose" value="{{ old('purpose') }}" maxlength="5000" required placeholder="Explain the operational need for this procurement"></div>
        </div>
        <div class="d-flex justify-content-between align-items-center gap-2 mb-2"><div><strong class="small">Requested line items</strong><div class="small-muted">Select an asset or consumable category. Leave blank for general supplies.</div></div><button type="button" class="btn btn-sm btn-outline-primary" id="addPurchaseLine"><i class="bi bi-plus-lg me-1"></i>Add line</button></div>
        <div class="table-responsive"><table class="table align-middle mb-2" id="purchaseRequestItems"><thead><tr><th style="min-width:230px">Description</th><th style="min-width:190px">Asset / consumable category</th><th style="width:110px">Quantity</th><th style="width:160px">Est. unit cost</th><th style="width:46px"></th></tr></thead><tbody>
            @php($oldItems=old('items', [['description'=>'','category_id'=>'','qty'=>1,'est_unit_cost'=>'0.00']]))
            @foreach($oldItems as $index=>$item)<tr class="purchase-line"><td><input class="form-control" name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" maxlength="255" placeholder="Item / specification" required></td><td><select class="form-select" name="items[{{ $index }}][category_id]"><option value="">General supply (no category)</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(($item['category_id']??'')==$category->id)>{{ $category->name }} · {{ ucfirst($category->type) }}</option>@endforeach</select></td><td><input class="form-control" type="number" min="1" max="100000" name="items[{{ $index }}][qty]" value="{{ $item['qty'] ?? 1 }}" required></td><td><input class="form-control" type="number" min="0" step="0.01" name="items[{{ $index }}][est_unit_cost]" value="{{ $item['est_unit_cost'] ?? '0.00' }}" required></td><td><button type="button" class="btn btn-sm btn-light border remove-purchase-line" aria-label="Remove line"><i class="bi bi-x-lg"></i></button></td></tr>@endforeach
        </tbody></table></div>
        <div class="d-flex justify-content-end"><button class="btn btn-primary"><i class="bi bi-file-earmark-plus me-1"></i>Save draft request</button></div>
    </form>
</div>
@endif
<div class="panel">
    <div class="panel-header"><div><h2>Purchase request register</h2><p>{{ $requests->total() }} request(s)</p></div></div>
    <div class="list-toolbar"><form method="GET" class="inventory-filter-form"><input type="search" name="q" value="{{ request('q') }}" placeholder="Search request number or purpose"><select class="filter-select" name="status"><option value="">All statuses</option>@foreach(['draft','submitted','pending_approval','approved','rejected','revision_requested','ordered','partially_received','completed','cancelled'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>@endforeach</select><button type="submit">Apply</button>@if(request()->query())<a class="clear-search" href="{{ route('procurement.requests.index') }}">Clear</a>@endif</form></div>
    <div class="table-responsive"><table class="table data-table align-middle mb-0"><thead><tr><th>PR number</th><th>Purpose</th><th>Department</th><th>Requested by</th><th>Estimated total</th><th>Status</th><th>Purchase order</th><th></th></tr></thead><tbody>
    @forelse($requests as $purchaseRequest)<tr><td class="mono fw-semibold">{{ $purchaseRequest->pr_no }}</td><td class="text-wrap" style="min-width:210px">{{ \Illuminate\Support\Str::limit($purchaseRequest->purpose,85) }}</td><td>{{ $purchaseRequest->department?->name ?? '—' }}</td><td>{{ $purchaseRequest->requester?->name ?? '—' }}</td><td class="mono">{{ config('app.currency_symbol', '₱') }}{{ number_format((float)$purchaseRequest->total_estimated,2) }}</td><td><span class="status-pill {{ $purchaseRequest->status }}">{{ ucwords(str_replace('_',' ',$purchaseRequest->status)) }}</span></td><td class="mono">{{ $purchaseRequest->purchaseOrder?->po_no ?? '—' }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('procurement.requests.show',$purchaseRequest) }}">Open</a></td></tr>
    @empty<tr><td colspan="8" class="empty-state">No purchase requests have been created.</td></tr>@endforelse
    </tbody></table></div><div class="pagination-wrap">{{ $requests->withQueryString()->links() }}</div>
</div>
@endsection
@push('scripts')
<script>
(()=>{
 const body=document.querySelector('#purchaseRequestItems tbody'); const add=document.getElementById('addPurchaseLine'); if(!body||!add)return;
 let next=Math.max(0,...Array.from(body.querySelectorAll('input[name^="items["]')).map(el=>Number((el.name.match(/^items\[(\d+)\]/)||[])[1]||0)))+1;
 const categories=@json($categories->map(fn($category)=>['id'=>$category->id,'name'=>$category->name,'type'=>$category->type])->values());
 const escapeHtml=value=>String(value).replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
 const addRow=()=>{const i=next++;const options=categories.map(category=>`<option value="${category.id}">${escapeHtml(category.name)}</option>`).join('');const row=document.createElement('tr');row.className='purchase-line';row.innerHTML=`<td><input class="form-control" name="items[${i}][description]" maxlength="255" placeholder="Item / specification" required></td><td><select class="form-select" name="items[${i}][category_id]"><option value="">General supply (no category)</option>${options}</select></td><td><input class="form-control" type="number" min="1" max="100000" name="items[${i}][qty]" value="1" required></td><td><input class="form-control" type="number" min="0" step="0.01" name="items[${i}][est_unit_cost]" value="0.00" required></td><td><button type="button" class="btn btn-sm btn-light border remove-purchase-line" aria-label="Remove line"><i class="bi bi-x-lg"></i></button></td>`;body.append(row);};
 add.addEventListener('click',addRow);body.addEventListener('click',event=>{const button=event.target.closest('.remove-purchase-line');if(!button)return;const rows=body.querySelectorAll('.purchase-line');if(rows.length>1)button.closest('tr').remove();});
})();
</script>
@endpush
