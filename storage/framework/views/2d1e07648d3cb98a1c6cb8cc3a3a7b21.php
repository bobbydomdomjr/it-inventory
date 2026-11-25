<?php $__env->startSection('title', 'IT Assets'); ?>
<?php $__env->startSection('breadcrumb', 'IT Assets'); ?>

<?php $__env->startPush('styles'); ?>
<style>
/* Compact, table-first asset register. Scoped to this view only. */
.asset-list-page{font-size:12px;color:#344054;margin:-15px -15px 0}
.asset-list-panel{background:#fff;border:1px solid #e4e7ec;border-radius:5px;box-shadow:0 1px 2px rgba(16,24,40,.035);overflow:hidden}
.asset-list-toolbar{min-height:43px;padding:6px 9px;display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:1px solid #e6e9ed;background:#fff}
.asset-toolbar-left,.asset-toolbar-right{display:flex;align-items:center;gap:5px;min-width:0}
.asset-toolbar-select{height:29px;min-width:155px;width:155px;padding:3px 25px 3px 8px;border:1px solid #d0d5dd;border-radius:3px;background-color:#fff;font-size:11px;color:#475467}
.asset-mini-btn{height:29px;min-width:29px;padding:0 8px;border:1px solid #d0d5dd;border-radius:3px;background:#fff;color:#526273;display:inline-flex;align-items:center;justify-content:center;font-size:11px;text-decoration:none;white-space:nowrap}
.asset-mini-btn:hover{background:#f5f7fa;color:#245b88;border-color:#aab7c4}
.asset-mini-btn.primary{background:#347fa7;border-color:#347fa7;color:#fff}
.asset-mini-btn.primary:hover{background:#286b91}
.asset-mini-btn.danger{color:#ba4b4b}
.asset-quick-search{display:flex;align-items:center;width:min(280px,30vw)}
.asset-quick-search input{height:29px;min-width:80px;width:100%;padding:4px 8px;border:1px solid #d0d5dd;border-right:0;border-radius:3px 0 0 3px;font-size:11px;outline:none}
.asset-quick-search input:focus{border-color:#6c9fbd;box-shadow:0 0 0 2px rgba(52,127,167,.08)}
.asset-quick-search button{height:29px;width:29px;border:1px solid #347fa7;border-radius:0 3px 3px 0;background:#347fa7;color:#fff;display:grid;place-items:center;padding:0}
.asset-secondary-bar{min-height:38px;padding:5px 10px;display:flex;align-items:center;justify-content:space-between;gap:10px;background:#fff;border-bottom:1px solid #e6e9ed}
.asset-record-meta{font-size:11px;color:#606d7a;white-space:nowrap}
.asset-record-meta strong{font-weight:600;color:#344054}
.asset-page-size{height:25px;margin:0 3px;padding:1px 18px 1px 6px;border:1px solid #d0d5dd;border-radius:3px;background-color:#fff;color:#344054;font-size:11px}
.asset-pagination{margin:0;display:flex;align-items:center;justify-content:flex-end}
.asset-pagination .pagination{margin:0;gap:2px}
.asset-pagination .page-link{min-width:25px;height:25px;padding:4px 7px;border:1px solid #e0e4e9;border-radius:3px!important;background:#fff;color:#475467;font-size:10px;line-height:15px;text-align:center}
.asset-pagination .page-item.active .page-link{background:#347fa7;border-color:#347fa7;color:#fff}
.asset-pagination .page-item.disabled .page-link{color:#a5adb7;background:#fafbfc}
.asset-filter-collapse{padding:8px 10px;border-bottom:1px solid #e6e9ed;background:#fbfcfd}
.asset-filter-grid{display:grid;grid-template-columns:repeat(3,minmax(140px,1fr)) auto;gap:8px;align-items:end}
.asset-filter-grid label{display:block;margin:0 0 3px;font-size:10px;font-weight:600;color:#667085}
.asset-filter-grid .form-select{height:29px;min-height:29px;padding-top:3px;padding-bottom:3px;font-size:11px;border-radius:3px}
.asset-register-table{width:100%;min-width:1040px;margin:0;border-collapse:separate;border-spacing:0;font-size:11px}
.asset-register-table thead th{height:31px;padding:6px 7px;border-top:0;border-bottom:1px solid #dfe4e9;background:#f8f9fa;color:#46515d;font-size:10px;font-weight:700;white-space:nowrap;vertical-align:middle}
.asset-register-table thead th a{color:inherit;text-decoration:none}
.asset-register-table thead th a:hover{color:#347fa7}
.asset-register-table tbody tr{height:33px}
.asset-register-table tbody tr:nth-child(even){background:#fafafa}
.asset-register-table tbody tr:hover{background:#f1f7fb}
.asset-register-table tbody td{height:33px;padding:3px 7px;border-bottom:1px solid #eef0f2;color:#667085;vertical-align:middle;white-space:nowrap}
.asset-register-table .select-col{width:29px;text-align:center}
.asset-register-table .id-col{width:34px;color:#4b5563}
.asset-register-table .dept-col{max-width:115px;overflow:hidden;text-overflow:ellipsis}
.asset-register-table .device-col{width:58px;text-align:center}
.asset-device-image{display:inline-block;width:31px;height:27px;object-fit:contain;background:#fff;border:1px solid #edf0f2;border-radius:2px;padding:1px}
.asset-device-empty{display:inline-grid;width:31px;height:27px;place-items:center;background:#f7f8fa;border:1px solid #edf0f2;border-radius:2px;color:#98a2b3;font-size:14px}
.asset-tag-link{color:#4386a9;text-decoration:none;font-weight:500}
.asset-tag-link:hover{text-decoration:underline}
.asset-mono{font-family:'DM Mono',monospace;font-size:10px;color:#5485a0;max-width:165px;overflow:hidden;text-overflow:ellipsis;display:inline-block;vertical-align:middle}
.asset-muted{color:#9aa3ad!important}
.asset-supplier-dot{display:inline-block;width:7px;height:7px;margin-right:5px;border-radius:1px;background:#5793b4;vertical-align:middle}
.asset-status-pill{display:inline-block;padding:2px 5px;border-radius:2px;background:#f0f3f6;color:#637083;font-size:9px;font-weight:600;text-transform:capitalize}
.asset-check-btn{min-width:57px;height:23px;padding:2px 7px;border:0;border-radius:2px;color:#fff;font-size:10px;font-weight:600;line-height:18px}
.asset-check-btn.checkout{background:#d71d69}
.asset-check-btn.checkin{background:#62599e}
.asset-check-btn:disabled{background:#c8cdd3;color:#fff;cursor:not-allowed}
.asset-row-actions{display:flex;align-items:center;justify-content:flex-end;gap:3px}
.asset-icon-action{width:22px;height:22px;display:inline-grid;place-items:center;padding:0;border:0;border-radius:2px;background:#24afd0;color:#fff;text-decoration:none;font-size:11px}
.asset-icon-action.clone{background:#286b8f}
.asset-icon-action.edit{background:#efa51c}
.asset-icon-action.archive{background:#d94b3b}
.asset-icon-action:hover{filter:brightness(.92);color:#fff}
.asset-row-actions form{margin:0;display:inline-flex}
.asset-empty-state{padding:36px 12px!important;text-align:center!important;color:#929ba6!important}
.asset-empty-state i{font-size:22px;display:block;margin-bottom:4px}
.asset-pagination-bottom{display:flex;justify-content:flex-end;padding:6px 9px;background:#fff}
@media(max-width:900px){.asset-list-toolbar{align-items:flex-start;flex-direction:column}.asset-toolbar-left,.asset-toolbar-right{width:100%;flex-wrap:wrap}.asset-toolbar-right{justify-content:space-between}.asset-quick-search{width:min(100%,360px)}.asset-secondary-bar{align-items:flex-start;flex-direction:column}.asset-pagination{width:100%;justify-content:flex-start}.asset-filter-grid{grid-template-columns:repeat(2,minmax(130px,1fr))}}
@media(max-width:540px){.asset-filter-grid{grid-template-columns:1fr}.asset-toolbar-select{flex:1;min-width:120px}}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="asset-list-page">
 <div class="asset-list-panel">
  <div class="asset-list-toolbar">
   <div class="asset-toolbar-left">
    <select id="assetBulkAction" class="asset-toolbar-select" aria-label="Bulk action">
     <option value="">Bulk actions</option>
     <option value="print">Print QR / barcode labels</option>
     <?php if(auth()->user()->hasPermission('assets.manage')): ?><option value="update">Bulk update selected</option><option value="assign">Assign selected assets</option><?php endif; ?>
    </select>
    <button type="button" id="assetBulkGo" class="asset-mini-btn primary">Go</button>
   </div>

   <div class="asset-toolbar-right">
    <form method="GET" action="<?php echo e(route('inventory.index', 'assets')); ?>" class="asset-quick-search" role="search">
     <?php $__currentLoopData = request()->except(['q','page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <?php if(is_scalar($value)): ?><input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>"><?php endif; ?>
     <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
     <input type="search" name="q" value="<?php echo e(request('q')); ?>" placeholder="Search asset tag, serial, name..." aria-label="Search assets">
     <button type="submit" title="Search assets" aria-label="Search"><i class="bi bi-search"></i></button>
    </form>
    <?php if(request()->filled('q') || request()->hasAny(['status','category_id','location_id'])): ?>
     <a class="asset-mini-btn" href="<?php echo e(route('inventory.index', 'assets')); ?>" title="Clear search and filters" aria-label="Clear search and filters"><i class="bi bi-x-lg"></i></a>
    <?php endif; ?>
    <button type="button" class="asset-mini-btn" data-bs-toggle="collapse" data-bs-target="#assetFilters" aria-expanded="false" aria-controls="assetFilters" title="Show filters" aria-label="Show filters"><i class="bi bi-funnel"></i></button>
    <?php if(auth()->user()->hasPermission('assets.manage')): ?>
     <a class="asset-mini-btn primary" href="<?php echo e(route('inventory.create', 'assets')); ?>" title="Add asset" aria-label="Add asset"><i class="bi bi-plus-lg"></i></a>
     <button type="button" class="asset-mini-btn" data-bs-toggle="modal" data-bs-target="#importAssetsModal" title="Import assets" aria-label="Import assets"><i class="bi bi-upload"></i></button>
    <?php endif; ?>
    <?php if(auth()->user()->hasPermission('reports.export')): ?>
     <a class="asset-mini-btn" href="<?php echo e(route('reports.assets.csv')); ?>" title="Export CSV" aria-label="Export CSV"><i class="bi bi-filetype-csv"></i></a>
     <a class="asset-mini-btn" href="<?php echo e(route('reports.export', ['assets','xlsx'])); ?>" title="Export Excel" aria-label="Export Excel"><i class="bi bi-file-earmark-excel"></i></a>
    <?php endif; ?>
    <a class="asset-mini-btn" href="<?php echo e(route('inventory.index', array_merge(['resource'=>'assets'], request()->except('page')))); ?>" title="Refresh register" aria-label="Refresh"><i class="bi bi-arrow-clockwise"></i></a>
   </div>
  </div>

  <div class="collapse <?php echo e(request()->hasAny(['status','category_id','location_id']) ? 'show' : ''); ?>" id="assetFilters">
   <form method="GET" action="<?php echo e(route('inventory.index', 'assets')); ?>" class="asset-filter-collapse">
    <?php $__currentLoopData = request()->only(['q','per_page','sort','direction']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
     <?php if(is_scalar($value)): ?><input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>"><?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <div class="asset-filter-grid">
     <div><label for="assetStatusFilter">Status</label><select id="assetStatusFilter" class="form-select" name="status"><option value="">All statuses</option><?php $__currentLoopData = $filterOptions['status'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if((string)request('status')===(string)$value): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
     <div><label for="assetCategoryFilter">Category</label><select id="assetCategoryFilter" class="form-select" name="category_id"><option value="">All categories</option><?php $__currentLoopData = $filterOptions['category_id'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if((string)request('category_id')===(string)$value): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
     <div><label for="assetLocationFilter">Location</label><select id="assetLocationFilter" class="form-select" name="location_id"><option value="">All locations</option><?php $__currentLoopData = $filterOptions['location_id'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if((string)request('location_id')===(string)$value): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
     <div class="d-flex gap-1"><button type="submit" class="asset-mini-btn primary">Apply</button><a href="<?php echo e(route('inventory.index', 'assets')); ?>" class="asset-mini-btn">Reset</a></div>
    </div>
   </form>
  </div>

  <div class="asset-secondary-bar">
   <div class="asset-record-meta">
    Showing <strong><?php echo e($records->firstItem() ?? 0); ?></strong> to <strong><?php echo e($records->lastItem() ?? 0); ?></strong> of <strong><?php echo e(number_format($records->total())); ?></strong> records
    <label for="assetPerPage" class="ms-2">Show</label>
    <select id="assetPerPage" class="asset-page-size" aria-label="Rows per page">
     <?php $__currentLoopData = [10,25,50,100]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $size): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($size); ?>" <?php if($perPage===$size): echo 'selected'; endif; ?>><?php echo e($size); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select> rows
   </div>
   <div class="asset-pagination"><?php echo e($records->onEachSide(2)->links('pagination::bootstrap-5')); ?></div>
  </div>

  <div class="table-responsive">
   <table class="asset-register-table">
    <thead><tr>
     <th class="select-col"><input class="form-check-input" type="checkbox" id="selectAllAssets" aria-label="Select all visible assets"></th>
     <?php ($assetHeaders = [
      ['key'=>'id','label'=>'ID','class'=>'id-col'],
      ['key'=>null,'label'=>'Department','class'=>'dept-col'],
      ['key'=>null,'label'=>'Device Image','class'=>'device-col'],
      ['key'=>'asset_tag','label'=>'Asset Tag'],
      ['key'=>'serial_number','label'=>'Serial'],
      ['key'=>'model','label'=>'Model'],
      ['key'=>'category_id','label'=>'Category'],
      ['key'=>'location_id','label'=>'Location'],
      ['key'=>'brand','label'=>'Manufacturer'],
      ['key'=>'supplier_id','label'=>'Supplier'],
     ]); ?>
     <?php $__currentLoopData = $assetHeaders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $header): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <th class="<?php echo e($header['class'] ?? ''); ?>">
       <?php if($header['key']): ?>
        <a href="<?php echo e(route('inventory.index', array_merge(['resource'=>'assets'], request()->except('page'), ['sort'=>$header['key'],'direction'=>(request('sort')===$header['key'] && request('direction')==='asc')?'desc':'asc']))); ?>"><?php echo e($header['label']); ?> <?php if(request('sort')===$header['key']): ?><i class="bi bi-caret-<?php echo e(request('direction')==='asc'?'up':'down'); ?>-fill"></i><?php else: ?><i class="bi bi-arrow-down-up asset-muted"></i><?php endif; ?></a>
       <?php else: ?><?php echo e($header['label']); ?><?php endif; ?>
      </th>
     <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
     <th class="text-center">Checkin/Checkout</th><th class="text-end pe-2">Actions</th>
    </tr></thead>
    <tbody>
     <?php $__empty_1 = true; $__currentLoopData = $records; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <?php ($activeAssignment = $record->assignments->firstWhere('status', 'issued')); ?>
      <tr>
       <td class="select-col"><input class="form-check-input asset-row-select" type="checkbox" value="<?php echo e($record->id); ?>" aria-label="Select asset <?php echo e($record->asset_tag); ?>"></td>
       <td class="id-col"><?php echo e($record->id); ?></td>
       <td class="dept-col" title="<?php echo e($record->department?->name ?? 'No department'); ?>"><?php echo e($record->department?->name ?? '—'); ?></td>
       <td class="device-col">
        <?php if(filled($record->image_path)): ?>
         <img class="asset-device-image" src="<?php echo e(route('assets.image', $record->id)); ?>" alt="<?php echo e($record->name); ?>" loading="lazy" onerror="this.hidden=true;this.nextElementSibling.hidden=false">
         <span class="asset-device-empty" hidden><i class="bi bi-pc-display"></i></span>
        <?php else: ?><span class="asset-device-empty" title="No device image"><i class="bi bi-pc-display"></i></span><?php endif; ?>
       </td>
       <td><a class="asset-tag-link" href="<?php echo e(route('inventory.show', ['assets',$record->id])); ?>"><?php echo e($record->asset_tag); ?></a></td>
       <td><span class="asset-mono" title="<?php echo e($record->serial_number); ?>"><?php echo e($record->serial_number ?: '—'); ?></span></td>
       <td title="<?php echo e($record->model); ?>"><?php echo e($record->model ?: '—'); ?></td>
       <td><?php echo e($record->category?->name ?? '—'); ?></td>
       <td><?php echo e($record->location?->name ?? '—'); ?></td>
       <td><?php echo e($record->brand ?: '—'); ?></td>
       <td><span class="asset-supplier-dot"></span><?php echo e($record->supplier?->company_name ?? '—'); ?></td>
       <td class="text-center">
        <?php if($record->status==='available' && auth()->user()->hasPermission('assignments.manage')): ?>
         <button type="button" class="asset-check-btn checkout js-asset-checkout" data-asset-id="<?php echo e($record->id); ?>" data-asset-tag="<?php echo e($record->asset_tag); ?>">Checkout</button>
        <?php elseif($record->status==='assigned' && $activeAssignment && auth()->user()->hasPermission('assignments.manage')): ?>
         <button type="button" class="asset-check-btn checkin js-asset-checkin" data-assignment-id="<?php echo e($activeAssignment->id); ?>" data-asset-tag="<?php echo e($record->asset_tag); ?>">Checkin</button>
        <?php else: ?>
         <span class="asset-status-pill"><?php echo e(str_replace('_',' ',$record->status)); ?></span>
        <?php endif; ?>
       </td>
       <td>
        <div class="asset-row-actions">
         <a class="asset-icon-action" href="<?php echo e(route('inventory.show', ['assets',$record->id])); ?>" title="View asset" aria-label="View asset"><i class="bi bi-eye"></i></a>
         <?php if(auth()->user()->hasPermission('assets.manage')): ?>
          <a class="asset-icon-action clone" href="<?php echo e(route('inventory.clone', ['assets',$record->id])); ?>" title="Clone asset" aria-label="Clone asset"><i class="bi bi-copy"></i></a>
          <a class="asset-icon-action edit" href="<?php echo e(route('inventory.edit', ['assets',$record->id])); ?>" title="Edit asset" aria-label="Edit asset"><i class="bi bi-pencil"></i></a>
          <form method="POST" action="<?php echo e(route('inventory.destroy', ['assets',$record->id])); ?>" onsubmit="return confirm('Archive asset <?php echo e(addslashes($record->asset_tag)); ?>? It will be removed from the active register.')">
           <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
           <button class="asset-icon-action archive" type="submit" title="Archive asset" aria-label="Archive asset"><i class="bi bi-trash"></i></button>
          </form>
         <?php endif; ?>
        </div>
       </td>
      </tr>
     <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <tr><td colspan="13" class="asset-empty-state"><i class="bi bi-inbox"></i>No assets found. Try changing your search or filters.</td></tr>
     <?php endif; ?>
    </tbody>
   </table>
  </div>
 </div>
</div>

<?php if(auth()->user()->hasPermission('assets.manage')): ?>
 <div class="modal fade" id="importAssetsModal" tabindex="-1" aria-labelledby="importAssetsModalTitle" aria-hidden="true">
  <div class="modal-dialog"><form class="modal-content" method="POST" enctype="multipart/form-data" action="<?php echo e(route('imports.store','assets')); ?>">
   <?php echo csrf_field(); ?>
   <div class="modal-header"><h5 class="modal-title" id="importAssetsModalTitle">Import assets</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
   <div class="modal-body"><p class="small text-secondary">CSV and XLSX files are accepted. Maximum <?php echo e(number_format($importMaxRows)); ?> rows and <?php echo e($importMaxSizeMb); ?> MB per file.</p><label class="form-label" for="assetImportFile">Excel or CSV file</label><input class="form-control" id="assetImportFile" type="file" name="file" accept=".csv,.txt,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required></div>
   <div class="modal-footer"><button class="btn btn-light border" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Import assets</button></div>
  </form></div>
 </div>

 <div class="modal fade" id="bulkUpdateModal" tabindex="-1" aria-labelledby="bulkUpdateModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg"><form class="modal-content" method="POST" action="<?php echo e(route('assets.bulk-update')); ?>" id="bulkUpdateForm">
   <?php echo csrf_field(); ?>
   <div class="modal-header"><h5 class="modal-title" id="bulkUpdateModalTitle">Bulk update selected assets</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
   <div class="modal-body"><p class="small text-secondary">Only fields with a selected value will be changed. A history and audit entry will be recorded for each asset.</p><div id="bulkUpdateSelectedIds"></div>
    <div class="row g-3">
     <div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">Do not change</option><option value="available">Available</option><option value="defective">Defective</option></select></div>
     <div class="col-md-6"><label class="form-label">Condition</label><select class="form-select" name="condition"><option value="">Do not change</option><?php $__currentLoopData = ['New','Good','Fair','Defective']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>"><?php echo e($value); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
     <div class="col-md-4"><label class="form-label">Department</label><select class="form-select" name="department_id"><option value="">Do not change</option><?php $__currentLoopData = \App\Models\Department::orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($item->id); ?>"><?php echo e($item->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
     <div class="col-md-4"><label class="form-label">Location</label><select class="form-select" name="location_id"><option value="">Do not change</option><?php $__currentLoopData = \App\Models\Location::orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($item->id); ?>"><?php echo e($item->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
     <div class="col-md-4"><label class="form-label">Supplier</label><select class="form-select" name="supplier_id"><option value="">Do not change</option><?php $__currentLoopData = \App\Models\Supplier::orderBy('company_name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($item->id); ?>"><?php echo e($item->company_name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
    </div>
   </div>
   <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Update selected assets</button></div>
  </form></div>
 </div>

 <div class="modal fade" id="bulkAssignmentModal" tabindex="-1"><div class="modal-dialog modal-lg"><form class="modal-content" method="POST" action="<?php echo e(route('assets.bulk-assign')); ?>" id="bulkAssignmentForm"><?php echo csrf_field(); ?><div class="modal-header"><h5 class="modal-title">Assign selected assets</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="small text-secondary">Creates separate pending approval records for each selected asset.</p><div id="bulkAssignmentIds"></div><div class="row g-3"><div class="col-md-6"><label class="form-label">Employee / custodian</label><select name="employee_id" class="form-select" required><option value="">Select employee</option><?php $__currentLoopData = \App\Models\Employee::where('status','active')->orderBy('last_name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($employee->id); ?>"><?php echo e($employee->employee_no); ?> — <?php echo e($employee->full_name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div><div class="col-md-6"><label class="form-label">Assignment type</label><select name="assignment_type" class="form-select"><option value="permanent">Permanent</option><option value="temporary">Temporary</option></select></div><div class="col-md-6"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">Use employee department</option><?php $__currentLoopData = \App\Models\Department::orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($dept->id); ?>"><?php echo e($dept->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div><div class="col-md-6"><label class="form-label">Location</label><select name="location_id" class="form-select"><option value="">Keep current location</option><?php $__currentLoopData = \App\Models\Location::orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($loc->id); ?>"><?php echo e($loc->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div><div class="col-md-6"><label class="form-label">Issue date</label><input type="date" name="issued_at" class="form-control" value="<?php echo e(today()->toDateString()); ?>" required></div><div class="col-md-6"><label class="form-label">Due date</label><input type="date" name="due_date" class="form-control"></div><div class="col-12"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Submit for approval</button></div></form></div></div>
 <div class="modal fade" id="assetCheckoutModal" tabindex="-1" aria-labelledby="assetCheckoutModalTitle" aria-hidden="true">
  <div class="modal-dialog"><form class="modal-content" method="POST" id="assetCheckoutForm" data-action-template="<?php echo e(route('assets.assign', ['asset'=>'ASSET_ID'])); ?>">
   <?php echo csrf_field(); ?>
   <div class="modal-header"><h5 class="modal-title" id="assetCheckoutModalTitle">Check out asset</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
   <div class="modal-body"><p class="small text-secondary" id="assetCheckoutTag"></p><label class="form-label" for="checkoutEmployee">Employee / Custodian</label><select class="form-select mb-3" name="employee_id" id="checkoutEmployee" required><option value="">Select employee</option><?php $__currentLoopData = \App\Models\Employee::where('status','active')->orderBy('last_name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($employee->id); ?>"><?php echo e($employee->employee_no); ?> — <?php echo e($employee->full_name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><div class="row g-2"><div class="col-md-6"><label class="form-label">Assignment type</label><select class="form-select mb-3" name="assignment_type"><option value="permanent">Permanent</option><option value="temporary">Temporary</option></select></div><div class="col-md-6"><label class="form-label">Department</label><select class="form-select mb-3" name="department_id"><option value="">Use employee department</option><?php $__currentLoopData = \App\Models\Department::orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($dept->id); ?>"><?php echo e($dept->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div><div class="col-md-6"><label class="form-label">Location</label><select class="form-select mb-3" name="location_id"><option value="">Keep current location</option><?php $__currentLoopData = \App\Models\Location::orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($loc->id); ?>"><?php echo e($loc->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div><div class="col-md-6"><label class="form-label">Approval</label><select class="form-select mb-3" name="requires_approval"><option value="0">Issue immediately</option><option value="1">Submit for approval</option></select></div></div><label class="form-label" for="checkoutDate">Issue date</label><input class="form-control mb-3" type="date" id="checkoutDate" name="issued_at" value="<?php echo e(today()->toDateString()); ?>" required><label class="form-label" for="checkoutDueDate">Due date</label><input class="form-control mb-3" type="date" id="checkoutDueDate" name="due_date"><label class="form-label" for="checkoutRemarks">Remarks</label><textarea class="form-control" id="checkoutRemarks" name="remarks" rows="2"></textarea></div>
   <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Confirm checkout</button></div>
  </form></div>
 </div>

 <div class="modal fade" id="assetCheckinModal" tabindex="-1" aria-labelledby="assetCheckinModalTitle" aria-hidden="true">
  <div class="modal-dialog"><form class="modal-content" method="POST" id="assetCheckinForm" data-action-template="<?php echo e(route('assignments.return', ['assignment'=>'ASSIGNMENT_ID'])); ?>">
   <?php echo csrf_field(); ?>
   <div class="modal-header"><h5 class="modal-title" id="assetCheckinModalTitle">Check in asset</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
   <div class="modal-body"><p class="small text-secondary" id="assetCheckinTag"></p><label class="form-label" for="checkinCondition">Returned condition</label><select class="form-select mb-3" id="checkinCondition" name="condition" required><option>Good</option><option>New</option><option>Fair</option><option>Defective</option></select><label class="form-label" for="checkinRemarks">Remarks</label><textarea class="form-control" id="checkinRemarks" name="remarks" rows="2"></textarea></div>
   <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Confirm checkin</button></div>
  </form></div>
 </div>
<?php endif; ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ids = () => Array.from(document.querySelectorAll('.asset-row-select:checked')).map((item) => item.value);
    const selectAll = document.getElementById('selectAllAssets');
    const perPage = document.getElementById('assetPerPage');
    const actionSelect = document.getElementById('assetBulkAction');
    const goButton = document.getElementById('assetBulkGo');
    const bulkUpdateIds = document.getElementById('bulkUpdateSelectedIds');

    if (perPage) perPage.addEventListener('change', function () {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', perPage.value);
        url.searchParams.delete('page');
        window.location.assign(url.toString());
    });

    if (selectAll) selectAll.addEventListener('change', function () {
        document.querySelectorAll('.asset-row-select').forEach((item) => { item.checked = selectAll.checked; });
        selectAll.indeterminate = false;
    });
    document.querySelectorAll('.asset-row-select').forEach((item) => item.addEventListener('change', function () {
        const all = Array.from(document.querySelectorAll('.asset-row-select'));
        const selected = all.filter((entry) => entry.checked).length;
        if (selectAll) { selectAll.checked = all.length > 0 && selected === all.length; selectAll.indeterminate = selected > 0 && selected < all.length; }
    }));

    if (goButton) goButton.addEventListener('click', function () {
        const selected = ids();
        if (!selected.length) { window.alert('Select at least one asset first.'); return; }
        if (actionSelect.value === 'print') {
            const url = new URL(<?php echo json_encode(route('assets.bulk-labels'), 15, 512) ?>, window.location.origin);
            selected.forEach((id) => url.searchParams.append('ids[]', id));
            window.open(url.toString(), '_blank', 'noopener');
        } else if (actionSelect.value === 'update') {
            if (bulkUpdateIds) bulkUpdateIds.innerHTML = selected.map((id) => '<input type="hidden" name="ids[]" value="' + String(id).replace(/[^0-9]/g, '') + '">').join('');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkUpdateModal')).show();
        } else if (actionSelect.value === 'assign') {
            document.getElementById('bulkAssignmentIds').innerHTML = selected.map((id) => '<input type="hidden" name="asset_ids[]" value="' + String(id).replace(/[^0-9]/g, '') + '">').join('');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkAssignmentModal')).show();
        } else {
            window.alert('Choose a bulk action.');
        }
    });

    document.querySelectorAll('.js-asset-checkout').forEach((button) => button.addEventListener('click', function () {
        const form = document.getElementById('assetCheckoutForm');
        form.action = form.dataset.actionTemplate.replace('ASSET_ID', this.dataset.assetId);
        document.getElementById('assetCheckoutTag').textContent = 'Asset: ' + this.dataset.assetTag;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('assetCheckoutModal')).show();
    }));
    document.querySelectorAll('.js-asset-checkin').forEach((button) => button.addEventListener('click', function () {
        const form = document.getElementById('assetCheckinForm');
        form.action = form.dataset.actionTemplate.replace('ASSIGNMENT_ID', this.dataset.assignmentId);
        document.getElementById('assetCheckinTag').textContent = 'Asset: ' + this.dataset.assetTag;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('assetCheckinModal')).show();
    }));
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\it-inventory\resources\views/inventory/assets-index.blade.php ENDPATH**/ ?>