<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Department;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\MaintenanceRecord;
use App\Models\Office;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SoftwareLicense;
use App\Models\Supplier;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\StockLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'assets' => 'IT Assets', 'categories' => 'Categories', 'assignments' => 'Assignment',
            'consumables' => 'Stock and Consumables', 'software' => 'Software and Licenses',
            'procurements' => 'Procurement', 'maintenance' => 'Maintenance', 'tickets' => 'Helpdesk',
            'employees' => 'Employees', 'departments' => 'Departments', 'locations' => 'Locations',
            'audits' => 'Physical Inventory', 'transfers' => 'Transfers and Turnover',
            'disposals' => 'Disposal', 'suppliers' => 'Suppliers', 'documents' => 'Documents',
            'reports' => 'Reports', 'users' => 'Users and Access', 'roles' => 'Roles and Permissions',
            'approvals' => 'Approvals', 'notifications' => 'Notifications', 'settings' => 'Settings',
            'audit' => 'Audit Trail', 'backups' => 'Backup', 'portal' => 'Employee Portal',
        ];

        $permissionNames = [];
        foreach ($modules as $module => $label) {
            foreach (['view', 'create', 'update', 'delete', 'manage', 'export'] as $action) {
                $name = $module.'.'.$action;
                Permission::updateOrCreate(
                    ['name' => $name],
                    ['label' => ucfirst($action).' '.$label, 'module' => $module]
                );
                $permissionNames[] = $name;
            }
        }

        $specialPermissions = [
            'system.access' => ['Access the application', 'system'],
            'transfers.approve' => ['Approve asset transfers', 'transfers'],
            'approvals.approve' => ['Approve assigned workflow requests', 'approvals'],
            'approvals.override' => ['Override approver assignment', 'approvals'],
            'portal.self' => ['Access employee self-service', 'portal'],
            'settings.manage' => ['Manage application settings', 'settings'],
            'notifications.view' => ['View notifications', 'notifications'],
            'backups.manage' => ['Create and manage database backups', 'backups'],
            'reports.export' => ['Export reports', 'reports'],
            'audit.view' => ['View audit history', 'audit'],
            'offices.view' => ['View office administration', 'offices'],
            'offices.manage' => ['Manage offices', 'offices'],
        ];
        foreach ($specialPermissions as $name => [$label, $module]) {
            Permission::updateOrCreate(['name' => $name], ['label' => $label, 'module' => $module]);
            $permissionNames[] = $name;
        }

        $allPermissions = Permission::query()->pluck('id', 'name');
        $inventoryModules = [
            'assets', 'categories', 'employees', 'departments', 'locations', 'suppliers',
            'consumables', 'software', 'maintenance', 'tickets', 'procurements', 'assignments',
            'transfers', 'audits', 'disposals', 'documents',
        ];
        $allModuleNames = array_keys($modules);

        $roleDefinitions = [
            ['Super Admin', 'super-admin', 'Unrestricted system administration.', $allModuleNames, ['view','create','update','delete','manage','export'], ['approvals.approve','approvals.override','transfers.approve','portal.self','settings.manage','notifications.view','backups.manage','reports.export','audit.view','system.access','offices.view','offices.manage']],
            ['System Admin', 'system-admin', 'Manage accounts, configuration, access, and application operations.', $allModuleNames, ['view','create','update','delete','manage','export'], ['approvals.approve','approvals.override','transfers.approve','settings.manage','notifications.view','backups.manage','reports.export','audit.view','system.access','offices.view','offices.manage']],
            ['IT Inventory Manager', 'inventory-manager', 'Manage IT asset lifecycle and inventory operations.', $inventoryModules, ['view','create','update','delete','manage','export'], ['approvals.approve','transfers.approve','notifications.view','reports.export','audit.view','system.access']],
            ['Property Custodian', 'property-custodian', 'Manage asset issuance, accountability, returns, and turnover.', ['assets','categories','employees','departments','locations','assignments','transfers','audits','disposals','documents','reports'], ['view','create','update','delete','manage','export'], ['transfers.approve','notifications.view','reports.export','system.access']],
            ['Technician', 'it-technician', 'Handle maintenance, technical support, and assigned tickets.', ['assets','categories','locations','consumables','software','maintenance','tickets','documents'], ['view','create','update','manage'], ['notifications.view','system.access']],
            ['Procurement Officer', 'procurement-officer', 'Manage purchase requests, supplier quotations, orders, and receiving.', ['assets','categories','suppliers','consumables','procurements','documents','reports'], ['view','create','update','delete','manage','export'], ['approvals.approve','notifications.view','reports.export','system.access']],
            ['Department Head', 'department-head', 'Review departmental inventory and assigned approval requests.', ['assets','categories','employees','departments','locations','assignments','transfers','audits','tickets','procurements','reports','documents'], ['view','create','manage','export'], ['approvals.approve','portal.self','notifications.view','reports.export','system.access']],
            ['Department Staff', 'department-staff', 'View permitted departmental records and submit service requests.', ['assets','categories','employees','departments','locations','assignments','tickets','documents'], ['view','create'], ['portal.self','notifications.view','system.access']],
            ['Auditor', 'auditor', 'Read-only inventory access and audit reporting.', $allModuleNames, ['view','export'], ['reports.export','audit.view','system.access']],
            ['Employee', 'employee', 'Access assigned equipment, personal requests, and notifications.', ['assets','assignments','tickets','documents'], ['view','create'], ['portal.self','notifications.view','system.access']],
        ];

        foreach ($roleDefinitions as [$name, $slug, $description, $roleModules, $actions, $special]) {
            $role = Role::updateOrCreate(['slug' => $slug], ['name' => $name, 'description' => $description]);
            $grants = [];
            foreach ($roleModules as $module) {
                foreach ($actions as $action) {
                    $permissionName = $module.'.'.$action;
                    if (isset($allPermissions[$permissionName])) {
                        $grants[] = $allPermissions[$permissionName];
                    }
                }
            }
            foreach ($special as $permissionName) {
                if (isset($allPermissions[$permissionName])) {
                    $grants[] = $allPermissions[$permissionName];
                }
            }
            $role->permissions()->sync(array_values(array_unique($grants)));
        }

        // Initialize a default office for fresh installs and ensure seeded
        // sample records are visible to office-scoped non-super-admin users.
        $office = Office::withTrashed()->firstOrNew(['code' => 'MAIN']);
        if (! $office->exists) {
            $office->fill([
                'name' => 'Main Office',
                'short_name' => 'MAIN',
                'is_active' => true,
            ])->save();
        } else {
            if ($office->trashed()) {
                $office->restore();
            }
            if (! $office->is_active) {
                $office->forceFill(['is_active' => true])->save();
            }
        }

        $ensureOffice = static function ($record) use ($office) {
            if ($record && ! $record->getAttribute('office_id')) {
                $record->forceFill(['office_id' => $office->id])->save();
            }

            return $record;
        };

        $department = $ensureOffice(Department::firstOrCreate(['code' => 'IT'], ['name' => 'Information Technology', 'head_name' => 'IT Office']));
        $location = $ensureOffice(Location::firstOrCreate(['name' => 'Main IT Office'], ['building' => 'Administration Building', 'floor' => '2', 'room' => 'IT-201', 'type' => 'room']));
        $computerCategory = $ensureOffice(AssetCategory::firstOrCreate(['code' => 'COMP'], ['name' => 'Computer Equipment', 'default_useful_life_months' => 48, 'useful_life_months' => 48, 'type' => 'asset']));
        $networkCategory = $ensureOffice(AssetCategory::firstOrCreate(['code' => 'NET'], ['name' => 'Network Equipment', 'default_useful_life_months' => 60, 'useful_life_months' => 60, 'type' => 'asset']));
        $supplier = $ensureOffice(Supplier::firstOrCreate(['company_name' => 'Northstar Technology Supply'], ['contact_person' => 'Sales Desk', 'email' => 'sales@example.local', 'phone' => '000-0000']));

        $employee = $ensureOffice(Employee::firstOrCreate(['employee_no' => 'EMP-0001'], [
            'first_name' => 'Alex', 'last_name' => 'Morgan', 'email' => 'alex.morgan@example.local',
            'position' => 'IT Officer', 'department_id' => $department->id,
        ]));

        $asset = $ensureOffice(Asset::firstOrCreate(['asset_tag' => 'IT-2026-0001'], [
            'name' => 'Engineering Workstation', 'serial_number' => 'WS-10482', 'category_id' => $computerCategory->id,
            'brand' => 'Dell', 'model' => 'OptiPlex', 'specifications' => 'Intel Core i7 • 16 GB RAM • 512 GB SSD',
            'purchase_date' => now()->subMonths(4)->toDateString(), 'purchase_cost' => 68500,
            'supplier_id' => $supplier->id, 'department_id' => $department->id, 'location_id' => $location->id,
            'condition' => 'Good', 'status' => 'available',
        ]));

        $maintenanceAsset = $ensureOffice(Asset::firstOrCreate(['asset_tag' => 'IT-2026-0002'], [
            'name' => 'Network Switch 24-Port', 'serial_number' => 'SW-24091', 'category_id' => $networkCategory->id,
            'brand' => 'Cisco', 'model' => 'Catalyst 1000', 'purchase_cost' => 42000,
            'department_id' => $department->id, 'location_id' => $location->id, 'condition' => 'Good', 'status' => 'maintenance',
        ]));

        $maintenanceRecord = $ensureOffice(MaintenanceRecord::firstOrCreate(['work_order_no' => 'MWO-2026-0001'], [
            'asset_id' => $maintenanceAsset->id, 'maintenance_type' => 'preventive',
            'problem' => 'Scheduled inspection and firmware review.', 'priority' => 'normal',
            'assigned_technician' => 'IT Support Team', 'scheduled_at' => today(), 'status' => 'in_progress',
        ]));

        $stockItem = $ensureOffice(InventoryItem::firstOrCreate(['sku' => 'CAB-CAT6-001'], [
            'name' => 'CAT6 LAN Cable', 'unit' => 'piece', 'quantity_on_hand' => 18,
            'reorder_level' => 20, 'min_level' => 20, 'unit_cost' => 180, 'supplier_id' => $supplier->id,
        ]));

        // Bring pre-ledger sample or legacy stock into the new append-only ledger
        // once. The current cached balance is reset and then re-received through
        // the same transaction service used by live stock operations.
        if (! $stockItem->movements()->exists() && (int) $stockItem->quantity_on_hand > 0) {
            $openingBalance = (int) $stockItem->quantity_on_hand;
            DB::transaction(function () use ($stockItem, $openingBalance): void {
                $locked = InventoryItem::query()->whereKey($stockItem->id)->lockForUpdate()->firstOrFail();
                if ($locked->movements()->exists()) {
                    return;
                }
                $locked->forceFill(['quantity_on_hand' => 0])->save();
                app(StockLedgerService::class)->move(
                    $locked->id,
                    'receive',
                    $openingBalance,
                    auth()->id(),
                    'SEED-OPENING-BALANCE',
                    'Opening balance migrated into the append-only stock movement ledger.'
                );
            }, 3);
        }
        $softwareLicense = $ensureOffice(SoftwareLicense::firstOrCreate(['software_name' => 'Endpoint Security Suite'], [
            'publisher' => 'Example Security', 'license_type' => 'subscription', 'quantity_purchased' => 50,
            'quantity_assigned' => 0, 'expires_at' => now()->addDays(28)->toDateString(), 'purchase_cost' => 95000,
        ]));
        $supportTicket = $ensureOffice(SupportTicket::firstOrCreate(['ticket_no' => 'HD-2026-0001'], [
            'subject' => 'Workstation connectivity review', 'requester_name' => 'Alex Morgan',
            'requester_email' => 'alex.morgan@example.local', 'category' => 'network',
            'description' => 'Intermittent wired network connection.', 'priority' => 'normal', 'status' => 'open',
        ]));

        $admin = $ensureOffice(User::firstOrCreate(['email' => 'admin@inventory.local'], [
            'name' => 'System Administrator', 'password' => Hash::make('ChangeMe123!'),
            'is_active' => true, 'department_id' => $department->id, 'must_change_password' => true,
        ]));
        $admin->roles()->sync([Role::where('slug', 'super-admin')->value('id')]);

        // Add the standard category catalog idempotently; existing category
        // settings and asset/stock records are not overwritten.
        $this->call(AssetCategoryCatalogSeeder::class);
    }
}
