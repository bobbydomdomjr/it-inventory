<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The office-ownership rollout added nullable office_id columns but did not
     * assign existing records. Non-super-admin users are intentionally scoped
     * to their office, so NULL-owned records otherwise become invisible.
     *
     * This migration is non-destructive: it only fills NULL office_id values.
     * Existing office assignments and all business records are preserved.
     */
    private array $officeOwnedTables = [
        'users',
        'departments',
        'locations',
        'asset_categories',
        'suppliers',
        'employees',
        'assets',
        'inventory_items',
        'software_licenses',
        'assignments',
        'asset_transfers',
        'stock_transactions',
        'maintenance_records',
        'support_tickets',
        'procurements',
        'work_orders',
        'documents',
        'receiving_reports',
        'supplier_evaluations',
        'asset_disposals',
        'purchase_requests',
        'purchase_orders',
        'quotations',
        'purchase_request_items',
        'receiving_report_items',
        'licenses',
        'stock_counts',
        'stock_count_items',
        'maintenance_schedules',
        'inventory_audit_items',
        'license_renewals',
        'license_assignments',
        'software_license_assignments',
        'ticket_comments'
    ];

    public function up(): void
    {
        if (! Schema::hasTable('offices')) {
            return;
        }

        DB::transaction(function (): void {
            $now = now();

            DB::table('offices')->insertOrIgnore([
                'code' => 'MAIN',
                'name' => 'Main Office',
                'short_name' => 'MAIN',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);

            $office = DB::table('offices')
                ->where('code', 'MAIN')
                ->first();

            if (! $office) {
                throw new RuntimeException('Unable to initialize the default Main Office record.');
            }

            // If this default office was previously soft-deleted, reactivate it.
            DB::table('offices')->where('id', $office->id)->update([
                'is_active' => true,
                'deleted_at' => null,
                'updated_at' => $now,
            ]);

            foreach ($this->officeOwnedTables as $tableName) {
                if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'office_id')) {
                    continue;
                }

                DB::table($tableName)
                    ->whereNull('office_id')
                    ->update(['office_id' => $office->id]);
            }
        });
    }

    /**
     * Deliberately retain office assignments on rollback. Reverting them to
     * NULL would make existing records inaccessible to office-scoped users.
     */
    public function down(): void
    {
        // Non-destructive data migration: no automatic data rollback.
    }
};
