<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title','Dashboard') · {{ config('app.name') }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">@if(file_exists(public_path('build/manifest.json')))@vite(['resources/css/app.css','resources/js/app.js'])@else<link href="{{ asset('css/app.css') }}" rel="stylesheet">@endif
@stack('styles')</head><body>
<div class="app-shell" id="appShell" style="--blue: {{ $systemBranding['primary'] ?? '#315f9f' }}; --green: {{ $systemBranding['accent'] ?? '#0caa6a' }};">
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
@php
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
@endphp
<aside class="sidebar" id="sidebar">
 <div class="brand">
 <button type="button" class="brand-trigger" id="sidebarBrandToggle" aria-label="Collapse sidebar" aria-controls="sidebar" aria-expanded="true" title="Collapse sidebar">
  @if(!empty($systemBranding['logo_url']))
   <span class="brand-mark--image" title="{{ $systemBranding['name'] ?? 'IT Inventory Management System' }}"><img src="{{ $systemBranding['logo_url'] }}" alt="{{ $systemBranding['short_name'] ?? 'System' }}" decoding="async"></span>
  @else
   <span class="brand-mark" title="{{ $systemBranding['name'] ?? 'IT Inventory Management System' }}">{{ strtoupper(substr($systemBranding['short_name'] ?? 'IT', 0, 3)) }}</span>
  @endif
  <span class="brand-copy"><strong>{{ $systemBranding['short_name'] ?? 'IT Inventory' }}</strong><span>Management Portal</span></span>
 </button>
 <button class="sidebar-close d-lg-none" id="sidebarClose" type="button" aria-label="Close menu"><i class="bi bi-x-lg"></i></button>
</div>
 <div class="sidebar-scroll">
  @foreach($sidebarGroups as $groupKey => $group)
   @if(count($group['items']) === 1)
    @php($item = $group['items'][0])
    <a class="nav-link {{ $item['active'] ? 'active' : '' }}" href="{{ $item['href'] }}"><i class="bi {{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span>@if($item['badge'] > 0)<span class="nav-count">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>@endif</a>
   @elseif(count($group['items']) > 1)
    @php($groupTitle = ucfirst($groupKey))
    <section class="sidebar-group" data-sidebar-group="{{ $groupKey }}">
     <button type="button" class="nav-group-toggle" id="sidebarGroup{{ $groupTitle }}Toggle" aria-expanded="true" aria-controls="sidebarGroup{{ $groupTitle }}" data-sidebar-tooltip="{{ ucfirst($group['label']) }}">
      <span class="nav-group-label"><i class="bi {{ $group['icon'] }}" aria-hidden="true"></i><span class="nav-label">{{ $group['label'] }}</span></span><i class="bi bi-chevron-down nav-group-chevron" aria-hidden="true"></i>
     </button>
     <div class="nav-group-panel" id="sidebarGroup{{ $groupTitle }}" aria-labelledby="sidebarGroup{{ $groupTitle }}Toggle">
      <div class="nav-group-inner">
       @foreach($group['items'] as $item)
        <a class="nav-link {{ $item['active'] ? 'active' : '' }}" href="{{ $item['href'] }}"><i class="bi {{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span>@if($item['badge'] > 0)<span class="nav-count">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>@endif</a>
       @endforeach
      </div>
     </div>
    </section>
   @endif
  @endforeach
 </div>
</aside><div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<main class="main-area">
 @php($unreadNotificationCount = $sidebarUnreadCount)
 <header class="topbar">
  <div class="topbar-leading">
   <button type="button" class="icon-button icon-button-plain sidebar-toggle" id="sidebarToggle" aria-label="Open navigation menu" aria-controls="sidebar" aria-expanded="false" title="Open navigation menu">
    <i class="bi bi-list" id="sidebarToggleIcon" aria-hidden="true"></i>
   </button>
   <div class="breadcrumb-line"><span>Workspace</span><i class="bi bi-chevron-right"></i><strong>@yield('breadcrumb','Dashboard')</strong></div>
  </div>
  <div class="topbar-actions">
   @if(auth()->user()->hasPermission('notifications.view'))
   <div class="dropdown notification-dropdown">
    <button type="button" class="icon-button icon-button-plain notification-trigger" id="notificationToggle" data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="outside" aria-label="Open notifications" aria-controls="topbarNotifications" aria-expanded="false" title="Notifications">
     <i class="bi bi-bell" aria-hidden="true"></i>
     @if($unreadNotificationCount)<span class="notification-count" aria-label="{{ $unreadNotificationCount }} unread notifications">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>@endif
    </button>
    <div class="dropdown-menu dropdown-menu-end notification-menu shadow-sm border-0" id="topbarNotifications" aria-labelledby="notificationToggle">
     <div class="notification-menu-head"><strong>Notifications</strong><a href="{{ route('notifications.index') }}">View all</a></div>
     @forelse(auth()->user()->unreadNotifications()->latest()->limit(5)->get() as $notification)
      <form method="POST" action="{{ route('notifications.read',$notification->id) }}" class="notification-mini-form">@csrf<button class="notification-mini" type="submit"><strong>{{ $notification->data['title'] ?? 'System alert' }}</strong><span>{{ $notification->data['message'] ?? '' }}</span><small>{{ $notification->created_at?->diffForHumans() }}</small></button></form>
     @empty
      <div class="notification-empty">No unread notifications.</div>
     @endforelse
    </div>
   </div>
   @endif
   <div class="topbar-date d-none d-md-flex"><i class="bi bi-calendar3" aria-hidden="true"></i>{{ now()->format(config('app.date_format', 'D, d M Y')) }} <span class="topbar-time">{{ now()->format(config('app.time_format', 'H:i')) }}</span></div>
   <div class="topbar-divider"></div>
   <div class="dropdown profile-dropdown">
    <button type="button" class="profile-trigger" id="profileToggle" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-controls="profileMenu">
     <span class="avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span>
     <span class="profile-meta d-none d-sm-flex"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->roles->first()?->name ?? 'User' }}</small></span>
     <i class="bi bi-chevron-down small" aria-hidden="true"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" id="profileMenu" aria-labelledby="profileToggle">
     <li><span class="dropdown-item-text small text-secondary">{{ auth()->user()->email }}</span></li>
     <li><hr class="dropdown-divider"></li>
     <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person-circle me-2" aria-hidden="true"></i>My Profile</a></li>
     <li><hr class="dropdown-divider"></li>
     <li><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Sign out</button></form></li>
    </ul>
   </div>
  </div>
 </header>
 <section class="page-content">
  @if(session('success'))<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
  @if($errors->any())<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Review the form details.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
  @yield('content')
 </section><footer class="app-footer"><span>{{ $systemBranding['name'] ?? 'IT Inventory Management' }}</span><span>Internal use · {{ now()->format('Y') }}</span></footer>
</main></div>
<link href="{{ asset('css/module-ui-standard.css') }}?v={{ filemtime(public_path('css/module-ui-standard.css')) }}" rel="stylesheet"><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script><script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>@if(!file_exists(public_path('build/manifest.json')))<script src="{{ asset('js/app.js') }}"></script>@endif@stack('scripts')
</body></html>
