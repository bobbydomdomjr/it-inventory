<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive multi-office foundation.
     *
     * office_id is intentionally nullable during rollout. Existing records must be
     * mapped to the correct office before office-level authorization is enforced.
     */
    private array $ownedTables = [
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
    ];

    public function up(): void
    {
        if (! Schema::hasTable('offices')) {
            Schema::create('offices', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('name');
                $table->string('short_name', 80)->nullable();
                $table->string('email')->nullable();
                $table->string('phone', 50)->nullable();
                $table->text('address')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        foreach ($this->ownedTables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'office_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('office_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('offices')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->ownedTables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'office_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('office_id');
            });
        }

        Schema::dropIfExists('offices');
    }
};
