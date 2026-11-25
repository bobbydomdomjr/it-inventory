<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Upgrade path for installations that already ran the earlier version
        // of migration 000006 before the audit table was aligned to the build plan.
        if (Schema::hasTable('physical_audits') && ! Schema::hasTable('inventory_audits')) {
            Schema::rename('physical_audits', 'inventory_audits');
        }

        if (Schema::hasTable('approvals') && ! Schema::hasColumn('approvals', 'escalated_at')) {
            Schema::table('approvals', function (Blueprint $table) {
                $table->timestamp('escalated_at')->nullable();
            });
        }

        if (Schema::hasTable('approvals') && ! Schema::hasColumn('approvals', 'round')) {
            Schema::table('approvals', function (Blueprint $table) {
                $table->unsignedSmallInteger('round')->default(1);
            });
        }
    }

    public function down(): void
    {
        // Keep audit history and approval escalation data when rolling back an
        // application release; those records may already be operational data.
    }
};
