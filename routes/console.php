<?php

use App\Models\Approval;
use App\Models\Assignment;
use App\Models\Asset;
use App\Models\InventoryItem;
use App\Models\MaintenanceRecord;
use App\Models\SoftwareLicense;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Role;
use App\Services\AssetDepreciationService;
use App\Services\MaintenanceScheduleService;
use App\Services\DatabaseBackupService;
use Carbon\CarbonImmutable;
use App\Notifications\SystemAlert;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inventory:about', function () {
    $this->info('IT Inventory Management System — Laravel 13');
})->purpose('Display application information');


Artisan::command('inventory:doctor', function () {
    $checks = [];
    $checks[] = ['Application key', filled(config('app.key')), filled(config('app.key')) ? 'Configured' : 'Missing — run php artisan key:generate'];
    $checks[] = ['PHP version', version_compare(PHP_VERSION, '8.3.0', '>='), PHP_VERSION.' (Laravel 13 requires PHP 8.3+)'];

    foreach (['ctype','curl','dom','fileinfo','filter','gd','hash','iconv','mbstring','openssl','pdo','pdo_mysql','session','simplexml','tokenizer','xml','xmlreader','xmlwriter','zip','zlib'] as $extension) {
        $loaded = extension_loaded($extension);
        $checks[] = ['PHP extension: '.$extension, $loaded, $loaded ? 'Loaded' : 'Enable this extension in the active PHP CLI php.ini'];
    }

    $checks[] = ['Storage writable', is_writable(storage_path()), is_writable(storage_path()) ? storage_path() : 'Grant write access to storage/'];
    $checks[] = ['Bootstrap cache writable', is_writable(base_path('bootstrap/cache')), is_writable(base_path('bootstrap/cache')) ? 'Writable' : 'Grant write access to bootstrap/cache/'];
    $checks[] = ['Frontend assets', file_exists(public_path('build/manifest.json')) || (file_exists(public_path('css/app.css')) && file_exists(public_path('js/app.js'))), file_exists(public_path('build/manifest.json')) ? 'Vite production manifest found' : 'Using public CSS/JS fallback or build assets with npm run build'];
    $checks[] = ['PDF export dependency', class_exists(\Barryvdh\DomPDF\Facade\Pdf::class), class_exists(\Barryvdh\DomPDF\Facade\Pdf::class) ? 'Dompdf facade available' : 'Install report packages with Composer on the target machine'];
    $checks[] = ['Excel export dependency', class_exists(\Maatwebsite\Excel\Facades\Excel::class), class_exists(\Maatwebsite\Excel\Facades\Excel::class) ? 'Laravel Excel facade available' : 'Install report packages with Composer on the target machine'];

    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $checks[] = ['Database connection', true, 'Connected to '.config('database.default')];

        $requiredTables = [
            'users','roles','permissions','offices','departments','asset_categories','employees','locations','suppliers',
            'assets','assignments','asset_transfers','asset_histories','inventory_items','software_licenses','stock_movements',
            'stock_transactions','stock_counts','stock_count_items','software','licenses','license_assignments','software_license_assignments',
            'purchase_requests','purchase_request_items','quotations','purchase_orders','receiving_reports',
            'receiving_report_items','maintenance_schedules','work_orders','maintenance_records','support_tickets',
            'ticket_comments','inventory_audits','inventory_audit_items','asset_disposals','documents',
            'approval_flows','approval_flow_steps','approvals','login_histories','audit_logs','system_settings',
            'notifications','backup_records','depreciation_entries','supplier_evaluations','license_renewals','number_sequences',
        ];

        $presentTables = [];
        foreach ($requiredTables as $table) {
            $present = \Illuminate\Support\Facades\Schema::hasTable($table);
            $checks[] = ['Table: '.$table, $present, $present ? 'Present' : 'Missing — run php artisan migrate'];
            if ($present) $presentTables[$table] = true;
        }

        // Dashboard queries depend on these columns. Report missing fields
        // explicitly so a stale database can be corrected before opening the
        // dashboard instead of presenting an unexplained HTTP 500.
        $dashboardColumns = [
            'assets' => ['id','status','purchase_cost','warranty_expiration','category_id','asset_tag','name','created_at'],
            'asset_categories' => ['id','name'],
            'inventory_items' => ['id','quantity_on_hand','reorder_level','name','unit'],
            'software_licenses' => ['id','expires_at','software_name'],
            'licenses' => ['id','expiry_date','software_id'],
            'software' => ['id','name'],
            'support_tickets' => ['id','status'],
            'maintenance_records' => ['id','scheduled_at','status'],
            'approvals' => ['id','status','approver_id'],
            'users' => ['id','office_id'],
            'offices' => ['id','is_active','deleted_at'],
        ];

        foreach ($dashboardColumns as $table => $columns) {
            if (! isset($presentTables[$table])) continue;
            foreach ($columns as $column) {
                $present = \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
                $checks[] = [
                    'Dashboard column: '.$table.'.'.$column,
                    $present,
                    $present ? 'Present' : 'Missing — apply pending migrations before loading dashboard metrics',
                ];
            }
        }

        // Validate every field rendered by the generic Create/Edit forms against
        // the live database. A connected database with stale migrations is not
        // sufficient: a missing form column makes inserts or updates fail.
        foreach (config('inventory.resources', []) as $resource => $definition) {
            $modelClass = $definition['model'] ?? null;
            if (! is_string($modelClass) || ! class_exists($modelClass)) {
                $checks[] = ['Form model: '.$resource, false, 'Configured model class is missing'];
                continue;
            }

            $table = (new $modelClass())->getTable();
            if (! isset($presentTables[$table])) continue;

            $columns = array_values(array_unique(array_filter(array_map(
                static fn (array $field): ?string => $field['name'] ?? null,
                $definition['fields'] ?? []
            ))));

            // The creator trait writes these fields automatically on every
            // authenticated create/update and therefore they are mandatory.
            if (in_array(\App\Models\Concerns\TracksCreators::class, class_uses_recursive($modelClass), true)) {
                $columns = array_merge($columns, ['created_by', 'updated_by']);
            }

            foreach (array_unique($columns) as $column) {
                $present = \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
                if (! $present) {
                    $checks[] = ['Form column: '.$table.'.'.$column, false, 'Missing — run pending migrations before saving '.$resource.' records'];
                }
            }
        }

        // Every model using TracksCreators must have both audit columns.
        // This catches schema drift in workflow-only tables too, not just the
        // generic CRUD resources listed in config/inventory.php.
        foreach (glob(app_path('Models/*.php')) ?: [] as $modelFile) {
            $class = 'App\\Models\\'.pathinfo($modelFile, PATHINFO_FILENAME);
            if (! class_exists($class)) continue;
            if (! in_array(\App\Models\Concerns\TracksCreators::class, class_uses_recursive($class), true)) continue;

            $table = (new $class())->getTable();
            if (! \Illuminate\Support\Facades\Schema::hasTable($table)) continue;
            foreach (['created_by', 'updated_by'] as $column) {
                $present = \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
                $checks[] = ['Creator column: '.$table.'.'.$column, $present, $present ? 'Present' : 'Missing — migration must add creator tracking columns'];
            }
        }

        // Every model using BelongsToOffice has an office_id column and
        // depends on the offices table in its global scope. This prevents
        // non-super-admin dashboard/register requests from failing with an
        // unknown office_id column after a partial migration.
        foreach (glob(app_path('Models/*.php')) ?: [] as $modelFile) {
            $class = 'App\\Models\\'.pathinfo($modelFile, PATHINFO_FILENAME);
            if (! class_exists($class)) continue;
            if (! in_array(\App\Models\Concerns\BelongsToOffice::class, class_uses_recursive($class), true)) continue;

            $table = (new $class())->getTable();
            if (! isset($presentTables[$table])) continue;

            $present = \Illuminate\Support\Facades\Schema::hasColumn($table, 'office_id');
            $checks[] = [
                'Office scope column: '.$table.'.office_id',
                $present,
                $present ? 'Present' : 'Missing — run the latest office ownership migration',
            ];
        }

        // Columns used by transactions and lifecycle workflows are not always
        // visible on the generic resource forms, so check these separately.
        $workflowColumns = [
            'users' => ['employee_id','mfa_secret','mfa_enabled','must_change_password','failed_login_attempts','locked_until'],
            'assets' => ['current_employee_id','procurement_id','purchase_order_id','salvage_value','useful_life_months','depreciation_method'],
            'assignments' => ['acknowledged_by','acknowledged_at','signature_path','issued_by'],
            'procurements' => ['requested_by_user_id','quantity_received','quantity_rejected','received_by_user_id','inspection_remarks'],
            'inventory_items' => ['quantity_on_hand','reorder_level','category_id','location_id'],
            'stock_movements' => ['stock_item_id','type','qty','unit_cost','performed_by'],
            'stock_transactions' => ['inventory_item_id','type','quantity','balance_before','balance_after','reference','remarks','user_id','transaction_date'],
            'stock_counts' => ['count_no','location_id','department_id','status','counted_on','counted_by','approved_by','remarks','created_by','updated_by'],
            'stock_count_items' => ['stock_count_id','stock_item_id','system_qty','counted_qty','variance','remarks','created_by','updated_by'],
            'asset_histories' => ['asset_id','event','old_values','new_values','actor_id'],
            'inventory_audits' => ['scope','scheduled_for','lead_id','completed_at'],
            'inventory_audit_items' => ['audit_id','asset_id','result','seen_location_id','seen_employee_id','auditor_id','created_by','updated_by'],
            'ticket_comments' => ['support_ticket_id','user_id','comment','is_internal'],
            'receiving_reports' => ['location_id'],
            'receiving_report_items' => ['registered_model','registered_id','registered_ids'],
            'work_orders' => ['maintenance_schedule_id'],
            'documents' => ['original_name','checksum','version'],
            'approval_flows' => ['name','subject_type','mode','is_active','created_by','updated_by'],
            'approval_flow_steps' => ['approval_flow_id','sequence','approver_user_id','approver_role_id','mode','created_by','updated_by'],
            'supplier_evaluations' => ['created_by','updated_by'],
            'licenses' => ['created_by','updated_by'],
            'software' => ['created_by','updated_by'],
            'license_assignments' => ['created_by','updated_by'],
            'license_renewals' => ['created_by','updated_by'],
        ];

        foreach ($workflowColumns as $table => $columns) {
            if (! isset($presentTables[$table])) continue;
            foreach ($columns as $column) {
                $present = \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
                $checks[] = ['Workflow column: '.$table.'.'.$column, $present, $present ? 'Present' : 'Missing — run pending migrations'];
            }
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('migrations')) {
            $applied = \Illuminate\Support\Facades\DB::table('migrations')->pluck('migration')->all();
            $migrationFiles = glob(database_path('migrations/*.php')) ?: [];
            $expected = array_map(static fn (string $file): string => pathinfo($file, PATHINFO_FILENAME), $migrationFiles);
            $pending = array_values(array_diff($expected, $applied));
            $checks[] = ['Migration status', count($pending) === 0, $pending ? 'Pending: '.implode(', ', $pending) : count($expected).' migration(s) applied'];
        } else {
            $checks[] = ['Migration status', false, 'The migrations table is missing — run php artisan migrate'];
        }
    } catch (\Throwable $exception) {
        $checks[] = ['Database/schema inspection', false, $exception->getMessage()];
    }

    $failed = 0;
    foreach ($checks as [$label, $passed, $detail]) {
        $this->line(($passed ? '[PASS] ' : '[FAIL] ').$label.' — '.$detail);
        if (! $passed) $failed++;
    }

    $this->newLine();
    $this->line($failed === 0
        ? 'System checks passed. This verifies environment and schema readiness; execute application workflows to verify live writes.'
        : $failed.' check(s) need attention. Resolve the failed items, then run inventory:doctor again.');

    return $failed === 0 ? 0 : 1;
})->purpose('Validate runtime prerequisites, migrations, CRUD form columns, workflow schema, and core tables');

Artisan::command('inventory:send-reminders', function () {
    $setting = function (string $key, int $default): int {
        $record = SystemSetting::where('key', $key)->first();
        return (int) ($record?->value['value'] ?? $default);
    };
    $flag = static function (string $key, bool $default = true): bool {
        return (bool) data_get(SystemSetting::where('key', $key)->first()?->value, 'value', $default);
    };
    if (! $flag('notifications_enabled', true)) {
        $this->info('System notifications are disabled in System Settings.');
        return 0;
    }
    $recipients = [];
    $usersFor = function (string $permission) use (&$recipients): \Illuminate\Support\Collection {
        return $recipients[$permission] ??= User::query()
            ->where('is_active', true)
            ->whereHas('roles.permissions', fn ($query) => $query->where('name', $permission))
            ->get();
    };
    $sendOnce = function ($users, string $key, string $title, string $message, string $url, string $level = 'warning'): void {
        foreach ($users as $user) {
            $exists = $user->notifications()
                ->whereDate('created_at', today())
                ->where('data->event_key', $key)
                ->exists();
            if (! $exists) {
                $user->notify(new SystemAlert($title, $message, $url, $level, $key));
            }
        }
    };

    $warrantyDays = $setting('warranty_reminder_days', 30);
    if ($flag('notify_warranty', true)) foreach (Asset::whereNotNull('warranty_expiration')->whereBetween('warranty_expiration', [today(), today()->addDays($warrantyDays)])->get() as $asset) {
        $sendOnce($usersFor('assets.manage'), 'warranty:'.$asset->id.':'.$asset->warranty_expiration->toDateString(), 'Warranty expiring', $asset->asset_tag.' warranty expires on '.$asset->warranty_expiration->format('d M Y').'.', route('inventory.show', ['assets', $asset->id]));
    }

    $licenseDays = $setting('license_reminder_days', 60);
    if ($flag('notify_license_expiry', true)) foreach (SoftwareLicense::whereNotNull('expires_at')->whereBetween('expires_at', [today(), today()->addDays($licenseDays)])->get() as $license) {
        $sendOnce($usersFor('software.manage'), 'license:'.$license->id.':'.$license->expires_at->toDateString(), 'Software license expiring', $license->software_name.' license expires on '.$license->expires_at->format('d M Y').'.', route('inventory.show', ['software', $license->id]));
    }

    $maintenanceDays = $setting('maintenance_reminder_days', 14);
    if ($flag('notify_maintenance', true)) foreach (MaintenanceRecord::where(function ($query) use ($maintenanceDays) {
        $query->where(function ($active) use ($maintenanceDays) {
            $active->whereNotIn('status', ['completed', 'cancelled'])
                ->whereBetween('scheduled_at', [today(), today()->addDays($maintenanceDays)]);
        })->orWhereBetween('next_maintenance_at', [today(), today()->addDays($maintenanceDays)]);
    })->get() as $workOrder) {
        $date = $workOrder->next_maintenance_at ?? $workOrder->scheduled_at;
        $sendOnce($usersFor('maintenance.manage'), 'maintenance:'.$workOrder->id.':'.$date->toDateString(), 'Maintenance due', $workOrder->work_order_no.' is scheduled for '.$date->format('d M Y').'.', route('inventory.show', ['maintenance', $workOrder->id]));
    }

    if ($flag('notify_low_stock', true)) foreach (InventoryItem::whereColumn('quantity_on_hand', '<=', 'reorder_level')->get() as $item) {
        $sendOnce($usersFor('consumables.manage'), 'stock:'.$item->id.':'.today()->toDateString(), 'Low stock alert', $item->name.' has '.$item->quantity_on_hand.' '.$item->unit.' remaining.', route('inventory.show', ['consumables', $item->id]));
    }

    $this->info('Inventory reminders processed.');
})->purpose('Send scheduled warranty, license, maintenance and low-stock reminders');

Schedule::command('inventory:send-reminders')->dailyAt(config('app.notification_delivery_time', '08:00'))->timezone(config('app.timezone', 'Asia/Manila'));

Artisan::command('inventory:depreciate {--as-of=} {--dry-run}', function (AssetDepreciationService $service) {
    try {
        $asOf = $this->option('as-of')
            ? CarbonImmutable::parse($this->option('as-of'))
            : CarbonImmutable::now(config('app.timezone'))->startOfMonth()->subDay();
    } catch (\Throwable $exception) {
        $this->error('Invalid --as-of date. Use YYYY-MM-DD.');
        return 1;
    }

    $dryRun = (bool) $this->option('dry-run');
    $result = $service->post($asOf, $dryRun);

    $this->table(
        ['Measure', 'Result'],
        [
            ['As-of period', $asOf->toDateString()],
            ['Mode', $dryRun ? 'Preview only — no entries saved' : 'Posted / updated'],
            ['Assets processed', $result['assets_processed']],
            [$dryRun ? 'Entries to post' : 'Entries posted or updated', $result['entries_created_or_updated']],
            ['Skipped: life or method not configured', $result['skipped_unconfigured']],
            ['Skipped: method not supported by this posting job', $result['skipped_not_straight_line']],
        ]
    );

    return 0;
})->purpose('Post monthly straight-line depreciation entries; supports preview mode');

Schedule::command('inventory:depreciate')->monthlyOn(1, '08:30')->timezone(config('app.timezone', 'Asia/Manila'));

Artisan::command('inventory:escalate-approvals', function () {
    $superAdmins = Role::where('slug', 'super-admin')->first()?->users()->where('is_active', true)->get() ?? collect();
    $escalated = 0;
    Approval::with(['approver', 'step', 'approvable'])
        ->where('status', 'pending')->whereNull('escalated_at')
        ->whereNotNull('step_id')->chunkById(100, function ($approvals) use ($superAdmins, &$escalated) {
            foreach ($approvals as $approval) {
                $hours = $approval->step?->escalate_after_hours;
                if (! $hours || $approval->created_at->gt(now()->subHours((int) $hours))) {
                    continue;
                }
                $approval->forceFill(['escalated_at' => now()])->save();
                $url = route('approvals.index');
                $subject = class_basename($approval->approvable_type).' #'.$approval->approvable_id;
                $approval->approver?->notify(new SystemAlert('Approval overdue', $subject.' has exceeded its configured approval time limit.', $url, 'warning', 'approval-escalation:'.$approval->id));
                foreach ($superAdmins as $admin) {
                    $admin->notify(new SystemAlert('Approval escalated', $subject.' is overdue at approval step '.$approval->sequence.'.', $url, 'warning', 'approval-escalation-admin:'.$approval->id));
                }
                $escalated++;
            }
        });
    $this->info($escalated.' overdue approval task(s) escalated.');
})->purpose('Notify approvers and Super Administrators when configured approval steps exceed their time limit');

Schedule::command('inventory:escalate-approvals')->hourly()->timezone(config('app.timezone', 'Asia/Manila'))->when(fn () => (bool) data_get(SystemSetting::where('key', 'approval_escalation_enabled')->first()?->value, 'value', true));



Artisan::command('inventory:notify-overdue-assignments', function () {
    $notified = 0;
    Assignment::with(['asset', 'employee'])
        ->where('status', 'issued')
        ->whereNotNull('due_date')
        ->whereDate('due_date', '<', today())
        ->whereNull('overdue_notified_at')
        ->chunkById(100, function ($assignments) use (&$notified) {
            foreach ($assignments as $assignment) {
                $recipients = User::where('is_active', true)
                    ->whereHas('roles.permissions', fn ($query) => $query->where('name', 'assignments.manage'))
                    ->where(fn ($query) => $query->where('office_id', $assignment->office_id)->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('slug', 'super-admin')))
                    ->get();
                $assetLabel = $assignment->asset?->asset_tag ?: ('Asset #'.$assignment->asset_id);
                $employeeName = $assignment->employee?->full_name ?: 'Unknown custodian';
                foreach ($recipients as $user) {
                    $user->notify(new SystemAlert(
                        'Overdue IT asset return',
                        $assetLabel.' assigned to '.$employeeName.' was due for return on '.$assignment->due_date->format('d M Y').'.',
                        route('inventory.show', ['assignments', $assignment->id]),
                        'warning',
                        'overdue-assignment:'.$assignment->id
                    ));
                }
                $assignment->forceFill(['overdue_notified_at' => now()])->save();
                $notified++;
            }
        });
    $this->info($notified.' overdue assignment notification(s) sent.');
})->purpose('Notify asset custodians of overdue asset returns');

Schedule::command('inventory:notify-overdue-assignments')->dailyAt('08:00')->timezone(config('app.timezone', 'Asia/Manila'));

Artisan::command('maintenance-schedules:generate', function (MaintenanceScheduleService $service) {
    $result = $service->generateDue();
    $this->info($result['generated'].' preventive work order(s) generated; '.$result['skipped'].' schedule(s) skipped.');
})->purpose('Generate preventive work orders for due maintenance schedules');

Schedule::command('maintenance-schedules:generate')->dailyAt(config('app.maintenance_generation_time', '06:00'))->timezone(config('app.timezone', 'Asia/Manila'));


Artisan::command('inventory:backup', function (DatabaseBackupService $service) {
    try {
        $record = $service->create();
        $retentionSetting = SystemSetting::where('key', 'backup_retention_days')->first();
        $retentionDays = (int) data_get($retentionSetting?->value, 'value', 30);
        $pruned = $service->prune($retentionDays);
        $this->info('Database backup created: '.$record->filename);
        $this->info('Retention: '.$retentionDays.' days; old backups removed: '.$pruned.'.');
        return 0;
    } catch (Throwable $exception) {
        report($exception);
        $this->error('Scheduled database backup failed: '.$exception->getMessage());
        return 1;
    }
})->purpose('Create a private MySQL backup and prune expired backup records');

Schedule::command('inventory:backup')
    ->dailyAt(config('app.backup_schedule_time', '01:30'))
    ->timezone(config('app.timezone', 'Asia/Manila'))
    ->when(fn () => (bool) data_get(SystemSetting::where('key', 'automatic_backups')->first()?->value, 'value', false));
