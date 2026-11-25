@extends('layouts.app')
@section('title',$d['title']) @section('breadcrumb',$d['title'])
@push('styles')
<style>

/* Consumables register layout: intentionally scoped to avoid changing Assets. */
.stock-overview{display:grid;grid-template-columns:minmax(260px,2fr) minmax(360px,1fr);gap:12px;align-items:stretch;margin:12px 0 14px}
.stock-value-card{position:relative;overflow:hidden;min-height:112px;border:1px solid #d9e5ef;border-radius:7px;background:linear-gradient(110deg,#fff 0%,#fff 68%,#f3f8fc 100%);padding:16px 19px;display:flex;flex-direction:column;justify-content:center;gap:5px;box-shadow:0 1px 2px rgba(16,24,40,.04)}
/* No decorative left stripe: keep the analytics card clean and consistent with Categories. */
.stock-value-heading{display:flex;align-items:center;gap:8px}
.stock-value-icon{width:27px;height:27px;display:inline-grid;place-items:center;border:1px solid #d7e7f1;border-radius:6px;background:#eef6fb;color:#347fa7;font-size:14px}
.stock-overview-label{font-size:11px;font-weight:650;color:#526579;letter-spacing:.035em;text-transform:uppercase}
.stock-value-amount{display:flex;align-items:baseline;gap:5px;color:#243b53;font-variant-numeric:tabular-nums;line-height:1.1}
.stock-value-currency{font-size:16px;font-weight:650;color:#347fa7}
.stock-value-card strong{font-size:29px;line-height:1.1;font-weight:750;letter-spacing:-.035em;color:#243b53;font-variant-numeric:tabular-nums}
.stock-value-card small{font-size:10px;color:#7b8794;margin-top:1px}
.stock-value-card .stock-value-note{display:flex;align-items:center;gap:5px;color:#6b7f91}
.stock-value-card .stock-value-note i{font-size:11px;color:#347fa7}
.stock-quick-stats{display:grid;grid-template-rows:repeat(3,minmax(0,1fr));gap:6px}
.stock-quick-stat{border:1px solid #e1e6ec;border-radius:5px;background:#fff;padding:7px 11px;display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:27px}
.stock-quick-stat span{font-size:11px;color:#667085;font-weight:500}
.stock-quick-stat strong{font-size:13px;font-weight:700;color:#344054;font-variant-numeric:tabular-nums}
.stock-quick-stat.warning strong{color:#b7791f}
.stock-quick-stat.danger strong{color:#c2413b}
.stock-register-toolbar{min-height:48px;padding:8px 10px;display:flex;align-items:center;justify-content:space-between;gap:10px;border-bottom:1px solid #e4e7ec;background:#fff;flex-wrap:wrap}
.stock-toolbar-left{display:flex;align-items:center;min-width:260px;flex:1 1 320px}
.stock-toolbar-caption{font-size:12px;font-weight:650;color:#344054}
.stock-toolbar-right{display:flex;align-items:center;justify-content:flex-end;gap:5px;flex:0 0 auto;flex-wrap:wrap;margin-left:auto}
.stock-quick-search{display:flex;align-items:stretch;height:30px;width:min(360px,100%);min-width:220px;max-width:360px;flex:1 1 300px}
.stock-quick-search input{width:100%;min-width:0;height:30px;padding:4px 9px;border:1px solid #d0d5dd;border-right:0;border-radius:4px 0 0 4px;background:#fff;color:#475467;font-size:11px;outline:none}
.stock-quick-search input:focus{border-color:#7aa7c7;box-shadow:inset 0 0 0 1px #7aa7c7}
.stock-quick-search button{width:32px;min-width:32px;height:30px;border:1px solid #347fa7;border-radius:0 4px 4px 0;background:#347fa7;color:#fff;display:grid;place-items:center}
.stock-tool-btn{width:30px;height:30px;min-width:30px;display:inline-flex;align-items:center;justify-content:center;padding:0;border:1px solid #d5dce4;border-radius:4px;background:#fff;color:#64748b;text-decoration:none;font-size:13px;line-height:1}
.stock-tool-btn:hover,.stock-tool-btn:focus{background:#f1f6fa;border-color:#b9cbd8;color:#315f9f}
.stock-tool-btn.primary{background:#347fa7;border-color:#347fa7;color:#fff}
.stock-tool-btn-replenish{color:#347fa7;border-color:#b9d5e4;background:#f7fbfd}
.stock-tool-btn:disabled{opacity:.4;cursor:not-allowed;background:#f8fafc;color:#98a2b3}
.stock-tool-btn.primary:hover{background:#286b91;border-color:#286b91;color:#fff}
.stock-list-meta{min-height:36px;padding:7px 10px;display:flex!important;align-items:center;justify-content:space-between;gap:10px;border-bottom:1px solid #e4e7ec;background:#fff;color:#667085;font-size:11px;line-height:1.4}
.stock-list-meta-left,.stock-list-meta-right{display:flex;align-items:center;gap:4px;white-space:nowrap}
.stock-list-meta strong{color:#344054;font-weight:700;font-variant-numeric:tabular-nums}
@media(max-width:520px){.stock-list-meta{align-items:flex-start;flex-direction:column;gap:3px}}
.stock-register-panel .data-table{width:100%;font-size:11px}
.stock-register-panel .data-table thead th{height:32px;padding:6px 8px;background:#f8f9fa;color:#46515d;font-size:10px;font-weight:700;white-space:nowrap;border-top:0;border-bottom:1px solid #dfe4e9}
.stock-register-panel .data-table tbody td{padding:6px 8px;height:36px;border-bottom:1px solid #eef0f2;vertical-align:middle;color:#667085}
.stock-register-panel .data-table tbody tr:nth-child(even){background:#fafafa}
.stock-register-panel .data-table tbody tr:hover{background:#f1f7fb}
.stock-register-panel .data-table tbody tr:last-child td{border-bottom:0}
.stock-register-panel .table-actions{display:inline-flex;align-items:center;justify-content:flex-end;gap:4px}
.stock-register-panel .table-actions a,.stock-register-panel .asset-action-button{width:27px;height:27px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #dfe5ec;border-radius:4px;background:#fff;color:#64748b;text-decoration:none;font-size:12px}
.stock-register-panel .table-actions a[title*="View"],.stock-register-panel .table-actions a[aria-label="View"]{color:#3478b8}
.stock-register-panel .table-actions a[title*="Edit"],.stock-register-panel .table-actions a[aria-label="Edit"]{color:#b7791f}
.stock-register-panel .table-actions button{color:#c2413b}
.stock-register-panel .table-actions a:hover,.stock-register-panel .asset-action-button:hover{background:#f4f7fa;border-color:#cbd5df}
@media(max-width:850px){.stock-overview{grid-template-columns:1fr}.stock-quick-stats{grid-template-columns:repeat(3,minmax(0,1fr));grid-template-rows:auto}.stock-quick-stat{align-items:flex-start;flex-direction:column;gap:2px}}
@media(max-width:600px){.stock-register-toolbar{align-items:stretch}.stock-toolbar-left{width:100%;min-width:0;flex-basis:100%}.stock-toolbar-right{width:100%;justify-content:flex-start;margin-left:0}.stock-quick-search{width:100%;min-width:100%;max-width:none;flex-basis:100%}.stock-quick-stats{grid-template-columns:1fr}.stock-quick-stat{flex-direction:row;align-items:center}}


/* Assignments register: use the same compact table-first visual language as Assets. */
.assignment-overview{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin:12px 0 14px}
.assignment-overview-card{min-height:58px;padding:9px 12px;border:1px solid #e2e7ed;border-radius:5px;background:#fff;display:flex;align-items:center;justify-content:space-between;gap:10px}
.assignment-overview-card span{font-size:11px;color:#667085;font-weight:500}
.assignment-overview-card strong{font-size:19px;line-height:1.1;color:#344054;font-weight:700;font-variant-numeric:tabular-nums}
.assignment-overview-card.pending strong{color:#b7791f}.assignment-overview-card.issued strong{color:#347fa7}.assignment-overview-card.overdue strong{color:#c2413b}.assignment-overview-card.returned strong{color:#21845b}
.assignment-register-toolbar{min-height:43px;padding:6px 9px;display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:1px solid #e6e9ed;background:#fff}
.assignment-toolbar-left,.assignment-toolbar-right{display:flex;align-items:center;gap:5px;min-width:0}
.assignment-quick-search{display:flex;align-items:center;width:min(360px,38vw)}
.assignment-quick-search input{height:29px;min-width:80px;width:100%;padding:4px 8px;border:1px solid #d0d5dd;border-right:0;border-radius:3px 0 0 3px;font-size:11px;outline:none}
.assignment-quick-search input:focus{border-color:#6c9fbd;box-shadow:0 0 0 2px rgba(52,127,167,.08)}
.assignment-quick-search button{height:29px;width:29px;border:1px solid #347fa7;border-radius:0 3px 3px 0;background:#347fa7;color:#fff;display:grid;place-items:center;padding:0}
.assignment-tool-btn{height:29px;min-width:29px;padding:0 8px;border:1px solid #d0d5dd;border-radius:3px;background:#fff;color:#526273;display:inline-flex;align-items:center;justify-content:center;font-size:11px;text-decoration:none;white-space:nowrap}
.assignment-tool-btn:hover{background:#f5f7fa;color:#245b88;border-color:#aab7c4}.assignment-tool-btn.primary{background:#347fa7;border-color:#347fa7;color:#fff}
.assignment-filter-collapse{padding:8px 10px;border-bottom:1px solid #e6e9ed;background:#fbfcfd}
.assignment-filter-grid{display:grid;grid-template-columns:repeat(3,minmax(140px,1fr)) auto;gap:8px;align-items:end}
.assignment-filter-grid label{display:block;margin:0 0 3px;font-size:10px;font-weight:600;color:#667085}
.assignment-filter-grid .form-select{height:29px;min-height:29px;padding-top:3px;padding-bottom:3px;font-size:11px;border-radius:3px}
.assignment-register .status-pill{display:inline-flex;align-items:center;padding:2px 6px;border-radius:3px;font-size:10px;font-weight:600;text-transform:capitalize;background:#eef2f6;color:#475467}
.assignment-register .status-pill.issued{background:#eaf3fb;color:#27658e}.assignment-register .status-pill.returned{background:#e8f5ee;color:#21734d}.assignment-register .status-pill.pending_approval,.assignment-register .status-pill.pending{background:#fff4df;color:#986515}.assignment-register .status-pill.danger,.assignment-register .status-pill.overdue{background:#fdeceb;color:#b42318}
.assignment-register .data-table{width:100%;min-width:900px;margin:0;border-collapse:separate;border-spacing:0;font-size:11px}
.assignment-register .data-table thead th{height:31px;padding:6px 7px;border-top:0;border-bottom:1px solid #dfe4e9;background:#f8f9fa;color:#46515d;font-size:10px;font-weight:700;white-space:nowrap;vertical-align:middle}
.assignment-register .data-table tbody tr{height:33px}.assignment-register .data-table tbody tr:nth-child(even){background:#fafafa}.assignment-register .data-table tbody tr:hover{background:#f1f7fb}
.assignment-register .data-table tbody td{height:33px;padding:3px 7px;border-bottom:1px solid #eef0f2;color:#667085;vertical-align:middle;white-space:nowrap}
.assignment-register .table-actions{display:flex;align-items:center;justify-content:flex-end;gap:3px}
.assignment-register .table-actions a,.assignment-register .table-actions button{width:25px;height:25px;display:inline-grid;place-items:center;padding:0;border:1px solid #dfe5ec;border-radius:4px;background:#fff;color:#3478b8;text-decoration:none;font-size:11px}
.assignment-register .table-actions a[title*="Edit"],.assignment-register .table-actions a[aria-label="Edit"]{color:#b7791f}.assignment-register .table-actions button{color:#c2413b}
.assignment-register .table-actions a:hover,.assignment-register .table-actions button:hover{background:#f4f7fa;border-color:#cbd5df}
.assignment-register .pagination-wrap{display:flex;justify-content:flex-end;padding:6px 9px;border-top:1px solid #eef0f2}
.assignment-register .pagination{margin:0;gap:2px}.assignment-register .pagination .page-link{min-width:25px;height:25px;padding:4px 7px;border:1px solid #e0e4e9;border-radius:3px!important;font-size:10px;line-height:15px;text-align:center;color:#475467}
.assignment-register .pagination .page-item.active .page-link{background:#347fa7;border-color:#347fa7;color:#fff}
@media(max-width:850px){.assignment-overview{grid-template-columns:repeat(2,minmax(0,1fr))}.assignment-register-toolbar{align-items:flex-start;flex-direction:column}.assignment-toolbar-left,.assignment-toolbar-right{width:100%;flex-wrap:wrap}.assignment-quick-search{width:min(100%,360px)}.assignment-filter-grid{grid-template-columns:repeat(2,minmax(130px,1fr))}}
@media(max-width:540px){.assignment-overview{grid-template-columns:1fr 1fr}.assignment-filter-grid{grid-template-columns:1fr}.assignment-toolbar-right{justify-content:flex-end}}

</style>
@endpush
@section('content')
@if(!in_array($resource,['consumables','assignments'],true))
<div class="page-heading asset-register-heading"><div><div class="eyebrow">INVENTORY REGISTER</div><h1>{{ $d['title'] }}</h1><p>Manage records, review details, and keep information up to date.</p></div><div class="heading-actions">@if($resource==='consumables')
@elseif(in_array($resource,['assets','consumables']) && auth()->user()->hasPermission($d['permission'].'.manage'))<a class="btn btn-light border" href="{{ route('imports.template',$resource) }}"><i class="bi bi-file-earmark-arrow-down me-1"></i>CSV template</a><button class="btn btn-light border" data-bs-toggle="modal" data-bs-target="#importModal"><i class="bi bi-upload me-1"></i>Import Excel / CSV</button>@endif
@if($resource==='assets' && auth()->user()->hasPermission('reports.export'))
<a class="btn btn-light border" href="{{ route('reports.assets.csv') }}"><i class="bi bi-filetype-csv me-1"></i>Export CSV</a>
<a class="btn btn-light border" href="{{ route('reports.export',['assets','xlsx']) }}"><i class="bi bi-file-earmark-excel me-1"></i>Export Excel</a>
@endif
@if($resource==='consumables')
<a class="btn btn-light border" href="{{ route('stock-ledger.index') }}"><i class="bi bi-journal-text me-1"></i>Inventory ledger</a>
<a class="btn btn-light border" href="{{ route('stock-counts.index') }}"><i class="bi bi-clipboard2-check me-1"></i>Stock counts</a>
@if(auth()->user()->hasPermission('reports.export'))<a class="btn btn-light border" href="{{ route('reports.stock.csv') }}"><i class="bi bi-filetype-csv me-1"></i>Export stock</a>@endif
@if(auth()->user()->hasPermission('consumables.manage') && auth()->user()->hasPermission('procurements.manage'))<form method="POST" action="{{ route('stock.replenishment.create') }}" class="d-inline">@csrf<button class="btn btn-outline-primary" type="submit" @disabled(($stockSummary['low']??0)+($stockSummary['out']??0)===0)><i class="bi bi-cart-plus me-1"></i>Draft replenishment PR</button></form>@endif
@endif
@if(auth()->user()->hasPermission($d['permission'].'.manage') && empty($d['workflow_only']))<a href="{{ route('inventory.create',$resource) }}" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Add {{ strtolower($d['singular']) }}</a>@endif</div></div>
@endif
@if($resource==='assignments')
<div class="assignment-overview">
 <div class="assignment-overview-card pending"><span>Pending Approval</span><strong>{{ \App\Models\Assignment::where('status','pending_approval')->count() }}</strong></div>
 <div class="assignment-overview-card issued"><span>Currently Issued</span><strong>{{ \App\Models\Assignment::where('status','issued')->count() }}</strong></div>
 <div class="assignment-overview-card overdue"><span>Overdue Returns</span><strong>{{ \App\Models\Assignment::where('status','issued')->whereNotNull('due_date')->whereDate('due_date','<',today())->count() }}</strong></div>
 <div class="assignment-overview-card returned"><span>Returned</span><strong>{{ \App\Models\Assignment::where('status','returned')->count() }}</strong></div>
</div>
@endif
@if($resource==='consumables' && $stockSummary)
<div class="stock-overview mb-3">
 <div class="stock-value-card">
  <div class="stock-value-heading"><span class="stock-value-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span><span class="stock-overview-label">Total Inventory Value</span></div>
  <div class="stock-value-amount"><span class="stock-value-currency">₱</span><strong>{{ number_format((float) $stockSummary['value'], 2) }}</strong></div>
  <small class="stock-value-note"><i class="bi bi-info-circle" aria-hidden="true"></i>Estimated current stock valuation</small>
 </div>
 <div class="stock-quick-stats">
  <div class="stock-quick-stat"><span>Stock Items</span><strong>{{ number_format($stockSummary['items']) }}</strong></div>
  <div class="stock-quick-stat warning"><span>Low Stock</span><strong>{{ number_format($stockSummary['low']) }}</strong></div>
  <div class="stock-quick-stat danger"><span>Out of Stock</span><strong>{{ number_format($stockSummary['out']) }}</strong></div>
 </div>
</div>
@if($stockSummary['expiring']>0)<div class="alert alert-warning py-2 mb-3 small"><i class="bi bi-calendar2-exclamation me-1"></i>{{ $stockSummary['expiring'] }} stock batch(es) expire within 30 days or are already expired. Review the item batch records.</div>@endif
@endif
<div class="panel asset-register-panel {{ $resource==='consumables' ? 'stock-register-panel' : '' }} {{ $resource==='assignments' ? 'assignment-register' : '' }}">@if($resource==='assets')
<div class="px-3 pt-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
 <div class="small text-secondary"><i class="bi bi-info-circle me-1"></i>Select assets for bulk actions, QR/barcode labels, or batch updates.</div>
 <button type="button" class="btn btn-outline-primary btn-sm" id="bulkPrintLabels" disabled><i class="bi bi-printer me-1"></i>Print selected labels <span id="bulkPrintCount" class="ms-1">(0)</span></button>
 @if(auth()->user()->hasPermission('assets.manage'))
 <button type="button" class="btn btn-outline-secondary btn-sm" id="openBulkUpdate" data-bs-toggle="modal" data-bs-target="#bulkUpdateModal" disabled><i class="bi bi-pencil-square me-1"></i>Bulk update selected</button>
 @endif
</div>
@endif
<style>
.asset-filter-panel{padding:12px 16px;border-bottom:1px solid #e9edf2;background:#fff}
.asset-filter-panel .form-label{font-size:.78rem;font-weight:600;color:#475569;margin-bottom:4px}
.asset-filter-panel .form-control,.asset-filter-panel .form-select{height:36px;min-height:36px;font-size:.84rem;border:1px solid #d8dee6;border-radius:5px;box-shadow:none}
.asset-filter-panel .form-control:focus,.asset-filter-panel .form-select:focus{border-color:#7aa7c7;box-shadow:0 0 0 .12rem rgba(39,125,180,.10)}
.asset-filter-panel .filter-row{display:grid;grid-template-columns:minmax(240px,2fr) repeat(3,minmax(145px,1fr)) auto;gap:10px;align-items:end}
.asset-filter-panel .filter-actions{display:flex;gap:6px}
.asset-filter-panel .filter-actions .btn{height:36px;padding:0 13px;font-size:.82rem;white-space:nowrap}
.asset-device-cell{width:58px;min-width:58px}
.asset-device-thumb{width:42px;height:42px;object-fit:contain;border:1px solid #e8edf2;border-radius:6px;background:#fff;padding:2px;display:block}
.asset-device-placeholder{width:42px;height:42px;display:grid;place-items:center;border:1px solid #e8edf2;border-radius:6px;background:#f7f9fb;color:#9aa6b5;font-size:17px}
.asset-action-form{display:inline-flex;margin:0}
.asset-action-button{width:25px;height:25px;display:inline-grid;place-items:center;padding:0;border:1px solid #e5e9ef;border-radius:5px;background:#fff;color:#7b8795}
.asset-action-button:hover{background:#f0f4f9;border-color:#cfdae8;color:#315f9f}
.asset-action-button.archive:hover{background:#fff3f1;border-color:#f0d1ca;color:#ae5143}
.asset-register-panel .data-table .asset-device-cell{padding-left:10px;padding-right:10px}
@media(max-width:900px){.asset-filter-panel .filter-row{grid-template-columns:repeat(2,minmax(0,1fr))}.asset-filter-panel .search-field{grid-column:1/-1}}
@media(max-width:560px){.asset-filter-panel .filter-row{grid-template-columns:1fr}.asset-filter-panel .search-field{grid-column:auto}.asset-filter-panel .filter-actions{justify-content:flex-start}}
</style>
@if($resource==='consumables')
<div class="stock-register-toolbar">
 <div class="stock-toolbar-left">
  <form method="GET" action="{{ route('inventory.index',$resource) }}" class="stock-quick-search" role="search">
   <input type="search" name="q" value="{{ request('q') }}" placeholder="Search item, SKU, category..." aria-label="Search stock items">
   <button type="submit" title="Search" aria-label="Search"><i class="bi bi-search"></i></button>
  </form>
 </div>
 <div class="stock-toolbar-right">
  <button type="button" class="stock-tool-btn" data-bs-toggle="collapse" data-bs-target="#stockAdvancedFilters" aria-expanded="false" title="Filter stock" aria-label="Filter stock"><i class="bi bi-funnel"></i></button>
  @if(auth()->user()->hasPermission($d['permission'].'.manage'))
   <a href="{{ route('inventory.create',$resource) }}" class="stock-tool-btn primary" title="Add inventory item" aria-label="Add inventory item"><i class="bi bi-plus-lg"></i></a>
   <a href="{{ route('imports.template',$resource) }}" class="stock-tool-btn" title="Download CSV template" aria-label="Download CSV template"><i class="bi bi-file-earmark-arrow-down"></i></a>
   <button type="button" class="stock-tool-btn" data-bs-toggle="modal" data-bs-target="#importModal" title="Import Excel or CSV" aria-label="Import Excel or CSV"><i class="bi bi-upload"></i></button>
  @endif
  <a href="{{ route('stock-ledger.index') }}" class="stock-tool-btn" title="Inventory ledger" aria-label="Inventory ledger"><i class="bi bi-journal-text"></i></a>
  <a href="{{ route('stock-counts.index') }}" class="stock-tool-btn" title="Stock counts" aria-label="Stock counts"><i class="bi bi-clipboard2-check"></i></a>
  @if(auth()->user()->hasPermission('reports.export'))<a href="{{ route('reports.stock.csv') }}" class="stock-tool-btn" title="Export stock CSV" aria-label="Export stock CSV"><i class="bi bi-filetype-csv"></i></a>@endif
  @if(auth()->user()->hasPermission('consumables.manage') && auth()->user()->hasPermission('procurements.manage'))
   <form method="POST" action="{{ route('stock.replenishment.create') }}" class="d-inline m-0">@csrf<button class="stock-tool-btn stock-tool-btn-replenish" type="submit" title="Draft replenishment purchase request" aria-label="Draft replenishment purchase request" @disabled(($stockSummary['low']??0)+($stockSummary['out']??0)===0)><i class="bi bi-cart-plus"></i></button></form>
  @endif
  <a href="{{ route('inventory.index',$resource) }}" class="stock-tool-btn" title="Refresh stock register" aria-label="Refresh stock register"><i class="bi bi-arrow-clockwise"></i></a>
 </div>
</div>
@endif
@if($resource==='assignments')
<div class="assignment-register-toolbar">
 <div class="assignment-toolbar-left">
  <form method="GET" action="{{ route('inventory.index',$resource) }}" class="assignment-quick-search" role="search">
   <input type="search" name="q" value="{{ request('q') }}" placeholder="Search reference, asset tag, or employee..." aria-label="Search assignments">
   <button type="submit" title="Search assignments" aria-label="Search"><i class="bi bi-search"></i></button>
  </form>
 </div>
 <div class="assignment-toolbar-right">
  <button type="button" class="assignment-tool-btn" data-bs-toggle="collapse" data-bs-target="#assignmentAdvancedFilters" aria-expanded="{{ request()->hasAny(array_column($filters,'name')) ? 'true' : 'false' }}" title="Show filters" aria-label="Show filters"><i class="bi bi-funnel"></i></button>
  @if(auth()->user()->hasPermission($d['permission'].'.manage') && empty($d['workflow_only']))
   <a href="{{ route('inventory.create',$resource) }}" class="assignment-tool-btn primary" title="Create assignment" aria-label="Create assignment"><i class="bi bi-plus-lg"></i></a>
  @endif
  <a href="{{ route('inventory.index',$resource) }}" class="assignment-tool-btn" title="Refresh assignments" aria-label="Refresh assignments"><i class="bi bi-arrow-clockwise"></i></a>
 </div>
</div>
@endif
<div class="asset-filter-panel {{ $resource==='consumables' ? 'collapse' : '' }} {{ $resource==='assignments' ? 'collapse assignment-filter-collapse '.(request()->hasAny(array_column($filters,'name')) ? 'show' : '') : '' }}" id="{{ $resource==='assignments' ? 'assignmentAdvancedFilters' : 'stockAdvancedFilters' }}">
 <form method="GET" action="{{ route('inventory.index',$resource) }}" class="mb-0">
 @if($resource==='assets')
  <div class="filter-row">
   <div class="search-field"><label class="form-label" for="assetSearch">Search assets</label><input id="assetSearch" class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Asset name, asset tag, or serial number"></div>
   @foreach($filters as $filter)
    <div><label class="form-label" for="filter_{{ $filter['name'] }}">{{ $filter['label'] }}</label><select id="filter_{{ $filter['name'] }}" class="form-select" name="{{ $filter['name'] }}"><option value="">All {{ strtolower($filter['label']) }}</option>@foreach($filterOptions[$filter['name']]??[] as $value=>$label)<option value="{{ $value }}" @selected((string)request($filter['name'])===(string)$value)>{{ $label }}</option>@endforeach</select></div>
   @endforeach
   <div class="filter-actions"><button class="btn btn-primary" type="submit">Search</button><a href="{{ route('inventory.index',$resource) }}" class="btn btn-outline-secondary">Reset</a></div>
  </div>
 @elseif($resource==='assignments')
  <div class="assignment-filter-grid">
   @foreach($filters as $filter)<div><label for="assignment_filter_{{ $filter['name'] }}">{{ $filter['label'] }}</label><select id="assignment_filter_{{ $filter['name'] }}" class="form-select" name="{{ $filter['name'] }}"><option value="">All {{ strtolower($filter['label']) }}</option>@foreach($filterOptions[$filter['name']]??[] as $value=>$label)<option value="{{ $value }}" @selected((string)request($filter['name'])===(string)$value)>{{ $label }}</option>@endforeach</select></div>@endforeach
   <div class="d-flex gap-1"><button class="assignment-tool-btn primary" type="submit" title="Apply filters"><i class="bi bi-check-lg"></i></button><a href="{{ route('inventory.index',$resource) }}" class="assignment-tool-btn" title="Clear filters"><i class="bi bi-x-lg"></i></a></div>
  </div>
 @else
  <div class="filter-row" style="grid-template-columns:minmax(240px,2fr) repeat(2,minmax(145px,1fr)) auto">
   <div class="search-field"><label class="form-label" for="assetSearch">Search</label><input id="assetSearch" class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Search {{ strtolower($d['title']) }}"></div>
   @foreach($filters as $filter)<div><label class="form-label" for="filter_{{ $filter['name'] }}">{{ $filter['label'] }}</label><select id="filter_{{ $filter['name'] }}" class="form-select" name="{{ $filter['name'] }}"><option value="">All {{ strtolower($filter['label']) }}</option>@foreach($filterOptions[$filter['name']]??[] as $value=>$label)<option value="{{ $value }}" @selected((string)request($filter['name'])===(string)$value)>{{ $label }}</option>@endforeach</select></div>@endforeach
   <div class="filter-actions"><button class="btn btn-primary" type="submit">Search</button><a href="{{ route('inventory.index',$resource) }}" class="btn btn-outline-secondary">Reset</a></div>
  </div>
 @endif
 </form>
</div>
<div class="stock-list-meta {{ $resource==='assignments' ? 'assignment-list-meta' : '' }}" aria-live="polite">
 <div class="stock-list-meta-left">Showing <strong>{{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }}</strong> of <strong>{{ $records->total() }}</strong> {{ $resource==='assignments' ? 'assignments' : 'records' }}</div>
 @if($resource==='consumables')<div class="stock-list-meta-right">Inventory items <strong>{{ $records->total() }}</strong></div>@endif
</div>
<div class="table-responsive"><table class="table data-table align-middle mb-0"><thead><tr>@if($resource==='assets')<th style="width:42px"><input class="form-check-input" type="checkbox" id="selectAllAssets" aria-label="Select all assets on this page"></th><th class="asset-device-cell">Device</th>@endif @foreach($d['columns'] as $column)<th>@if($resource==='assets')
<a class="text-decoration-none text-reset" href="{{ route('inventory.index', array_merge(['resource'=>$resource], request()->except('page'), ['sort'=>$column['key'], 'direction'=>(request('sort')===$column['key'] && request('direction')==='asc')?'desc':'asc'])) }}">{{ $column['label'] }} @if(request('sort')===$column['key'])<i class="bi bi-arrow-{{ request('direction')==='asc'?'up':'down' }}"></i>@endif</a>
@else{{ $column['label'] }}@endif</th>@endforeach<th class="text-end">Actions</th></tr></thead><tbody>
@forelse($records as $record)<tr>
@if($resource==='assets')
<td><input class="form-check-input asset-label-select" type="checkbox" value="{{ $record->id }}" aria-label="Select {{ $record->asset_tag }}"></td>
<td class="asset-device-cell">
 @if(filled($record->image_path))
  <img class="asset-device-thumb" src="{{ route('assets.image',$record->id) }}" alt="{{ $record->name }} device image" loading="lazy" onerror="this.hidden=true;this.nextElementSibling.hidden=false">
  <span class="asset-device-placeholder" hidden aria-label="No image"><i class="bi bi-laptop"></i></span>
 @else
  <span class="asset-device-placeholder" aria-label="No image"><i class="bi bi-laptop"></i></span>
 @endif
</td>
@endif
@foreach($d['columns'] as $column)@php($value=data_get($record,$column['key']))@if($resource==='consumables' && $column['key']==='available_quantity')@php($reserved=$record->reservations->sum(fn($r)=>max(0,(int)$r->quantity-(int)$r->fulfilled_qty)))@php($value=max(0,(int)$record->quantity_on_hand-$reserved))@elseif($resource==='consumables' && $column['key']==='stock_value')@php($trackedBatchQty=$record->batches->sum('quantity_on_hand'))@php($batchValue=$record->batches->sum(fn($batch)=>(int)$batch->quantity_on_hand*(float)$batch->unit_cost))@php($value=$batchValue+max(0,(int)$record->quantity_on_hand-(int)$trackedBatchQty)*(float)$record->unit_cost)@elseif($resource==='consumables' && $column['key']==='stock_status')@php($threshold=max((int)$record->reorder_level,(int)$record->min_level))@php($reserved=$record->reservations->sum(fn($r)=>max(0,(int)$r->quantity-(int)$r->fulfilled_qty)))@php($available=max(0,(int)$record->quantity_on_hand-$reserved))@php($value=$available<=0?'out_of_stock':($available<=$threshold?'low_stock':'in_stock'))@elseif($column['key']==='category_id')@php($value=$record->category?->name ?? $value)@elseif($column['key']==='department_id')@php($value=$record->department?->name ?? $value)@elseif($column['key']==='asset_id')@php($value=$record->asset?->asset_tag ?? $value)@elseif($column['key']==='employee_id')@php($value=$record->employee?->full_name ?? $value)@elseif($column['key']==='to_employee_id')@php($value=$record->toEmployee?->full_name ?? $value)@elseif($column['key']==='location_id')@php($value=$record->location?->name ?? $value)@elseif($column['key']==='supplier_id')@php($value=$record->supplier?->company_name ?? $value)@elseif($column['key']==='parent_id')@php($value=$record->parent?->name ?? '—')@elseif($column['key']==='head_employee_id')@php($value=$record->headEmployee?->full_name ?? $record->head_name ?? '—')@endif<td>
@if(in_array($column['key'],['name','asset_tag','ticket_no','reference_no','company_name','software_name','request_no','audit_no','employee_no'],true))<a class="table-primary" href="{{ route('inventory.show',[$resource,$record->id]) }}">{{ $value ?: '—' }}</a>
@elseif($resource==='consumables' && $column['key']==='stock_status')<span class="status-pill {{ $value==='out_of_stock'?'danger':($value==='low_stock'?'warning':'success') }}">{{ str_replace('_',' ',$value) }}</span>
@elseif($resource==='consumables' && in_array($column['key'],['quantity_on_hand','available_quantity','reorder_level'],true))<span class="mono fw-semibold">{{ number_format((int)$value) }}</span>
@elseif($resource==='consumables' && in_array($column['key'],['unit_cost','stock_value'],true))<span class="mono">{{ number_format((float)$value,2) }}</span>
@elseif($resource==='assignments' && $column['key']==='status' && $record->status==='issued' && $record->due_date && $record->due_date->lt(today()))<span class="status-pill danger">Overdue</span>
@elseif(!empty($column['badge']))<span class="status-pill {{ strtolower((string)$value) }}">{{ is_bool($value)?($value?'Yes':'No'):($resource==='assets' && $column['key']==='status' ? ($filterOptions['status'][$value] ?? str_replace('_',' ',$value ?? '—')) : str_replace('_',' ',$value ?? '—')) }}</span>
@elseif(in_array($column['key'],['purchase_date','expires_at','issued_at','due_date','scheduled_at','created_at'],true)){{ $value ? \Illuminate\Support\Carbon::parse($value)->format(config('app.date_format', 'd M Y')) : '—' }}
@elseif($column['key']==='condition' && $resource==='assets'){{ $filterOptions['condition'][$value] ?? $value ?? '—' }}
@elseif($column['key']==='full_name'){{ $record->full_name }}
@else{{ $value===null || $value===''?'—':$value }}@endif
</td>@endforeach
<td class="text-end"><div class="table-actions">
 <a href="{{ route('inventory.show',[$resource,$record->id]) }}" title="View" aria-label="View"><i class="bi bi-eye"></i></a>
 @if(auth()->user()->hasPermission($d['permission'].'.manage') && empty($d['workflow_only']))
  @if($resource==='assets')<a href="{{ route('inventory.clone',[$resource,$record->id]) }}" title="Clone asset" aria-label="Clone asset"><i class="bi bi-copy"></i></a>@endif
  <a href="{{ route('inventory.edit',[$resource,$record->id]) }}" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
  <form class="asset-action-form" method="POST" action="{{ route('inventory.destroy',[$resource,$record->id]) }}" onsubmit="return confirm('Delete / archive this {{ strtolower($d['singular']) }} record? It will be removed from the active register.')">
   @csrf @method('DELETE')
   <button type="submit" class="asset-action-button archive" title="Delete / Archive" aria-label="Delete / Archive"><i class="bi bi-trash"></i></button>
  </form>
 @endif
</div></td></tr>
@empty<tr><td colspan="{{ count($d['columns'])+1+($resource==='assets'?2:0) }}"><div class="empty-state"><i class="bi bi-inbox"></i><strong>No records found</strong><span>Try changing your search or add a new record.</span></div></td></tr>@endforelse
</tbody></table></div><div class="pagination-wrap">{{ $records->links() }}</div></div>
@if(in_array($resource,['assets','consumables']) && auth()->user()->hasPermission($d['permission'].'.manage'))<div class="modal fade" id="importModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" enctype="multipart/form-data" action="{{ route('imports.store',$resource) }}">@csrf<div class="modal-header"><h5 class="modal-title">Import {{ $d['title'] }}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="small text-secondary">Download the CSV template first. CSV and XLSX files are accepted. Imports are limited to {{ number_format($importMaxRows) }} records and {{ $importMaxSizeMb }} MB per file. Each upload is validated before records are saved.</p><label class="form-label">Excel or CSV file</label><input class="form-control" type="file" name="file" accept=".csv,.txt,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required></div><div class="modal-footer"><button class="btn btn-light border" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Import records</button></div></form></div></div>@endif
@if($resource==='assets' && auth()->user()->hasPermission('assets.manage'))
<div class="modal fade" id="bulkUpdateModal" tabindex="-1" aria-labelledby="bulkUpdateModalLabel" aria-hidden="true">
 <div class="modal-dialog modal-lg"><form class="modal-content" method="POST" action="{{ route('assets.bulk-update') }}" id="bulkUpdateForm">
  @csrf
  <div class="modal-header"><h5 class="modal-title" id="bulkUpdateModalLabel">Bulk update selected assets</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
   <p class="small text-secondary">Only fields with a selected value will be changed. Leave other fields blank to retain their current values. A history and audit entry will be recorded for every asset.</p>
   <div id="bulkUpdateSelectedIds"></div>
   <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">Do not change</option>@foreach(['available'=>'Available','defective'=>'Defective'] as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Condition</label><select class="form-select" name="condition"><option value="">Do not change</option>@foreach(['New','Good','Fair','Defective'] as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Department</label><select class="form-select" name="department_id"><option value="">Do not change</option>@foreach(\App\Models\Department::orderBy('name')->get() as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Location</label><select class="form-select" name="location_id"><option value="">Do not change</option>@foreach(\App\Models\Location::orderBy('name')->get() as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Supplier</label><select class="form-select" name="supplier_id"><option value="">Do not change</option>@foreach(\App\Models\Supplier::orderBy('company_name')->get() as $item)<option value="{{ $item->id }}">{{ $item->company_name }}</option>@endforeach</select></div>
   </div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" onclick="return confirm('Apply the selected changes to all selected assets?')">Update selected assets</button></div>
 </form></div>
</div>
@endif
<div class="modal fade" id="bulkAssignModal" tabindex="-1"><div class="modal-dialog modal-lg"><form class="modal-content" method="POST" action="{{ route('assets.bulk-assign') }}">@csrf<div class="modal-header"><h5 class="modal-title">Submit multiple asset assignments</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="small text-secondary">Selected assets are submitted as individual pending approval records. Assets remain available until approved.</p><div id="bulkAssignSelectedIds"></div><div class="row g-3"><div class="col-md-6"><label class="form-label">Employee / custodian</label><select class="form-select" name="employee_id" required><option value="">Select employee</option>@foreach(\App\Models\Employee::where('status','active')->orderBy('last_name')->get() as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no }} — {{ $employee->full_name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Assignment type</label><select class="form-select" name="assignment_type" required><option value="permanent">Permanent</option><option value="temporary">Temporary</option></select></div><div class="col-md-6"><label class="form-label">Department</label><select class="form-select" name="department_id"><option value="">Use employee department</option>@foreach(\App\Models\Department::orderBy('name')->get() as $dept)<option value="{{ $dept->id }}">{{ $dept->name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Office / location</label><select class="form-select" name="location_id"><option value="">Keep each asset location</option>@foreach(\App\Models\Location::orderBy('name')->get() as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Issue date</label><input type="date" class="form-control" name="issued_at" value="{{ today()->toDateString() }}" required></div><div class="col-md-6"><label class="form-label">Due date</label><input type="date" class="form-control" name="due_date"></div><div class="col-12"><label class="form-label">Remarks</label><textarea class="form-control" name="remarks" rows="2"></textarea></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Submit for approval</button></div></form></div></div>
@if($resource==='assets')
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = Array.from(document.querySelectorAll('.asset-label-select'));
    const selectAll = document.getElementById('selectAllAssets');
    const printButton = document.getElementById('bulkPrintLabels');
    const countLabel = document.getElementById('bulkPrintCount');
    const bulkUpdateButton = document.getElementById('openBulkUpdate');
    const bulkUpdateIds = document.getElementById('bulkUpdateSelectedIds');
    if (!printButton) return;

    function updateSelection() {
        const selected = checkboxes.filter((checkbox) => checkbox.checked);
        printButton.disabled = selected.length === 0;
        if (bulkUpdateButton) bulkUpdateButton.disabled = selected.length === 0;
        if (bulkUpdateIds) bulkUpdateIds.innerHTML = selected.map((checkbox) => '<input type="hidden" name="ids[]" value="' + String(checkbox.value).replace(/[^0-9]/g, '') + '">').join('');
        countLabel.textContent = '(' + selected.length + ')';
        if (selectAll) {
            selectAll.checked = checkboxes.length > 0 && selected.length === checkboxes.length;
            selectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
        }
    }

    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelection));
    selectAll?.addEventListener('change', function () {
        checkboxes.forEach((checkbox) => { checkbox.checked = selectAll.checked; });
        updateSelection();
    });

    printButton.addEventListener('click', function () {
        const ids = checkboxes.filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value);
        if (!ids.length) return;
        const url = new URL(@json(route('assets.bulk-labels')), window.location.origin);
        ids.forEach((id) => url.searchParams.append('ids[]', id));
        window.open(url.toString(), '_blank', 'noopener');
    });
    updateSelection();
});
</script>
@endpush
@endif
@endsection
