<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (! Schema::hasColumn('employees', 'separated_at')) $table->date('separated_at')->nullable();
                if (! Schema::hasColumn('employees', 'offboarding_remarks')) $table->text('offboarding_remarks')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (Schema::hasColumn('employees', 'offboarding_remarks')) $table->dropColumn('offboarding_remarks');
                if (Schema::hasColumn('employees', 'separated_at')) $table->dropColumn('separated_at');
            });
        }
    }
};
