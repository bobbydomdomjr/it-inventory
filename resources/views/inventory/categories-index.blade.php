@extends('layouts.app')
@section('title', 'Asset Categories')
@section('breadcrumb', 'Asset Categories')

@push('styles')
<style>
.category-list-page{font-size:12px;color:#344054;margin:-15px -15px 0}
.category-list-panel{background:#fff;border:1px solid #e4e7ec;border-radius:5px;box-shadow:0 1px 2px rgba(16,24,40,.035);overflow:hidden}
.category-list-toolbar{min-height:43px;padding:6px 9px;display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:1px solid #e6e9ed;background:#fff}
.category-toolbar-left,.category-toolbar-right{display:flex;align-items:center;gap:5px;min-width:0}
.category-quick-search{display:flex;align-items:center;width:min(310px,32vw)}
.category-quick-search input{height:29px;min-width:80px;width:100%;padding:4px 8px;border:1px solid #d0d5dd;border-right:0;border-radius:3px 0 0 3px;font-size:11px;outline:none}
.category-quick-search input:focus{border-color:#6c9fbd;box-shadow:0 0 0 2px rgba(52,127,167,.08)}
.category-quick-search button{height:29px;width:30px;border:1px solid #347fa7;border-radius:0 3px 3px 0;background:#347fa7;color:#fff;display:grid;place-items:center;padding:0}
.category-filter-select{height:29px;min-width:112px;padding:3px 23px 3px 7px;border:1px solid #d0d5dd;border-radius:3px;background:#fff;color:#475467;font-size:11px}
.category-mini-btn{height:29px;min-width:29px;padding:0 8px;border:1px solid #d0d5dd;border-radius:3px;background:#fff;color:#526273;display:inline-flex;align-items:center;justify-content:center;font-size:11px;text-decoration:none;white-space:nowrap}
.category-mini-btn:hover{background:#f5f7fa;color:#245b88;border-color:#aab7c4}
.category-mini-btn.primary{background:#347fa7;border-color:#347fa7;color:#fff}
.category-secondary-bar{min-height:36px;padding:5px 10px;display:flex;align-items:center;justify-content:space-between;gap:10px;background:#fff;border-bottom:1px solid #e6e9ed}
.category-record-meta{font-size:11px;color:#606d7a;white-space:nowrap}
.category-register-table{width:100%;min-width:900px;margin:0;border-collapse:separate;border-spacing:0;font-size:11px}
.category-register-table thead th{height:31px;padding:6px 8px;border-top:0;border-bottom:1px solid #dfe4e9;background:#f8f9fa;color:#46515d;font-size:10px;font-weight:700;white-space:nowrap;vertical-align:middle}
.category-register-table tbody tr{height:35px}
.category-register-table tbody tr:nth-child(even){background:#fafafa}
.category-register-table tbody tr:hover{background:#f1f7fb}
.category-register-table tbody td{height:35px;padding:4px 8px;border-bottom:1px solid #eef0f2;color:#667085;vertical-align:middle;white-space:nowrap}
.category-code{font-family:'DM Mono',ui-monospace,monospace;font-size:10px;color:#5485a0}
.category-name{font-size:11px;color:#4386a9;text-decoration:none;font-weight:600}
.category-type-pill{display:inline-block;padding:2px 5px;border-radius:2px;background:#f0f3f6;color:#637083;font-size:9px;font-weight:600;text-transform:capitalize}
.category-description{max-width:250px;overflow:hidden;text-overflow:ellipsis;color:#7b8491}
.category-actions{display:flex;align-items:center;justify-content:flex-end;gap:3px}
.category-actions form{margin:0;display:inline-flex}
.category-icon-action{width:22px;height:22px;display:inline-grid;place-items:center;padding:0;border:0;border-radius:2px;background:#24afd0;color:#fff;text-decoration:none;font-size:11px}
.category-icon-action.edit{background:#efa51c}
.category-icon-action.archive{background:#d94b3b}
.category-icon-action:hover{filter:brightness(.92);color:#fff}
.category-status{display:inline-block;padding:2px 5px;border-radius:2px;font-size:9px;font-weight:600}
.category-status.active{background:#eaf6ee;color:#38804e}
.category-status.inactive{background:#f0f1f3;color:#727b87}
.category-pagination{display:flex;justify-content:flex-end;padding:6px 9px;background:#fff}
.category-pagination .pagination{margin:0;gap:2px}
.category-pagination .page-link{min-width:25px;height:25px;padding:4px 7px;border:1px solid #e0e4e9;border-radius:3px!important;background:#fff;color:#475467;font-size:10px;line-height:15px;text-align:center}
.category-pagination .page-item.active .page-link{background:#347fa7;border-color:#347fa7;color:#fff}
.category-pagination .page-item.disabled .page-link{color:#a5adb7;background:#fafbfc}
@media(max-width:850px){.category-list-toolbar{align-items:flex-start;flex-direction:column}.category-toolbar-left,.category-toolbar-right{width:100%;flex-wrap:wrap}.category-toolbar-right{justify-content:flex-start}.category-quick-search{width:min(100%,380px)}}
@media(max-width:520px){.category-toolbar-right{display:grid;grid-template-columns:1fr 1fr}.category-filter-select{width:100%}}
</style>
@endpush

@section('content')
<div class="category-list-page">
 <div class="category-list-panel">
  <form method="GET" action="{{ route('inventory.index', 'categories') }}" class="category-list-toolbar" role="search">
   <div class="category-toolbar-left">
    <div class="category-quick-search">
     <input type="search" name="q" value="{{ request('q') }}" placeholder="Search category name, code, or example..." aria-label="Search categories">
     <button type="submit" title="Search categories" aria-label="Search"><i class="bi bi-search"></i></button>
    </div>
    @if(request()->filled('q'))<a class="category-mini-btn" href="{{ route('inventory.index', 'categories') }}" title="Clear search and filters" aria-label="Clear search and filters"><i class="bi bi-x-lg"></i></a>@endif
   </div>
   <div class="category-toolbar-right">
    <select class="category-filter-select" name="type" aria-label="Filter by category type">
     <option value="">All types</option>
     @foreach($filterOptions['type'] ?? [] as $value=>$label)<option value="{{ $value }}" @selected((string)request('type')===(string)$value)>{{ $label }}</option>@endforeach
    </select>
    <select class="category-filter-select" name="is_active" aria-label="Filter by category status">
     <option value="">All statuses</option>
     @foreach($filterOptions['is_active'] ?? [] as $value=>$label)<option value="{{ $value }}" @selected((string)request('is_active')===(string)$value)>{{ $label }}</option>@endforeach
    </select>
    <button class="category-mini-btn" type="submit" title="Apply filters" aria-label="Apply filters"><i class="bi bi-funnel"></i></button>
    @if(request()->hasAny(['q','type','is_active']))<a class="category-mini-btn" href="{{ route('inventory.index', 'categories') }}" title="Reset filters" aria-label="Reset filters"><i class="bi bi-arrow-counterclockwise"></i></a>@endif
    @if(auth()->user()->hasPermission('categories.manage'))<a class="category-mini-btn primary" href="{{ route('inventory.create', 'categories') }}" title="Add category" aria-label="Add category"><i class="bi bi-plus-lg"></i></a>@endif
   </div>
  </form>

  <div class="category-secondary-bar">
   <div class="d-flex align-items-center gap-2">
    <span class="category-record-meta">Showing <strong>{{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }}</strong> of <strong>{{ number_format($records->total()) }}</strong> categories</span>
    <form method="GET" action="{{ route('inventory.index', 'categories') }}" class="d-inline-flex align-items-center gap-1">
     @foreach(request()->except(['per_page','page']) as $key=>$value) @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
     <label class="category-record-meta" for="categoryPageSize">Rows</label>
     <select id="categoryPageSize" name="per_page" class="category-filter-select" style="min-width:58px;width:62px" onchange="this.form.submit()">
      @foreach([10,25,50,100] as $size)<option value="{{ $size }}" @selected($perPage===$size)>{{ $size }}</option>@endforeach
     </select>
    </form>
   </div>
   <div class="category-pagination">{{ $records->onEachSide(2)->links() }}</div>
  </div>

  <div class="table-responsive">
   <table class="table category-register-table align-middle mb-0">
    <thead><tr><th style="width:78px">Code</th><th>Category</th><th style="width:95px">Type</th><th>Parent</th><th>Examples</th><th style="width:125px">Depreciation / salvage</th><th style="width:88px">Useful life</th><th style="width:75px">Min. stock</th><th style="width:70px">Fields</th><th style="width:75px">Status</th><th style="width:88px" class="text-end">Actions</th></tr></thead>
    <tbody>
     @forelse($records as $category)
      <tr>
       <td><span class="category-code">{{ $category->code }}</span></td>
       <td><a class="category-name" href="{{ route('inventory.show', ['categories',$category->id]) }}">{{ $category->name }}</a></td>
       <td><span class="category-type-pill">{{ $category->type }}</span></td>
       <td>{{ $category->parent?->name ?? '—' }}</td>
       <td class="category-description" title="{{ $category->description }}">{{ $category->description ?: '—' }}</td>
       <td>{{ $category->depreciation_method && $category->depreciation_method !== 'not_configured' ? str_replace('_',' ',$category->depreciation_method) : '—' }}@if((float)$category->salvage_percent > 0)<span class="text-secondary"> · {{ rtrim(rtrim(number_format((float)$category->salvage_percent, 3), '0'), '.') }}%</span>@endif</td>
       <td>{{ ($category->useful_life_months ?: $category->default_useful_life_months) ? (($category->useful_life_months ?: $category->default_useful_life_months).' mo.') : '—' }}</td>
       <td>{{ $category->type === 'consumable' ? (int)$category->default_min_stock_level : '—' }}</td>
       <td>{{ count($category->custom_fields ?? []) ?: '—' }}</td>
       <td><span class="category-status {{ $category->is_active ? 'active' : 'inactive' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
       <td><div class="category-actions">
        <a class="category-icon-action" href="{{ route('inventory.show', ['categories',$category->id]) }}" title="View category" aria-label="View category"><i class="bi bi-eye"></i></a>
        @if(auth()->user()->hasPermission('categories.manage'))
         <a class="category-icon-action edit" href="{{ route('inventory.edit', ['categories',$category->id]) }}" title="Edit category" aria-label="Edit category"><i class="bi bi-pencil"></i></a>
         @if($category->is_active)
          <form method="POST" action="{{ route('inventory.destroy', ['categories',$category->id]) }}" onsubmit="return confirm('Archive this category? Existing linked asset and stock records will remain connected.')">@csrf @method('DELETE')<button class="category-icon-action archive" type="submit" title="Archive category" aria-label="Archive category"><i class="bi bi-archive"></i></button></form>
         @endif
        @endif
       </div></td>
      </tr>
     @empty
      <tr><td colspan="11" class="text-center py-5 text-secondary"><i class="bi bi-tags d-block fs-4 mb-1"></i>No categories found.</td></tr>
     @endforelse
    </tbody>
   </table>
  </div>
  <div class="category-pagination">{{ $records->onEachSide(2)->links() }}</div>
 </div>
</div>
@endsection
