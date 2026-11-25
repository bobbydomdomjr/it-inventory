<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'purchase_orders', 'quotations', 'purchase_request_items', 'receiving_report_items', 'licenses',
        'stock_counts', 'stock_count_items', 'maintenance_schedules', 'inventory_audit_items',
        'license_renewals', 'license_assignments', 'software_license_assignments', 'ticket_comments',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'office_id')) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('office_id')->nullable()->after('id')->constrained('offices')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'office_id')) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('office_id');
            });
        }
    }
};
