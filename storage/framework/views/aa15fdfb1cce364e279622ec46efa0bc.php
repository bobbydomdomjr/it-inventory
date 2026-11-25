<?php $__env->startSection('title','Reports & analytics'); ?>
<?php $__env->startSection('breadcrumb','Reports'); ?>
<?php $__env->startSection('content'); ?>
<?php $defaultExportFormat = in_array($defaultExportFormat ?? 'xlsx', ['csv', 'xlsx', 'pdf'], true) ? $defaultExportFormat : 'xlsx'; ?>
<div class="page-heading">
    <div><div class="eyebrow">INSIGHTS</div><h1>Reports & analytics</h1><p>Operational summaries for asset accountability, stock control, maintenance, procurement and support. Preferred format: <?php echo e(strtoupper($defaultExportFormat ?? 'xlsx')); ?>.</p></div>
    <a class="btn btn-primary" href="<?php echo e($defaultExportFormat === 'csv' ? route('reports.assets.csv') : route('reports.export', ['assets', $defaultExportFormat])); ?>"><i class="bi bi-download me-2"></i>Export asset register (<?php echo e(strtoupper($defaultExportFormat)); ?>)</a>
</div>
<div class="row g-3 mb-3 report-metric-grid">
    <?php $__currentLoopData = [
        ['Registered assets',$summary['asset_count'],'bi-pc-display'],
        ['Recorded asset value',config('app.currency_symbol', '₱').number_format($summary['asset_value'],2),'bi-cash-stack'],
        ['Assigned assets',$summary['assigned'],'bi-person-check'],
        ['Available assets',$summary['available'],'bi-check-circle'],
        ['Retired assets',$summary['retired'],'bi-archive'],
        ['Software licenses',$summary['software_licenses'],'bi-key'],
        ['Licenses expiring in 60 days',$summary['expiring_licenses'],'bi-hourglass-split'],
        ['Low-stock items',$summary['low_stock'],'bi-box-seam'],
        ['Open support tickets',$summary['open_tickets'],'bi-headset'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label,$value,$icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-6 col-xl-4"><div class="metric-card report-metric"><div class="metric-top"><span class="metric-label"><?php echo e($label); ?></span><span class="metric-icon blue" aria-hidden="true"><i class="bi <?php echo e($icon); ?>"></i></span></div><div class="metric-value <?php echo e(is_string($value)&&str_starts_with($value,config('app.currency_symbol', '₱'))?'metric-currency':''); ?>"><?php echo e($value); ?></div></div></div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php
$reportGroups = [
    'Asset and organization registers' => [
        ['assets','IT asset register','Asset tag, serial number, custodian, department, location, value and status.','reports.assets.csv'],
        ['categories','Asset categories','Category type, parent category and depreciation configuration.','register'],
        ['employees','Employee register','Employee number, name, position, department and status.','register'],
        ['departments','Department register','Department hierarchy and assigned department head.','register'],
        ['locations','Location register','Building, floor, room, storage and data-center hierarchy.','register'],
        ['suppliers','Supplier register','Supplier contact details, TIN and accreditation status.','register'],
        ['assignments','Asset accountability','Custodians, issuance dates, due dates and returns.','register'],
        ['transfers','Asset transfers and turnover','Origin and destination custodian, reason and approval status.','register'],
        ['disposals','Asset disposal register','Disposal method, justification, salvage value and final status.','register'],
    ],
    'Operations and compliance' => [
        ['stock','Stock and consumables','On-hand balances, reorder levels, supplier and stock value.','reports.stock.csv'],
        ['software','Legacy software license register','Legacy license records and seat totals; license keys are excluded.','register'],
        ['licenses','Normalized software entitlements','Software catalog, purchased/assigned seats, suppliers and renewal history; keys are excluded.','register'],
        ['maintenance','Maintenance and repair history','Work orders, costs, technicians, service dates and resolutions.','reports.maintenance.csv'],
        ['tickets','IT helpdesk tickets','Priority, SLA dates, assigned technicians and resolution status.','reports.tickets.csv'],
        ['purchase_requests','Purchase request lifecycle','PR purpose, request owner, item count, selected quotation and purchase order.','register'],
        ['procurements','Legacy procurement register','Legacy procurement records and received quantities.','register'],
        ['audits','Physical inventory audits','Audit scope, schedules, status and checklist coverage.','register'],
    ],
];
?>
<div class="row g-3">
    <?php $__currentLoopData = $reportGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group=>$reports): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="col-12">
        <div class="panel">
            <div class="panel-header"><div><h2><?php echo e($group); ?></h2><p><?php echo e(count($reports)); ?> available reports</p></div></div>
            <?php $__currentLoopData = $reports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$resource,$title,$description,$csvRoute]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="export-row">
                    <div class="export-icon"><i class="bi <?php echo e(match($resource){'assets'=>'bi-pc-display','categories'=>'bi-tags','employees'=>'bi-people','departments'=>'bi-diagram-3','locations'=>'bi-geo-alt','suppliers'=>'bi-truck','assignments'=>'bi-person-check','transfers'=>'bi-arrow-left-right','disposals'=>'bi-recycle','stock'=>'bi-box-seam','software'=>'bi-key','licenses'=>'bi-key-fill','purchase_requests'=>'bi-file-earmark-text','maintenance'=>'bi-wrench','tickets'=>'bi-headset','procurements'=>'bi-receipt','audits'=>'bi-clipboard-check',default=>'bi-file-earmark-bar-graph'}); ?>"></i></div>
                    <div class="export-copy"><strong><?php echo e($title); ?></strong><span><?php echo e($description); ?></span></div>
                    <div class="export-actions">
                        <?php if($csvRoute==='register'): ?><a class="btn btn-sm <?php echo e($defaultExportFormat === 'csv' ? 'btn-primary' : 'btn-outline-secondary'); ?>" href="<?php echo e(route('reports.registers.csv',$resource)); ?>">CSV<?php echo e($defaultExportFormat === 'csv' ? ' · Default' : ''); ?></a>
                        <?php else: ?><a class="btn btn-sm <?php echo e($defaultExportFormat === 'csv' ? 'btn-primary' : 'btn-outline-secondary'); ?>" href="<?php echo e(route($csvRoute)); ?>">CSV<?php echo e($defaultExportFormat === 'csv' ? ' · Default' : ''); ?></a><?php endif; ?>
                        <a class="btn btn-sm <?php echo e($defaultExportFormat === 'xlsx' ? 'btn-primary' : 'btn-outline-secondary'); ?>" href="<?php echo e(route('reports.export',[$resource,'xlsx'])); ?>">Excel<?php echo e($defaultExportFormat === 'xlsx' ? ' · Default' : ''); ?></a>
                        <a class="btn btn-sm <?php echo e($defaultExportFormat === 'pdf' ? 'btn-primary' : 'btn-outline-primary'); ?>" href="<?php echo e(route('reports.export',[$resource,'pdf'])); ?>">PDF<?php echo e($defaultExportFormat === 'pdf' ? ' · Default' : ''); ?></a>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <div class="col-12"><div class="panel"><div class="panel-header"><div><h2>Asset valuation</h2><p>Depreciation posting and book-value tracking.</p></div><div class="export-actions"><a class="btn btn-sm btn-outline-secondary" href="<?php echo e(route('reports.depreciation.csv')); ?>">CSV</a><a class="btn btn-sm btn-outline-secondary" href="<?php echo e(route('reports.export',['depreciation','xlsx'])); ?>">Excel</a><a class="btn btn-sm btn-outline-primary" href="<?php echo e(route('reports.export',['depreciation','pdf'])); ?>">PDF</a></div></div><div class="p-3 small text-secondary">Depreciation exports use recorded posted entries. The system does not infer an accounting method where the asset or category has no configured policy.</div></div></div>
    <div class="col-12"><div class="panel"><div class="panel-header"><div><h2>System activity</h2><p>Searchable history of user and record activity.</p></div><a class="btn btn-sm btn-outline-secondary" href="<?php echo e(route('activity.index')); ?>">Open audit trail</a></div></div></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\it-inventory\resources\views/reports/index.blade.php ENDPATH**/ ?>