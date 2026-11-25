<?php $__env->startSection('title','Dashboard'); ?>
<?php $__env->startSection('breadcrumb','Dashboard'); ?>
<?php $__env->startSection('content'); ?>
<div class="page-heading">
    <div><div class="eyebrow">OVERVIEW</div><h1>IT Inventory Dashboard</h1><p>Real-time overview of IT assets, accountability, inventory, maintenance and system activity.</p></div>
    <div class="heading-actions">
        <?php if(auth()->user()->hasPermission('assets.create')): ?>
            <a href="<?php echo e(route('inventory.create','assets')); ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Register asset</a>
        <?php endif; ?>
    </div>
</div>

<?php if(!empty($databaseWarnings)): ?>
<div class="alert alert-warning" role="alert">
    <strong>Some dashboard data could not be loaded.</strong>
    <div class="small mt-1">Affected metrics: <?php echo e(implode(', ', $databaseWarnings)); ?>. Review <code>storage/logs/laravel.log</code>. Failed metrics are shown as zero or empty lists.</div>
</div>
<?php endif; ?>

<h2 class="h6 text-uppercase text-muted mt-3 mb-2">Asset status</h2>
<div class="row g-3 metric-grid">
<?php $__currentLoopData = [
 ['Total IT Assets','assets','bi-pc-display','blue'],
 ['Total Active Assets','active_assets','bi-activity','green'],
 ['Available Assets','available','bi-check2-circle','green'],
 ['Assigned Assets','assigned','bi-person-check','violet'],
 ['Under Maintenance','maintenance','bi-wrench-adjustable','amber'],
 ['Defective Assets','defective','bi-exclamation-triangle','red'],
 ['Retired / Disposed','retired','bi-archive','gray'],
 ['Lost / Missing','lost','bi-search','gray']
]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label,$key,$icon,$tone]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="col-6 col-xl-3"><div class="metric-card h-100"><div class="metric-top"><span class="metric-label"><?php echo e($label); ?></span><span class="metric-icon <?php echo e($tone); ?>"><i class="bi <?php echo e($icon); ?>"></i></span></div><div class="metric-value"><?php echo e(number_format($stats[$key])); ?></div><div class="metric-foot"><span>Current records</span><span class="metric-note">Live</span></div></div></div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<h2 class="h6 text-uppercase text-muted mt-4 mb-2">Inventory and workflow indicators</h2>
<div class="row g-3">
<?php $__currentLoopData = [
 ['Total Inventory Value',config('app.currency_symbol','₱').number_format($stats['inventory_value'],2),'bi-cash-stack','blue'],
 ['Total Software Licenses',number_format($stats['software_licenses']),'bi-key','violet'],
 ['Expiring Software Licenses',number_format($stats['expiring_licenses']),'bi-hourglass-split','amber'],
 ['Low Stock Consumables',number_format($stats['low_stock']),'bi-box-seam','red'],
 ['Pending Asset Requests',number_format($stats['pending_asset_requests']),'bi-inbox','amber'],
 ['Pending Approvals',number_format($stats['pending_approvals']),'bi-clipboard-check','violet'],
 ['Overdue Maintenance',number_format($stats['overdue_maintenance']),'bi-calendar-x','red']
]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label,$value,$icon,$tone]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="col-6 col-xl-3"><div class="metric-card h-100"><div class="metric-top"><span class="metric-label"><?php echo e($label); ?></span><span class="metric-icon <?php echo e($tone); ?>"><i class="bi <?php echo e($icon); ?>"></i></span></div><div class="metric-value fs-4"><?php echo e($value); ?></div><div class="metric-foot"><span>Current records</span></div></div></div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<h2 class="h6 text-uppercase text-muted mt-4 mb-2">Asset analytics</h2>
<div class="row g-3">
<div class="col-xl-6"><div class="panel h-100"><div class="panel-header"><div><h2>Asset condition overview</h2><p>Registered assets grouped by current status</p></div></div><div class="chart-wrap"><canvas id="assetConditionChart"></canvas></div></div></div>
<div class="col-xl-6"><div class="panel h-100"><div class="panel-header"><div><h2>Monthly asset acquisitions</h2><p>Last 12 months based on purchase date</p></div></div><div class="chart-wrap"><canvas id="monthlyAcquisitionChart"></canvas></div></div></div>
<div class="col-xl-4"><div class="panel h-100"><div class="panel-header"><div><h2>By department</h2><p>Asset distribution</p></div></div><div class="chart-wrap"><canvas id="departmentChart"></canvas></div></div></div>
<div class="col-xl-4"><div class="panel h-100"><div class="panel-header"><div><h2>By category</h2><p>Asset distribution</p></div></div><div class="chart-wrap"><canvas id="categoryChart"></canvas></div></div></div>
<div class="col-xl-4"><div class="panel h-100"><div class="panel-header"><div><h2>By location</h2><p>Asset distribution</p></div></div><div class="chart-wrap"><canvas id="locationChart"></canvas></div></div></div>
<div class="col-xl-6"><div class="panel h-100"><div class="panel-header"><div><h2>Asset maintenance statistics</h2><p>Maintenance records by status</p></div></div><div class="chart-wrap"><canvas id="maintenanceChart"></canvas></div></div></div>
<div class="col-xl-6"><div class="panel h-100"><div class="panel-header"><div><h2>Operational attention</h2><p>Items requiring follow-up</p></div></div>
<div class="snapshot-list">
<div class="snapshot-row"><div class="snapshot-icon amber"><i class="bi bi-box-seam"></i></div><div class="snapshot-copy"><strong>Low stock consumables</strong><span>At or below reorder level</span></div><strong class="snapshot-count"><?php echo e($stats['low_stock']); ?></strong></div>
<div class="snapshot-row"><div class="snapshot-icon violet"><i class="bi bi-hourglass-split"></i></div><div class="snapshot-copy"><strong>Expiring licenses</strong><span>Within 45 days</span></div><strong class="snapshot-count"><?php echo e($stats['expiring_licenses']); ?></strong></div>
<div class="snapshot-row"><div class="snapshot-icon red"><i class="bi bi-calendar-x"></i></div><div class="snapshot-copy"><strong>Overdue maintenance</strong><span>Past scheduled date</span></div><strong class="snapshot-count"><?php echo e($stats['overdue_maintenance']); ?></strong></div>
<div class="snapshot-row"><div class="snapshot-icon blue"><i class="bi bi-headset"></i></div><div class="snapshot-copy"><strong>Open support tickets</strong><span>Open, in progress or waiting</span></div><strong class="snapshot-count"><?php echo e($stats['open_tickets']); ?></strong></div>
</div></div></div>
</div>

<h2 class="h6 text-uppercase text-muted mt-4 mb-2">Recent asset movements</h2>
<div class="row g-3">
<div class="col-xl-4"><div class="panel h-100"><div class="panel-header"><div><h2>Recently added assets</h2><p>Latest registered equipment</p></div></div><div class="table-responsive"><table class="table data-table align-middle mb-0"><thead><tr><th>Asset</th><th>Category</th><th>Status</th></tr></thead><tbody>
<?php $__empty_1 = true; $__currentLoopData = $recentAssets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $asset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><a class="table-primary" href="<?php echo e(route('inventory.show',['assets',$asset->id])); ?>"><?php echo e($asset->name); ?></a><div class="table-sub mono"><?php echo e($asset->asset_tag); ?></div><div class="table-sub"><?php echo e(optional($asset->created_at)->format('d M Y')); ?></div></td><td><?php echo e($asset->category?->name ?? '—'); ?></td><td><span class="status-pill <?php echo e($asset->status); ?>"><?php echo e(str_replace('_',' ',$asset->status)); ?></span></td></tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="3" class="empty-state">No recently added assets.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<div class="col-xl-4"><div class="panel h-100"><div class="panel-header"><div><h2>Recently assigned assets</h2><p>Active accountability records</p></div></div><div class="table-responsive"><table class="table data-table align-middle mb-0"><thead><tr><th>Asset / Employee</th><th>Issued</th></tr></thead><tbody>
<?php $__empty_1 = true; $__currentLoopData = $recentAssignments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><strong><?php echo e($assignment->asset?->name ?? 'Asset unavailable'); ?></strong><div class="table-sub"><?php echo e($assignment->employee?->first_name); ?> <?php echo e($assignment->employee?->last_name); ?></div><div class="table-sub mono"><?php echo e($assignment->reference_no); ?></div></td><td><?php echo e(optional($assignment->issued_at)->format('d M Y') ?? '—'); ?></td></tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="2" class="empty-state">No active assignment records.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<div class="col-xl-4"><div class="panel h-100"><div class="panel-header"><div><h2>Recently returned assets</h2><p>Latest returned equipment</p></div></div><div class="table-responsive"><table class="table data-table align-middle mb-0"><thead><tr><th>Asset / Employee</th><th>Returned</th></tr></thead><tbody>
<?php $__empty_1 = true; $__currentLoopData = $recentReturns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><strong><?php echo e($assignment->asset?->name ?? 'Asset unavailable'); ?></strong><div class="table-sub"><?php echo e($assignment->employee?->first_name); ?> <?php echo e($assignment->employee?->last_name); ?></div><div class="table-sub mono"><?php echo e($assignment->reference_no); ?></div></td><td><?php echo e(optional($assignment->returned_at)->format('d M Y') ?? '—'); ?></td></tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="2" class="empty-state">No returned asset records.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
</div>

<h2 class="h6 text-uppercase text-muted mt-4 mb-2">Recent system activities</h2>
<div class="panel"><div class="table-responsive"><table class="table data-table align-middle mb-0"><thead><tr><th>Date / Time</th><th>User</th><th>Activity</th><th>Module</th></tr></thead><tbody>
<?php $__empty_1 = true; $__currentLoopData = $recentActivities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><?php echo e(\Illuminate\Support\Carbon::parse($activity->created_at)->format('d M Y H:i')); ?></td><td><?php echo e($activity->user_name ?? 'System'); ?></td><td><?php echo e(str($activity->action)->replace('_',' ')->headline()); ?></td><td><?php echo e(str($activity->module)->headline()); ?></td></tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="4" class="empty-state">No system activity has been recorded.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const chartPalette=['#315c9b','#27866b','#c48b36','#8b6bb1','#bf6464','#8894a5','#4d9ca8','#9b7a58'];
function makeBar(id, labels, values, horizontal=false) {
 const el=document.getElementById(id); if(!el) return;
 new Chart(el,{type:'bar',data:{labels,datasets:[{data:values,backgroundColor:chartPalette,borderRadius:5,maxBarThickness:34}]},
 options:{indexAxis:horizontal?'y':'x',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,grid:{display:!horizontal},ticks:{precision:0}},y:{beginAtZero:true,grid:{display:horizontal},ticks:{precision:0}}}}});
}
function makeDoughnut(id, labels, values) {
 const el=document.getElementById(id); if(!el) return;
 new Chart(el,{type:'doughnut',data:{labels,datasets:[{data:values,backgroundColor:chartPalette,borderWidth:1}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}});
}
const statusData=<?php echo json_encode($statusBreakdown, 15, 512) ?>;
makeDoughnut('assetConditionChart',Object.keys(statusData).map(x=>x.replaceAll('_',' ')),Object.values(statusData));
const dept=<?php echo json_encode($departmentDistribution, 15, 512) ?>; makeBar('departmentChart',dept.map(x=>x.label),dept.map(x=>Number(x.total)),true);
const cat=<?php echo json_encode($categoryDistribution, 15, 512) ?>; makeBar('categoryChart',cat.map(x=>x.label),cat.map(x=>Number(x.total)),true);
const loc=<?php echo json_encode($locationDistribution, 15, 512) ?>; makeBar('locationChart',loc.map(x=>x.label),loc.map(x=>Number(x.total)),true);
const maint=<?php echo json_encode($maintenanceStatistics, 15, 512) ?>; makeDoughnut('maintenanceChart',Object.keys(maint),Object.values(maint));
const acquisition=<?php echo json_encode($monthlyAcquisitions, 15, 512) ?>;
const acqEl=document.getElementById('monthlyAcquisitionChart');
if(acqEl){const months=[];const values=[];const now=new Date();for(let i=11;i>=0;i--){const d=new Date(now.getFullYear(),now.getMonth()-i,1);const key=d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0');months.push(d.toLocaleDateString(undefined,{month:'short',year:'2-digit'}));values.push(Number(acquisition[key]||0));}
new Chart(acqEl,{type:'line',data:{labels:months,datasets:[{label:'Assets acquired',data:values,borderColor:'#315c9b',backgroundColor:'rgba(49,92,155,.12)',fill:true,tension:.3}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\it-inventory\resources\views/dashboard/index.blade.php ENDPATH**/ ?>