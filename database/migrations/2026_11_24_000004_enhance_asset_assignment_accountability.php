<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('assignments')) return;
        Schema::table('assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('assignments', 'requested_by')) $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            if (! Schema::hasColumn('assignments', 'department_id')) $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            if (! Schema::hasColumn('assignments', 'location_id')) $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            if (! Schema::hasColumn('assignments', 'approved_at')) $table->timestamp('approved_at')->nullable();
            if (! Schema::hasColumn('assignments', 'overdue_notified_at')) $table->timestamp('overdue_notified_at')->nullable();
        });
        if (Schema::hasTable('asset_transfers')) {
            Schema::table('asset_transfers', function (Blueprint $table) {
                if (! Schema::hasColumn('asset_transfers', 'to_department_id')) $table->foreignId('to_department_id')->nullable()->constrained('departments')->nullOnDelete();
                if (! Schema::hasColumn('asset_transfers', 'to_location_id')) $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            });
        }
        $indexes = Schema::getIndexes('assignments');
        if (! collect($indexes)->contains(fn ($index) => ($index['name'] ?? '') === 'assignments_status_due_date_idx')) {
            Schema::table('assignments', function (Blueprint $table) {
                $table->index(['status', 'due_date'], 'assignments_status_due_date_idx');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('assignments')) return;
        if (Schema::hasTable('asset_transfers')) {
            Schema::table('asset_transfers', function (Blueprint $table) {
                foreach (['to_department_id','to_location_id'] as $column) {
                    if (Schema::hasColumn('asset_transfers', $column)) { $table->dropForeign([$column]); $table->dropColumn($column); }
                }
            });
        }
        Schema::table('assignments', function (Blueprint $table) {
            if (collect(Schema::getIndexes('assignments'))->contains(fn ($index) => ($index['name'] ?? '') === 'assignments_status_due_date_idx')) $table->dropIndex('assignments_status_due_date_idx');
            foreach (['requested_by', 'department_id', 'location_id'] as $column) {
                if (Schema::hasColumn('assignments', $column)) {
                    $table->dropForeign([$column]);
                    $table->dropColumn($column);
                }
            }
            if (Schema::hasColumn('assignments', 'approved_at')) $table->dropColumn('approved_at');
            if (Schema::hasColumn('assignments', 'overdue_notified_at')) $table->dropColumn('overdue_notified_at');
        });
    }
};
