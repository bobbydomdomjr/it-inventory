<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asset_categories') && ! Schema::hasColumn('asset_categories', 'default_min_stock_level')) {
            Schema::table('asset_categories', function (Blueprint $table): void {
                $table->unsignedInteger('default_min_stock_level')->default(0)->after('salvage_percent');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('asset_categories') && Schema::hasColumn('asset_categories', 'default_min_stock_level')) {
            Schema::table('asset_categories', function (Blueprint $table): void {
                $table->dropColumn('default_min_stock_level');
            });
        }
    }
};
