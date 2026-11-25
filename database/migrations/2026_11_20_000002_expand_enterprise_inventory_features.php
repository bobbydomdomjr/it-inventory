<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->after('employee_no')->constrained('users')->nullOnDelete();
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->decimal('salvage_value', 14, 2)->default(0)->after('purchase_cost');
            $table->string('acquisition_method', 40)->nullable()->after('purchase_date');
            $table->date('disposed_at')->nullable()->after('warranty_expiration');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->string('acknowledged_by')->nullable()->after('remarks');
            $table->timestamp('acknowledged_at')->nullable()->after('acknowledged_by');
            $table->string('signature_path')->nullable()->after('acknowledged_at');
        });

        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->text('parts_used')->nullable()->after('cost');
            $table->string('service_provider')->nullable()->after('assigned_technician');
            $table->date('next_maintenance_at')->nullable()->after('completed_at');
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->timestamp('first_response_at')->nullable()->after('assigned_to');
            $table->timestamp('resolved_at')->nullable()->after('first_response_at');
            $table->timestamp('sla_due_at')->nullable()->after('resolved_at');
            $table->unsignedInteger('satisfaction_rating')->nullable()->after('resolution');
        });

        Schema::table('procurements', function (Blueprint $table) {
            $table->string('purchase_order_no')->nullable()->after('supplier_name');
            $table->date('expected_delivery_at')->nullable()->after('requested_at');
            $table->date('received_at')->nullable()->after('expected_delivery_at');
            $table->string('inspection_result')->nullable()->after('received_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('phone', 50)->nullable()->after('last_login_at');
        });

        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('comment');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
        });

        Schema::create('software_license_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_license_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('assigned_to')->nullable();
            $table->date('assigned_at');
            $table->date('returned_at')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['software_license_id', 'status']);
        });

        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->string('disposal_no', 80)->unique();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('proposed_method', 60);
            $table->text('justification');
            $table->string('technical_condition')->nullable();
            $table->decimal('estimated_salvage_value', 14, 2)->default(0);
            $table->date('requested_at');
            $table->date('disposed_at')->nullable();
            $table->string('status')->default('pending_approval')->index();
            $table->text('remarks')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        Schema::create('backup_records', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('disk_path');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('status')->default('completed');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('backup_records');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('asset_disposals');
        Schema::dropIfExists('software_license_assignments');
        Schema::dropIfExists('ticket_comments');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login_at', 'phone']);
        });
        Schema::table('procurements', function (Blueprint $table) {
            $table->dropColumn(['purchase_order_no', 'expected_delivery_at', 'received_at', 'inspection_result']);
        });
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn(['first_response_at', 'resolved_at', 'sla_due_at', 'satisfaction_rating']);
        });
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->dropColumn(['parts_used', 'service_provider', 'next_maintenance_at']);
        });
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['acknowledged_by', 'acknowledged_at', 'signature_path']);
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['salvage_value', 'acquisition_method', 'disposed_at']);
        });
    }
};
