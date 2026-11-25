<?php $__env->startSection('title','Stock Counts'); ?>
<?php $__env->startSection('breadcrumb','Stock Counts'); ?>
<?php $__env->startSection('content'); ?>
<div class="page-heading asset-register-heading">
    <div><div class="eyebrow">STOCK CONTROL</div><h1>Physical stock counts</h1><p>Record observed quantities, review variances, and reconcile balances through the stock ledger.</p></div>
    <div class="heading-actions"><?php if(auth()->user()->hasPermission('reports.export')): ?><a class="btn btn-light border" href="<?php echo e(route('stock-counts.variance.csv')); ?>"><i class="bi bi-filetype-csv me-1"></i>Export variances</a><?php endif; ?></div>
</div>
<div class="row g-3 align-items-start">
    <?php if(auth()->user()->hasPermission('consumables.manage')): ?>
    <div class="col-xl-4">
        <div class="panel">
            <div class="panel-header"><div><h2>Start a stock count</h2><p>Creates a snapshot of current quantities for the selected location.</p></div></div>
            <form method="POST" action="<?php echo e(route('stock-counts.store')); ?>" class="p-3"><?php echo csrf_field(); ?>
                <div class="mb-3"><label class="form-label">Location</label><select class="form-select" name="location_id"><option value="">All locations</option><?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($location->id); ?>"><?php echo e($location->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                <div class="mb-3"><label class="form-label">Count date</label><input class="form-control" type="date" name="counted_on" value="<?php echo e(today()->toDateString()); ?>" required></div>
                <div class="mb-3"><label class="form-label">Instructions / remarks</label><textarea class="form-control" name="remarks" rows="3" maxlength="2000" placeholder="Optional instructions for the counting team"></textarea></div>
                <button class="btn btn-primary w-100"><i class="bi bi-plus-lg me-2"></i>Create count checklist</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-xl-<?php echo e(auth()->user()->hasPermission('consumables.manage')?'8':'12'); ?>">
        <div class="panel asset-register-panel"><div class="panel-header"><div><h2>Stock count register</h2><p>Showing <?php echo e($counts->firstItem() ?? 0); ?> to <?php echo e($counts->lastItem() ?? 0); ?> of <?php echo e($counts->total()); ?> count sessions</p></div></div>
            <div class="table-responsive"><table class="table data-table stock-count-table mb-0"><thead><tr><th>Count no.</th><th>Location</th><th>Count date</th><th>Counter</th><th>Status</th><th>Items</th><th></th></tr></thead><tbody>
            <?php $__empty_1 = true; $__currentLoopData = $counts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td class="mono"><?php echo e($count->count_no); ?></td><td><?php echo e($count->location?->name ?? 'All locations'); ?></td><td><?php echo e($count->counted_on?->format(config('app.date_format', 'd M Y')) ?? '—'); ?></td><td><?php echo e($count->counter?->name ?? '—'); ?></td><td><span class="status-pill <?php echo e($count->status); ?>"><?php echo e(str_replace('_',' ',$count->status)); ?></span></td><td><?php echo e($count->items()->count()); ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?php echo e(route('stock-counts.show',$count)); ?>">Open</a></td></tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="7" class="empty-state">No stock count sessions yet.</td></tr><?php endif; ?>
            </tbody></table></div><div class="pagination-wrap"><?php echo e($counts->links()); ?></div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\it-inventory\resources\views/stock-counts/index.blade.php ENDPATH**/ ?>