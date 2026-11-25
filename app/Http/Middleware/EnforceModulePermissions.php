<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces module/action permissions for every authenticated application route.
 *
 * Explicit controller-level checks remain in place as defense in depth.
 */
class EnforceModulePermissions
{
    private const RESOURCE_ALIASES = [
        'assets' => 'assets',
        'categories' => 'categories',
        'employees' => 'employees',
        'departments' => 'departments',
        'locations' => 'locations',
        'suppliers' => 'suppliers',
        'consumables' => 'consumables',
        'software' => 'software',
        'maintenance' => 'maintenance',
        'tickets' => 'tickets',
        'procurements' => 'procurements',
        'assignments' => 'assignments',
        'transfers' => 'transfers',
        'audits' => 'audits',
        'disposals' => 'disposals',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        $permission = $this->permissionFor($request);
        if ($permission !== null) {
            abort_unless($user->hasPermission($permission), 403);
        }

        return $next($request);
    }

    private function permissionFor(Request $request): ?string
    {
        $name = (string) $request->route()?->getName();
        $method = strtoupper($request->method());

        // Authentication/security routes are deliberately not module-gated.
        if ($name === '' || in_array($name, [
            'login', 'login.store', 'logout', 'mfa.challenge',
            'mfa.challenge.verify', 'password.change', 'password.update',
            'mfa.settings', 'mfa.enable', 'mfa.disable', 'branding.logo',
        ], true)) {
            return null;
        }

        if ($name === 'dashboard') return 'system.access';

        if (str_starts_with($name, 'inventory.')) {
            // The route name is shared (inventory.index/create/etc.); the
            // actual module is supplied by the constrained {resource} route parameter.
            $resource = (string) $request->route('resource');
            $action = explode('.', $name)[1] ?? 'index';
            $module = self::RESOURCE_ALIASES[$resource] ?? null;
            if (! $module) return 'system.access';

            $verb = match ($action) {
                'index', 'show', 'bulk-labels' => 'view',
                'create', 'store' => 'create',
                'edit', 'update' => 'update',
                'destroy' => 'delete',
                default => match ($method) {
                    'GET', 'HEAD' => 'view',
                    'POST' => 'create',
                    'PUT', 'PATCH' => 'update',
                    'DELETE' => 'delete',
                    default => 'manage',
                },
            };

            return $module.'.'.$verb;
        }

        $exact = [
            'software-catalog.index' => 'software.view',
            'software-catalog.store' => 'software.create',
            'software-catalog.update' => 'software.update',
            'software-catalog.licenses.store' => 'software.manage',
            'software-catalog.licenses.renew' => 'software.manage',
            'software-catalog.licenses.assign' => 'software.manage',
            'software-catalog.licenses.revoke' => 'software.manage',

            'assets.bulk-labels' => 'assets.view',
            'assets.assign' => 'assignments.create',
            'assignments.return' => 'assignments.update',
            'assignments.signature' => 'assignments.view',
            'assignments.receipt' => 'assignments.view',
            'assets.transfer' => 'transfers.create',
            'transfers.approve' => 'transfers.approve',
            'transfers.reject' => 'transfers.approve',
            'consumables.movement' => 'consumables.update',
            'audits.verify' => 'audits.update',
            'audits.complete' => 'audits.update',
            'audits.scan' => 'audits.view',

            'imports.template' => null,
            'imports.store' => null, // resolved below using the route resource
            'documents.index' => 'documents.view',
            'documents.preview' => 'documents.view',
            'documents.download' => 'documents.view',
            'documents.store' => 'documents.create',
            'documents.destroy' => 'documents.delete',

            'stock-counts.index' => 'consumables.view',
            'stock-counts.show' => 'consumables.view',
            'stock-counts.store' => 'consumables.create',
            'stock-counts.items.update' => 'consumables.update',
            'stock-counts.submit' => 'consumables.update',
            'stock-counts.approve' => 'consumables.manage',
            'stock-counts.reject' => 'consumables.manage',

            'maintenance-schedules.index' => 'maintenance.view',
            'maintenance-schedules.store' => 'maintenance.create',
            'maintenance-schedules.toggle' => 'maintenance.manage',
            'maintenance-schedules.generate' => 'maintenance.manage',
            'maintenance-schedules.work-orders.complete' => 'maintenance.update',
            'maintenance-schedules.work-orders.cancel' => 'maintenance.update',

            'procurement.requests.index' => 'procurements.view',
            'procurement.requests.store' => 'procurements.create',
            'procurement.requests.show' => 'procurements.view',
            'procurement.requests.submit' => 'procurements.update',
            'procurement.requests.quotations.store' => 'procurements.create',
            'procurement.requests.quotations.download' => 'procurements.view',
            'procurement.requests.quotations.select' => 'procurements.manage',
            'procurement.requests.purchase-order' => 'procurements.manage',
            'procurement.receiving.store' => 'procurements.update',
            'procurement.receiving.inspect' => 'procurements.manage',
            'suppliers.evaluations.store' => 'suppliers.update',
            'employees.offboard' => 'employees.manage',

            'approvals.index' => 'approvals.view',
            'approvals.submit' => 'approvals.create',
            'approvals.decide' => 'approvals.approve',
            'admin.approval-flows' => 'approvals.manage',
            'admin.approval-flows.store' => 'approvals.manage',
            'admin.approval-flows.steps.store' => 'approvals.manage',
            'admin.approval-flows.steps.destroy' => 'approvals.manage',
            'admin.approval-flows.toggle' => 'approvals.manage',
            'admin.approval-flows.destroy' => 'approvals.manage',

            'notifications.index' => 'notifications.view',
            'notifications.read-all' => 'notifications.view',
            'notifications.read' => 'notifications.view',
            'settings.index' => 'settings.manage',
            'settings.update' => 'settings.manage',
            'settings.test-email' => 'settings.manage',
            'backups.index' => 'backups.manage',
            'backups.runbook' => 'backups.manage',
            'backups.create' => 'backups.manage',
            'backups.download' => 'backups.manage',
            'backups.destroy' => 'backups.manage',

            'portal.index' => 'portal.self',
            'portal.tickets.store' => 'portal.self',
            'tickets.comments.store' => 'tickets.update',
            'licenses.assign' => 'software.manage',
            'licenses.return' => 'software.manage',
            'assets.disposal' => 'disposals.create',
            'disposals.complete' => 'disposals.manage',
            'maintenance.complete' => 'maintenance.update',
            'maintenance.cancel' => 'maintenance.update',
            'procurements.order' => 'procurements.manage',
            'procurements.receive' => 'procurements.update',
            'procurements.assets.store' => 'assets.create',

            'reports.index' => 'reports.view',
            'reports.export' => 'reports.export',
            'reports.assets.csv' => 'reports.export',
            'reports.stock.csv' => 'reports.export',
            'reports.depreciation.csv' => 'reports.export',
            'reports.maintenance.csv' => 'reports.export',
            'reports.tickets.csv' => 'reports.export',
            'reports.registers.csv' => 'reports.export',
            'activity.index' => 'audit.view',

            'admin.offices' => 'offices.view',
            'admin.offices.store' => 'offices.manage',
            'admin.offices.update' => 'offices.manage',
            'admin.offices.destroy' => 'offices.manage',
            'admin.users' => 'users.view',
            'admin.users.store' => 'users.manage',
            'admin.users.update' => 'users.manage',
            'admin.users.credentials' => 'users.manage',
            'admin.users.destroy' => 'users.manage',
            'admin.users.toggle' => 'users.manage',
            'admin.roles' => 'users.manage',
            'admin.roles.update' => 'users.manage',
        ];

        if (array_key_exists($name, $exact)) {
            if ($name === 'imports.template' || $name === 'imports.store') {
                $resource = (string) $request->route('resource');
                $module = self::RESOURCE_ALIASES[$resource] ?? null;
                if (! $module) return 'system.access';
                return $module.($name === 'imports.template' ? '.view' : '.create');
            }
            return $exact[$name];
        }

        // Unknown protected routes fail closed until an explicit permission mapping is added.
        return 'system.route.unmapped';
    }
}
