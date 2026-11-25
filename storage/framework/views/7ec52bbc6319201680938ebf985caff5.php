<?php $__env->startSection('title', 'Asset Categories'); ?>
<?php $__env->startSection('breadcrumb', 'Asset Categories'); ?>

<?php $__env->startPush('styles'); ?>
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
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="category-list-page">
 <div class="category-list-panel">
  <form method="GET" action="<?php echo e(route('inventory.index', 'categories')); ?>" class="category-list-toolbar" role="search">
   <div class="category-toolbar-left">
    <div class="category-quick-search">
     <input type="search" name="q" value="<?php echo e(request('q')); ?>" placeholder="Search category name, code, or example..." aria-label="Search categories">
     <button type="submit" title="Search categories" aria-label="Search"><i class="bi bi-search"></i></button>
    </div>
    <?php if(request()->filled('q')): ?><a class="category-mini-btn" href="<?php echo e(route('inventory.index', 'categories')); ?>" title="Clear search and filters" aria-label="Clear search and filters"><i class="bi bi-x-lg"></i></a><?php endif; ?>
   </div>
   <div class="category-toolbar-right">
    <select class="category-filter-select" name="type" aria-label="Filter by category type">
     <option value="">All types</option>
     <?php $__currentLoopData = $filterOptions['type'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if((string)request('type')===(string)$value): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <select class="category-filter-select" name="is_active" aria-label="Filter by category status">
     <option value="">All statuses</option>
     <?php $__currentLoopData = $filterOptions['is_active'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if((string)request('is_active')===(string)$value): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <button class="category-mini-btn" type="submit" title="Apply filters" aria-label="Apply filters"><i class="bi bi-funnel"></i></button>
    <?php if(request()->hasAny(['q','type','is_active'])): ?><a class="category-mini-btn" href="<?php echo e(route('inventory.index', 'categories')); ?>" title="Reset filters" aria-label="Reset filters"><i class="bi bi-arrow-counterclockwise"></i></a><?php endif; ?>
    <?php if(auth()->user()->hasPermission('categories.manage')): ?><a class="category-mini-btn primary" href="<?php echo e(route('inventory.create', 'categories')); ?>" title="Add category" aria-label="Add category"><i class="bi bi-plus-lg"></i></a><?php endif; ?>
   </div>
  </form>

  <div class="category-secondary-bar">
   <div class="d-flex align-items-center gap-2">
    <span class="category-record-meta">Showing <strong><?php echo e($records->firstItem() ?? 0); ?>–<?php echo e($records->lastItem() ?? 0); ?></strong> of <strong><?php echo e(number_format($records->total())); ?></strong> categories</span>
    <form method="GET" action="<?php echo e(route('inventory.index', 'categories')); ?>" class="d-inline-flex align-items-center gap-1">
     <?php $__currentLoopData = request()->except(['per_page','page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> <?php if(is_scalar($value)): ?><input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>"><?php endif; ?> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
     <label class="category-record-meta" for="categoryPageSize">Rows</label>
     <select id="categoryPageSize" name="per_page" class="category-filter-select" style="min-width:58px;width:62px" onchange="this.form.submit()">
      <?php $__currentLoopData = [10,25,50,100]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $size): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($size); ?>" <?php if($perPage===$size): echo 'selected'; endif; ?>><?php echo e($size); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
     </select>
    </form>
   </div>
   <div class="category-pagination"><?php echo e($records->onEachSide(2)->links()); ?></div>
  </div>

  <div class="table-responsive">
   <table class="table category-register-table align-middle mb-0">
    <thead><tr><th style="width:78px">Code</th><th>Category</th><th style="width:95px">Type</th><th>Parent</th><th>Examples</th><th style="width:125px">Depreciation / salvage</th><th style="width:88px">Useful life</th><th style="width:75px">Min. stock</th><th style="width:70px">Fields</th><th style="width:75px">Status</th><th style="width:88px" class="text-end">Actions</th></tr></thead>
    <tbody>
     <?php $__empty_1 = true; $__currentLoopData = $records; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <tr>
       <td><span class="category-code"><?php echo e($category->code); ?></span></td>
       <td><a class="category-name" href="<?php echo e(route('inventory.show', ['categories',$category->id])); ?>"><?php echo e($category->name); ?></a></td>
       <td><span class="category-type-pill"><?php echo e($category->type); ?></span></td>
       <td><?php echo e($category->parent?->name ?? '—'); ?></td>
       <td class="category-description" title="<?php echo e($category->description); ?>"><?php echo e($category->description ?: '—'); ?></td>
       <td><?php echo e($category->depreciation_method && $category->depreciation_method !== 'not_configured' ? str_replace('_',' ',$category->depreciation_method) : '—'); ?><?php if((float)$category->salvage_percent > 0): ?><span class="text-secondary"> · <?php echo e(rtrim(rtrim(number_format((float)$category->salvage_percent, 3), '0'), '.')); ?>%</span><?php endif; ?></td>
       <td><?php echo e(($category->useful_life_months ?: $category->default_useful_life_months) ? (($category->useful_life_months ?: $category->default_useful_life_months).' mo.') : '—'); ?></td>
       <td><?php echo e($category->type === 'consumable' ? (int)$category->default_min_stock_level : '—'); ?></td>
       <td><?php echo e(count($category->custom_fields ?? []) ?: '—'); ?></td>
       <td><span class="category-status <?php echo e($category->is_active ? 'active' : 'inactive'); ?>"><?php echo e($category->is_active ? 'Active' : 'Inactive'); ?></span></td>
       <td><div class="category-actions">
        <a class="category-icon-action" href="<?php echo e(route('inventory.show', ['categories',$category->id])); ?>" title="View category" aria-label="View category"><i class="bi bi-eye"></i></a>
        <?php if(auth()->user()->hasPermission('categories.manage')): ?>
         <a class="category-icon-action edit" href="<?php echo e(route('inventory.edit', ['categories',$category->id])); ?>" title="Edit category" aria-label="Edit category"><i class="bi bi-pencil"></i></a>
         <?php if($category->is_active): ?>
          <form method="POST" action="<?php echo e(route('inventory.destroy', ['categories',$category->id])); ?>" onsubmit="return confirm('Archive this category? Existing linked asset and stock records will remain connected.')"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="category-icon-action archive" type="submit" title="Archive category" aria-label="Archive category"><i class="bi bi-archive"></i></button></form>
         <?php endif; ?>
        <?php endif; ?>
       </div></td>
      </tr>
     <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <tr><td colspan="11" class="text-center py-5 text-secondary"><i class="bi bi-tags d-block fs-4 mb-1"></i>No categories found.</td></tr>
     <?php endif; ?>
    </tbody>
   </table>
  </div>
  <div class="category-pagination"><?php echo e($records->onEachSide(2)->links()); ?></div>
 </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\it-inventory\resources\views/inventory/categories-index.blade.php ENDPATH**/ ?>