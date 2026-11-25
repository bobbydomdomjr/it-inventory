<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('work_orders') && ! Schema::hasColumn('work_orders', 'maintenance_schedule_id')) {
            Schema::table('work_orders', function (Blueprint $table) {
                $table->foreignId('maintenance_schedule_id')->nullable()->after('asset_id')->constrained('maintenance_schedules')->nullOnDelete();
            });
        }

        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table) {
                if (! Schema::hasColumn('documents', 'original_name')) {
                    $table->string('original_name')->nullable()->after('title');
                }
                if (! Schema::hasColumn('documents', 'checksum')) {
                    $table->string('checksum', 64)->nullable()->after('size');
                }
            });
        }

        if (Schema::hasTable('receiving_reports') && ! Schema::hasColumn('receiving_reports', 'location_id')) {
            Schema::table('receiving_reports', function (Blueprint $table) {
                $table->foreignId('location_id')->nullable()->after('received_at')->constrained('locations')->nullOnDelete();
            });
        }

        if (Schema::hasTable('receiving_report_items')) {
            Schema::table('receiving_report_items', function (Blueprint $table) {
                if (! Schema::hasColumn('receiving_report_items', 'registered_model')) {
                    $table->string('registered_model', 150)->nullable()->after('registered_as');
                }
                if (! Schema::hasColumn('receiving_report_items', 'registered_id')) {
                    $table->unsignedBigInteger('registered_id')->nullable()->after('registered_model')->index();
                }
                if (! Schema::hasColumn('receiving_report_items', 'registered_ids')) {
                    $table->json('registered_ids')->nullable()->after('registered_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('receiving_report_items')) {
            Schema::table('receiving_report_items', function (Blueprint $table) {
                if (Schema::hasColumn('receiving_report_items', 'registered_ids')) $table->dropColumn('registered_ids');
                if (Schema::hasColumn('receiving_report_items', 'registered_id')) $table->dropColumn('registered_id');
                if (Schema::hasColumn('receiving_report_items', 'registered_model')) $table->dropColumn('registered_model');
            });
        }
        if (Schema::hasTable('receiving_reports') && Schema::hasColumn('receiving_reports', 'location_id')) {
            Schema::table('receiving_reports', function (Blueprint $table) {
                $table->dropConstrainedForeignId('location_id');
            });
        }
        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table) {
                if (Schema::hasColumn('documents', 'original_name')) $table->dropColumn('original_name');
                if (Schema::hasColumn('documents', 'checksum')) $table->dropColumn('checksum');
            });
        }
        if (Schema::hasTable('work_orders') && Schema::hasColumn('work_orders', 'maintenance_schedule_id')) {
            Schema::table('work_orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('maintenance_schedule_id');
            });
        }
    }
};
