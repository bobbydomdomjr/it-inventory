<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('receiving_report_items') && ! Schema::hasColumn('receiving_report_items', 'registered_ids')) {
            Schema::table('receiving_report_items', function (Blueprint $table) {
                $table->json('registered_ids')->nullable()->after('registered_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('receiving_report_items') && Schema::hasColumn('receiving_report_items', 'registered_ids')) {
            Schema::table('receiving_report_items', function (Blueprint $table) {
                $table->dropColumn('registered_ids');
            });
        }
    }
};
