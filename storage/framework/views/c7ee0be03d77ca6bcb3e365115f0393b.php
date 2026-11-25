<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
<title><?php echo $__env->yieldContent('title','Dashboard'); ?> · <?php echo e(config('app.name')); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"><?php if(file_exists(public_path('build/manifest.json'))): ?><?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css','resources/js/app.js']); ?><?php else: ?><link href="<?php echo e(asset('css/app.css')); ?>" rel="stylesheet"><?php endif; ?>
<?php echo $__env->yieldPushContent('styles'); ?></head><body>
<div class="app-shell" id="appShell" style="--blue: <?php echo e($systemBranding['primary'] ?? '#315f9f'); ?>; --green: <?php echo e($systemBranding['accent'] ?? '#0caa6a'); ?>;">
<script>
(() => {
    const shell = document.getElementById('appShell');
    if (!shell) return;

    // Prevent the expanded layout from flashing before the saved sidebar state is applied.
    shell.classList.add('sidebar-state-initializing');
    try {
        if (window.matchMedia('(min-width: 992px)').matches
            && window.localStorage.getItem('it-inventory.sidebar-collapsed') === '1') {
            shell.classList.add('sidebar-collapsed');
        }
    } catch (error) {
        // Keep the expanded layout when browser storage is unavailable.
    }
})();
</script>
<?php
    // Build the sidebar from the signed-in user's effective permissions.
    // Permission checks are cached for this render to avoid repeating role queries.
    $sidebarUser = auth()->user();
    $sidebarPermissionCache = [];
    $sidebarCan = function (string $permission) use ($sidebarUser, &$sidebarPermissionCache): bool {
        if (! array_key_exists($permission, $sidebarPermissionCache)) {
            $sidebarPermissionCache[$permission] = $sidebarUser->hasPermission($permission);
        }

        return $sidebarPermissionCache[$permission];
    };
    $sidebarItem = function (string $permission, string $label, string $icon, string $href, bool $active = false, int $badge = 0): array {
        return compact('permission', 'label', 'icon', 'href', 'active', 'badge');
    };
    $sidebarAllowedItems = function (array $items) use ($sidebarCan): array {
        return array_values(array_filter($items, fn (array $item): bool => $sidebarCan($item['permission'])));
    };

    $sidebarUnreadCount = $sidebarCan('notifications.view')
        ? $sidebarUser->unreadNotifications()->count()
        : 0;

    $sidebarInventoryItems = [];
    foreach ([
        ['assets', 'bi-pc-display', 'IT Assets'],
        ['categories', 'bi-tags', 'Categories'],
        ['consumables', 'bi-box-seam', 'Stock & Consumables'],
        ['assignments', 'bi-person-check', 'Assignments'],
        ['transfers', 'bi-arrow-left-right', 'Transfers'],
        ['software', 'bi-key', 'Software & Licenses'],
        ['disposals', 'bi-recycle', 'Asset Disposal'],
    ] as [$key, $icon, $label]) {
        $permission = config("inventory.resources.$key.permission") . '.view';

        // Keep the existing separate catalog entry for users who can view software.
        if ($key === 'software' && $sidebarCan('software.view')) {
            $sidebarInventoryItems[] = $sidebarItem(
                'software.view',
                'License Catalog & Renewals',
                'bi-key-fill',
                route('software-catalog.index'),
                request()->routeIs('software-catalog.*')
            );
        }

        if ($sidebarCan($permission)) {
            $sidebarInventoryItems[] = $sidebarItem(
                $permission,
                $label,
                $icon,
                route('inventory.index', $key),
                request()->is('manage/' . $key . '*')
            );
        }
    }
    if ($sidebarCan('consumables.view')) {
        $sidebarInventoryItems[] = $sidebarItem(
            'consumables.view', 'Stock Counts', 'bi-clipboard2-data',
            route('stock-counts.index'), request()->routeIs('stock-counts.*')
        );
    }
    $auditViewPermission = config('inventory.resources.audits.permission') . '.view';
    if ($sidebarCan($auditViewPermission)) {
        $sidebarInventoryItems[] = $sidebarItem(
            $auditViewPermission, 'Physical Inventory', 'bi-clipboard-check',
            route('inventory.index', 'audits'), request()->is('manage/audits*')
        );
    }

    $sidebarGroups = [
        'workspace' => [
            'label' => 'WORKSPACE', 'icon' => 'bi-house-door',
            'items' => $sidebarAllowedItems([
                $sidebarItem('system.access', 'Dashboard', 'bi-grid-1x2', route('dashboard'), request()->routeIs('dashboard')),
                $sidebarItem('portal.self', 'My equipment', 'bi-laptop', route('portal.index'), request()->routeIs('portal.*')),
                $sidebarItem('notifications.view', 'Notifications', 'bi-bell', route('notifications.index'), request()->routeIs('notifications.*'), $sidebarUnreadCount),
            ]),
        ],
        'inventory' => [
            'label' => 'INVENTORY', 'icon' => 'bi-boxes',
            'items' => $sidebarInventoryItems,
        ],
        'operations' => [
            'label' => 'OPERATIONS', 'icon' => 'bi-gear-wide-connected',
            'items' => $sidebarAllowedItems([
                $sidebarItem(config('inventory.resources.maintenance.permission') . '.view', 'Maintenance', 'bi-wrench-adjustable', route('inventory.index', 'maintenance'), request()->is('manage/maintenance*')),
                $sidebarItem(config('inventory.resources.tickets.permission') . '.view', 'Helpdesk', 'bi-headset', route('inventory.index', 'tickets'), request()->is('manage/tickets*')),
                $sidebarItem(config('inventory.resources.procurements.permission') . '.view', 'Procurement', 'bi-receipt', route('inventory.index', 'procurements'), request()->is('manage/procurements*')),
                $sidebarItem('maintenance.view', 'Maintenance Schedules', 'bi-calendar2-check', route('maintenance-schedules.index'), request()->routeIs('maintenance-schedules.*')),
                $sidebarItem('procurements.view', 'Purchase Requests', 'bi-file-earmark-text', route('procurement.requests.index'), request()->routeIs('procurement.requests.*')),
                $sidebarItem('approvals.view', 'Approval Center', 'bi-check2-square', route('approvals.index'), request()->routeIs('approvals.*')),
            ]),
        ],
        'organization' => [
            'label' => 'ORGANIZATION', 'icon' => 'bi-building',
            'items' => $sidebarAllowedItems([
                $sidebarItem(config('inventory.resources.employees.permission') . '.view', 'Employees', 'bi-people', route('inventory.index', 'employees'), request()->is('manage/employees*')),
                $sidebarItem(config('inventory.resources.departments.permission') . '.view', 'Departments', 'bi-diagram-3', route('inventory.index', 'departments'), request()->is('manage/departments*')),
                $sidebarItem(config('inventory.resources.locations.permission') . '.view', 'Locations', 'bi-geo-alt', route('inventory.index', 'locations'), request()->is('manage/locations*')),
                $sidebarItem(config('inventory.resources.suppliers.permission') . '.view', 'Suppliers', 'bi-truck', route('inventory.index', 'suppliers'), request()->is('manage/suppliers*')),
            ]),
        ],
        'insights' => [
            'label' => 'INSIGHTS', 'icon' => 'bi-graph-up-arrow',
            'items' => $sidebarAllowedItems([
                $sidebarItem('assets.view', 'Scan asset', 'bi-qr-code-scan', route('scan.index'), request()->routeIs('scan.*')),
                $sidebarItem('documents.view', 'Document Repository', 'bi-folder2-open', route('documents.index'), request()->routeIs('documents.index')),
                $sidebarItem('reports.view', 'Reports', 'bi-bar-chart-line', route('reports.index'), request()->routeIs('reports.*')),
                $sidebarItem('audit.view', 'Audit Trail', 'bi-clock-history', route('activity.index'), request()->routeIs('activity.*')),
            ]),
        ],
        'administration' => [
            'label' => 'ADMINISTRATION', 'icon' => 'bi-shield-lock',
            'items' => $sidebarAllowedItems([
                $sidebarItem('approvals.manage', 'Approval Flows', 'bi-diagram-2', route('admin.approval-flows'), request()->routeIs('admin.approval-flows*')),
                $sidebarItem('offices.view', 'Office Administration', 'bi-buildings', route('admin.offices'), request()->routeIs('admin.offices*')),
                $sidebarItem('users.view', 'User Management', 'bi-person-gear', route('admin.users'), request()->routeIs('admin.users')),
                $sidebarItem('users.manage', 'Roles & Permissions', 'bi-shield-lock', route('admin.roles'), request()->routeIs('admin.roles')),
                $sidebarItem('settings.manage', 'System Settings', 'bi-sliders', route('settings.index'), request()->routeIs('settings.*')),
                $sidebarItem('backups.manage', 'Backups', 'bi-database-lock', route('backups.index'), request()->routeIs('backups.*')),
            ]),
        ],
    ];
?>
<aside class="sidebar" id="sidebar">
 <div class="brand">
 <button type="button" class="brand-trigger" id="sidebarBrandToggle" aria-label="Collapse sidebar" aria-controls="sidebar" aria-expanded="true" title="Collapse sidebar">
  <?php if(!empty($systemBranding['logo_url'])): ?>
   <span class="brand-mark--image" title="<?php echo e($systemBranding['name'] ?? 'IT Inventory Management System'); ?>"><img src="<?php echo e($systemBranding['logo_url']); ?>" alt="<?php echo e($systemBranding['short_name'] ?? 'System'); ?>" decoding="async"></span>
  <?php else: ?>
   <span class="brand-mark" title="<?php echo e($systemBranding['name'] ?? 'IT Inventory Management System'); ?>"><?php echo e(strtoupper(substr($systemBranding['short_name'] ?? 'IT', 0, 3))); ?></span>
  <?php endif; ?>
  <span class="brand-copy"><strong><?php echo e($systemBranding['short_name'] ?? 'IT Inventory'); ?></strong><span>Management Portal</span></span>
 </button>
 <button class="sidebar-close d-lg-none" id="sidebarClose" type="button" aria-label="Close menu"><i class="bi bi-x-lg"></i></button>
</div>
 <div class="sidebar-scroll">
  <?php $__currentLoopData = $sidebarGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupKey => $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
   <?php if(count($group['items']) === 1): ?>
    <?php ($item = $group['items'][0]); ?>
    <a class="nav-link <?php echo e($item['active'] ? 'active' : ''); ?>" href="<?php echo e($item['href']); ?>"><i class="bi <?php echo e($item['icon']); ?>"></i><span><?php echo e($item['label']); ?></span><?php if($item['badge'] > 0): ?><span class="nav-count"><?php echo e($item['badge'] > 99 ? '99+' : $item['badge']); ?></span><?php endif; ?></a>
   <?php elseif(count($group['items']) > 1): ?>
    <?php ($groupTitle = ucfirst($groupKey)); ?>
    <section class="sidebar-group" data-sidebar-group="<?php echo e($groupKey); ?>">
     <button type="button" class="nav-group-toggle" id="sidebarGroup<?php echo e($groupTitle); ?>Toggle" aria-expanded="true" aria-controls="sidebarGroup<?php echo e($groupTitle); ?>" data-sidebar-tooltip="<?php echo e(ucfirst($group['label'])); ?>">
      <span class="nav-group-label"><i class="bi <?php echo e($group['icon']); ?>" aria-hidden="true"></i><span class="nav-label"><?php echo e($group['label']); ?></span></span><i class="bi bi-chevron-down nav-group-chevron" aria-hidden="true"></i>
     </button>
     <div class="nav-group-panel" id="sidebarGroup<?php echo e($groupTitle); ?>" aria-labelledby="sidebarGroup<?php echo e($groupTitle); ?>Toggle" data-sidebar-group-label="<?php echo e($group['label']); ?>">
      <div class="nav-group-inner">
       <?php $__currentLoopData = $group['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a class="nav-link <?php echo e($item['active'] ? 'active' : ''); ?>" href="<?php echo e($item['href']); ?>"><i class="bi <?php echo e($item['icon']); ?>"></i><span><?php echo e($item['label']); ?></span><?php if($item['badge'] > 0): ?><span class="nav-count"><?php echo e($item['badge'] > 99 ? '99+' : $item['badge']); ?></span><?php endif; ?></a>
       <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
     </div>
    </section>
   <?php endif; ?>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </div>
</aside><div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<main class="main-area">
 <?php ($unreadNotificationCount = $sidebarUnreadCount); ?>
 <header class="topbar">
  <div class="topbar-leading">
   <button type="button" class="icon-button icon-button-plain sidebar-toggle" id="sidebarToggle" aria-label="Open navigation menu" aria-controls="sidebar" aria-expanded="false" title="Open navigation menu">
    <i class="bi bi-list" id="sidebarToggleIcon" aria-hidden="true"></i>
   </button>
   <div class="breadcrumb-line"><span>Workspace</span><i class="bi bi-chevron-right"></i><strong><?php echo $__env->yieldContent('breadcrumb','Dashboard'); ?></strong></div>
  </div>
  <div class="topbar-actions">
   <?php if(auth()->user()->hasPermission('notifications.view')): ?>
   <div class="dropdown notification-dropdown">
    <button type="button" class="icon-button icon-button-plain notification-trigger" id="notificationToggle" data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="outside" aria-label="Open notifications" aria-controls="topbarNotifications" aria-expanded="false" title="Notifications">
     <i class="bi bi-bell" aria-hidden="true"></i>
     <?php if($unreadNotificationCount): ?><span class="notification-count" aria-label="<?php echo e($unreadNotificationCount); ?> unread notifications"><?php echo e($unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount); ?></span><?php endif; ?>
    </button>
    <div class="dropdown-menu dropdown-menu-end notification-menu shadow-sm border-0" id="topbarNotifications" aria-labelledby="notificationToggle">
     <div class="notification-menu-head"><strong>Notifications</strong><a href="<?php echo e(route('notifications.index')); ?>">View all</a></div>
     <?php $__empty_1 = true; $__currentLoopData = auth()->user()->unreadNotifications()->latest()->limit(5)->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <form method="POST" action="<?php echo e(route('notifications.read',$notification->id)); ?>" class="notification-mini-form"><?php echo csrf_field(); ?><button class="notification-mini" type="submit"><strong><?php echo e($notification->data['title'] ?? 'System alert'); ?></strong><span><?php echo e($notification->data['message'] ?? ''); ?></span><small><?php echo e($notification->created_at?->diffForHumans()); ?></small></button></form>
     <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <div class="notification-empty">No unread notifications.</div>
     <?php endif; ?>
    </div>
   </div>
   <?php endif; ?>
   <div class="topbar-date d-none d-md-flex"><i class="bi bi-calendar3" aria-hidden="true"></i><?php echo e(now()->format(config('app.date_format', 'D, d M Y'))); ?> <span class="topbar-time"><?php echo e(now()->format(config('app.time_format', 'H:i'))); ?></span></div>
   <div class="topbar-divider"></div>
   <div class="dropdown profile-dropdown">
    <button type="button" class="profile-trigger" id="profileToggle" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-controls="profileMenu">
     <span class="avatar"><?php echo e(strtoupper(substr(auth()->user()->name,0,1))); ?></span>
     <span class="profile-meta d-none d-sm-flex"><strong><?php echo e(auth()->user()->name); ?></strong><small><?php echo e(auth()->user()->roles->first()?->name ?? 'User'); ?></small></span>
     <i class="bi bi-chevron-down small" aria-hidden="true"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" id="profileMenu" aria-labelledby="profileToggle">
     <li><span class="dropdown-item-text small text-secondary"><?php echo e(auth()->user()->email); ?></span></li>
     <li><hr class="dropdown-divider"></li>
     <li><a class="dropdown-item" href="<?php echo e(route('profile.edit')); ?>"><i class="bi bi-person-circle me-2" aria-hidden="true"></i>My Profile</a></li>
     <li><hr class="dropdown-divider"></li>
     <li><form method="POST" action="<?php echo e(route('logout')); ?>"><?php echo csrf_field(); ?><button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Sign out</button></form></li>
    </ul>
   </div>
  </div>
 </header>
 <section class="page-content">
  <?php if(session('success')): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle me-2"></i><?php echo e(session('success')); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php if($errors->any()): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Review the form details.</strong><ul class="mb-0 mt-1"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php echo $__env->yieldContent('content'); ?>
 </section><footer class="app-footer"><span><?php echo e($systemBranding['name'] ?? 'IT Inventory Management'); ?></span><span>Internal use · <?php echo e(now()->format('Y')); ?></span></footer>
</main></div>
<link href="<?php echo e(asset('css/module-ui-standard.css')); ?>?v=<?php echo e(filemtime(public_path('css/module-ui-standard.css'))); ?>" rel="stylesheet"><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script><script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script><?php if(!file_exists(public_path('build/manifest.json'))): ?><script src="<?php echo e(asset('js/app.js')); ?>"></script><?php endif; ?>@stack('scripts')
</body></html>
<?php /**PATH C:\laragon\www\it-inventory\resources\views/layouts/app.blade.php ENDPATH**/ ?>