<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('physical_audits') && ! Schema::hasTable('inventory_audits')) {
            Schema::rename('physical_audits', 'inventory_audits');
        }

        // Existing tables are extended additively so this migration is safe for
        // installations that already ran the earlier project migrations.
        $this->add('users', 'mfa_secret', fn (Blueprint $t) => $t->text('mfa_secret')->nullable());
        $this->add('users', 'mfa_enabled', fn (Blueprint $t) => $t->boolean('mfa_enabled')->default(false));
        $this->add('users', 'must_change_password', fn (Blueprint $t) => $t->boolean('must_change_password')->default(false));
        $this->add('users', 'password_changed_at', fn (Blueprint $t) => $t->timestamp('password_changed_at')->nullable());
        $this->add('users', 'failed_login_attempts', fn (Blueprint $t) => $t->unsignedSmallInteger('failed_login_attempts')->default(0));
        $this->add('users', 'locked_until', fn (Blueprint $t) => $t->timestamp('locked_until')->nullable());

        $this->add('departments', 'parent_id', fn (Blueprint $t) => $t->foreignId('parent_id')->nullable()->constrained('departments')->nullOnDelete());
        $this->add('departments', 'head_employee_id', fn (Blueprint $t) => $t->foreignId('head_employee_id')->nullable()->constrained('employees')->nullOnDelete());
        $this->add('locations', 'parent_id', fn (Blueprint $t) => $t->foreignId('parent_id')->nullable()->constrained('locations')->nullOnDelete());
        $this->add('locations', 'type', fn (Blueprint $t) => $t->string('type', 40)->default('room'));
        $this->add('locations', 'code', fn (Blueprint $t) => $t->string('code', 60)->nullable()->unique());
        $this->add('locations', 'qr_code', fn (Blueprint $t) => $t->string('qr_code')->nullable());

        $this->add('asset_categories', 'parent_id', fn (Blueprint $t) => $t->foreignId('parent_id')->nullable()->constrained('asset_categories')->nullOnDelete());
        $this->add('asset_categories', 'type', fn (Blueprint $t) => $t->string('type', 30)->default('asset'));
        $this->add('asset_categories', 'depreciation_method', fn (Blueprint $t) => $t->string('depreciation_method', 30)->default('not_configured'));
        $this->add('asset_categories', 'useful_life_months', fn (Blueprint $t) => $t->unsignedInteger('useful_life_months')->nullable());
        $this->add('asset_categories', 'salvage_percent', fn (Blueprint $t) => $t->decimal('salvage_percent', 6, 3)->default(0));
        $this->add('asset_categories', 'custom_fields', fn (Blueprint $t) => $t->json('custom_fields')->nullable());

        $this->add('employees', 'position_rank', fn (Blueprint $t) => $t->string('position_rank')->nullable());
        $this->add('employees', 'designation', fn (Blueprint $t) => $t->string('designation')->nullable());
        $this->add('employees', 'transferred_at', fn (Blueprint $t) => $t->date('transferred_at')->nullable());
        $this->add('suppliers', 'tin', fn (Blueprint $t) => $t->string('tin', 40)->nullable());
        $this->add('suppliers', 'notes', fn (Blueprint $t) => $t->text('notes')->nullable());

        $this->add('inventory_audits', 'scope', fn (Blueprint $t) => $t->string('scope', 30)->default('all'));
        $this->add('inventory_audits', 'scheduled_for', fn (Blueprint $t) => $t->date('scheduled_for')->nullable());
        $this->add('inventory_audits', 'lead_id', fn (Blueprint $t) => $t->foreignId('lead_id')->nullable()->constrained('users')->nullOnDelete());
        $this->add('inventory_audits', 'completed_at', fn (Blueprint $t) => $t->timestamp('completed_at')->nullable());

        $this->add('assets', 'specs', fn (Blueprint $t) => $t->json('specs')->nullable());
        $this->add('assets', 'image_path', fn (Blueprint $t) => $t->string('image_path')->nullable());
        $this->add('assets', 'asset_prefix', fn (Blueprint $t) => $t->string('asset_prefix', 20)->nullable());
        $this->add('assets', 'useful_life_months', fn (Blueprint $t) => $t->unsignedInteger('useful_life_months')->nullable());
        $this->add('assets', 'depreciation_method', fn (Blueprint $t) => $t->string('depreciation_method', 30)->nullable());

        $this->add('assignments', 'assignment_type', fn (Blueprint $t) => $t->string('assignment_type', 30)->default('permanent'));
        $this->add('assignments', 'assignee_type', fn (Blueprint $t) => $t->string('assignee_type', 100)->nullable());
        $this->add('assignments', 'assignee_id', fn (Blueprint $t) => $t->unsignedBigInteger('assignee_id')->nullable()->index());
        $this->add('assignments', 'due_back_at', fn (Blueprint $t) => $t->dateTime('due_back_at')->nullable());
        $this->add('assignments', 'approved_by', fn (Blueprint $t) => $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete());
        $this->add('assignments', 'ack_signature_path', fn (Blueprint $t) => $t->string('ack_signature_path')->nullable());
        $this->add('assignments', 'ack_at', fn (Blueprint $t) => $t->timestamp('ack_at')->nullable());

        $this->add('asset_transfers', 'transfer_type', fn (Blueprint $t) => $t->string('transfer_type', 30)->default('permanent'));
        $this->add('asset_transfers', 'from_type', fn (Blueprint $t) => $t->string('from_type', 100)->nullable());
        $this->add('asset_transfers', 'from_id', fn (Blueprint $t) => $t->unsignedBigInteger('from_id')->nullable()->index());
        $this->add('asset_transfers', 'to_type', fn (Blueprint $t) => $t->string('to_type', 100)->nullable());
        $this->add('asset_transfers', 'to_id', fn (Blueprint $t) => $t->unsignedBigInteger('to_id')->nullable()->index());
        $this->add('asset_transfers', 'received_by', fn (Blueprint $t) => $t->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete());
        $this->add('asset_transfers', 'received_at', fn (Blueprint $t) => $t->timestamp('received_at')->nullable());

        $this->add('inventory_items', 'category_id', fn (Blueprint $t) => $t->foreignId('category_id')->nullable()->constrained('asset_categories')->nullOnDelete());
        $this->add('inventory_items', 'min_level', fn (Blueprint $t) => $t->unsignedInteger('min_level')->default(0));
        $this->add('inventory_items', 'location_id', fn (Blueprint $t) => $t->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete());

        $this->add('maintenance_records', 'technician_id', fn (Blueprint $t) => $t->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete());
        $this->add('maintenance_records', 'started_at', fn (Blueprint $t) => $t->timestamp('started_at')->nullable());
        $this->add('maintenance_records', 'downtime_minutes', fn (Blueprint $t) => $t->unsignedInteger('downtime_minutes')->default(0));
        $this->add('support_tickets', 'requester_id', fn (Blueprint $t) => $t->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete());
        $this->add('support_tickets', 'assignee_id', fn (Blueprint $t) => $t->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete());
        $this->add('support_tickets', 'escalated_at', fn (Blueprint $t) => $t->timestamp('escalated_at')->nullable());
        $this->add('support_tickets', 'closed_at', fn (Blueprint $t) => $t->timestamp('closed_at')->nullable());

        $this->add('asset_disposals', 'evaluation_report', fn (Blueprint $t) => $t->text('evaluation_report')->nullable());
        $this->add('asset_disposals', 'method', fn (Blueprint $t) => $t->string('method', 60)->nullable());
        $this->add('asset_disposals', 'disposal_date', fn (Blueprint $t) => $t->date('disposal_date')->nullable());
        $this->add('asset_disposals', 'disposal_value', fn (Blueprint $t) => $t->decimal('disposal_value', 14, 2)->default(0));
        $this->add('asset_disposals', 'salvage_value', fn (Blueprint $t) => $t->decimal('salvage_value', 14, 2)->default(0));
        $this->add('asset_disposals', 'witnesses', fn (Blueprint $t) => $t->json('witnesses')->nullable());
        $this->add('documents', 'type', fn (Blueprint $t) => $t->string('type', 80)->nullable());
        $this->add('documents', 'version', fn (Blueprint $t) => $t->unsignedInteger('version')->default(1));
        $this->add('approvals', 'step_id', fn (Blueprint $t) => $t->unsignedBigInteger('step_id')->nullable()->index());
        $this->add('approvals', 'decision', fn (Blueprint $t) => $t->string('decision', 30)->nullable());
        $this->add('approvals', 'decided_at', fn (Blueprint $t) => $t->timestamp('decided_at')->nullable());
        $this->add('approvals', 'escalated_at', fn (Blueprint $t) => $t->timestamp('escalated_at')->nullable());
        $this->add('approvals', 'round', fn (Blueprint $t) => $t->unsignedSmallInteger('round')->default(1));

        // Cross-cutting ownership columns. These remain nullable for legacy imports.
        foreach ([
            'departments', 'locations', 'asset_categories', 'suppliers', 'employees',
            'assets', 'inventory_items', 'software_licenses', 'assignments',
            'asset_transfers', 'maintenance_records', 'support_tickets', 'procurements',
            'inventory_audits', 'asset_disposals',
        ] as $table) {
            $this->add($table, 'created_by', fn (Blueprint $t) => $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete());
            $this->add($table, 'updated_by', fn (Blueprint $t) => $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete());
        }

        $this->createIfMissing('login_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('email')->nullable()->index();
            $t->string('ip', 45)->nullable();
            $t->string('user_agent', 500)->nullable();
            $t->string('status', 20)->index();
            $t->timestamp('logged_at')->useCurrent()->index();
        });

        $this->createIfMissing('asset_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $t->string('event', 100)->index();
            $t->json('old_values')->nullable();
            $t->json('new_values')->nullable();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->useCurrent()->index();
            $t->index(['asset_id', 'created_at']);
        });

        $this->createIfMissing('depreciation_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $t->date('period');
            $t->decimal('amount', 14, 2);
            $t->decimal('book_value', 14, 2);
            $t->timestamps();
            $t->unique(['asset_id', 'period']);
        });

        $this->createIfMissing('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stock_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $t->string('type', 30);
            $t->integer('qty'); // signed quantity; positive receives, negative issues
            $t->decimal('unit_cost', 14, 2)->default(0);
            $t->string('batch_no', 100)->nullable();
            $t->date('expiry_date')->nullable();
            $t->nullableMorphs('reference');
            $t->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->useCurrent()->index();
            $t->index(['stock_item_id', 'created_at']);
        });

        $this->createIfMissing('stock_counts', function (Blueprint $t) {
            $t->id();
            $t->string('count_no', 80)->unique();
            $t->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->string('status', 30)->default('draft')->index();
            $t->date('counted_on')->nullable();
            $t->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('remarks')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        $this->createIfMissing('stock_count_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stock_count_id')->constrained('stock_counts')->cascadeOnDelete();
            $t->foreignId('stock_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $t->unsignedInteger('system_qty');
            $t->unsignedInteger('counted_qty')->nullable();
            $t->integer('variance')->nullable();
            $t->text('remarks')->nullable();
            $t->timestamps();
            $t->unique(['stock_count_id', 'stock_item_id']);
        });

        $this->createIfMissing('software', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('version')->nullable();
            $t->string('publisher')->nullable();
            $t->foreignId('category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $t->text('description')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        $this->createIfMissing('licenses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('software_id')->constrained('software')->cascadeOnDelete();
            $t->text('license_key')->nullable();
            $t->string('type', 30)->default('subscription');
            $t->unsignedInteger('seats_total')->default(1);
            $t->date('purchase_date')->nullable();
            $t->date('expiry_date')->nullable();
            $t->decimal('cost', 14, 2)->default(0);
            $t->string('subscription_cycle', 30)->nullable();
            $t->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['software_id', 'expiry_date']);
        });

        $this->createIfMissing('license_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('license_id')->constrained('licenses')->cascadeOnDelete();
            $t->string('assignable_type');
            $t->unsignedBigInteger('assignable_id');
            $t->timestamp('assigned_at')->useCurrent();
            $t->timestamp('revoked_at')->nullable();
            $t->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['assignable_type', 'assignable_id']);
            $t->index(['license_id', 'revoked_at']);
        });

        $this->createIfMissing('license_renewals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('license_id')->constrained('licenses')->cascadeOnDelete();
            $t->date('renewed_on');
            $t->date('new_expiry');
            $t->decimal('cost', 14, 2)->default(0);
            $t->text('remarks')->nullable();
            $t->foreignId('renewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });

        $this->createIfMissing('purchase_requests', function (Blueprint $t) {
            $t->id();
            $t->string('pr_no', 80)->unique();
            $t->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->text('purpose');
            $t->string('status', 40)->default('draft')->index();
            $t->decimal('total_estimated', 14, 2)->default(0);
            $t->timestamps();
            $t->softDeletes();
        });

        $this->createIfMissing('purchase_request_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $t->string('description');
            $t->foreignId('category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $t->unsignedInteger('qty')->default(1);
            $t->decimal('est_unit_cost', 14, 2)->default(0);
            $t->timestamps();
            $t->softDeletes();
        });

        $this->createIfMissing('quotations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $t->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $t->decimal('total', 14, 2);
            $t->boolean('is_selected')->default(false);
            $t->string('file_path')->nullable();
            $t->text('remarks')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        $this->createIfMissing('purchase_orders', function (Blueprint $t) {
            $t->id();
            $t->string('po_no', 80)->unique();
            $t->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $t->unique('purchase_request_id');
            $t->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $t->date('ordered_at')->nullable();
            $t->decimal('total', 14, 2)->default(0);
            $t->string('status', 40)->default('ordered')->index();
            $t->timestamps();
            $t->softDeletes();
        });

        $this->createIfMissing('receiving_reports', function (Blueprint $t) {
            $t->id();
            $t->string('rr_no', 80)->unique();
            $t->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $t->date('received_at')->nullable();
            $t->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $t->date('accepted_at')->nullable();
            $t->string('status', 30)->default('received')->index();
            $t->text('inspection_remarks')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        $this->createIfMissing('receiving_report_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('receiving_report_id')->constrained('receiving_reports')->cascadeOnDelete();
            $t->foreignId('purchase_request_item_id')->nullable()->constrained('purchase_request_items')->nullOnDelete();
            $t->string('description');
            $t->unsignedInteger('qty_received')->default(0);
            $t->string('registered_as', 20)->default('stock');
            $t->timestamps();
            $t->softDeletes();
        });

        $this->add('assets', 'purchase_order_id', fn (Blueprint $t) => $t->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete());

        $this->createIfMissing('maintenance_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('asset_id')->nullable()->constrained('assets')->cascadeOnDelete();
            $t->foreignId('category_id')->nullable()->constrained('asset_categories')->cascadeOnDelete();
            $t->unsignedSmallInteger('interval_days');
            $t->date('next_due');
            $t->date('last_done')->nullable();
            $t->boolean('is_active')->default(true);
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['is_active', 'next_due']);
        });

        $this->createIfMissing('work_orders', function (Blueprint $t) {
            $t->id();
            $t->string('wo_no', 80)->unique();
            $t->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $t->string('type', 30)->default('corrective');
            $t->string('priority', 20)->default('normal');
            $t->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 30)->default('pending')->index();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->decimal('cost', 14, 2)->default(0);
            $t->json('parts_used')->nullable();
            $t->string('service_provider')->nullable();
            $t->unsignedInteger('downtime_minutes')->default(0);
            $t->text('findings')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });

        $this->createIfMissing('inventory_audit_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('audit_id')->constrained('inventory_audits')->cascadeOnDelete();
            $t->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $t->string('unregistered_tag')->nullable();
            $t->string('result', 50)->index();
            $t->foreignId('seen_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $t->foreignId('seen_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->string('condition', 40)->nullable();
            $t->string('photo_path')->nullable();
            $t->timestamp('scanned_at')->nullable();
            $t->foreignId('auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('remarks')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['audit_id', 'asset_id']);
        });

        $this->createIfMissing('approval_flows', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('subject_type', 150)->index();
            $t->string('mode', 20)->default('sequential');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });

        $this->createIfMissing('approval_flow_steps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('approval_flow_id')->constrained('approval_flows')->cascadeOnDelete();
            $t->unsignedSmallInteger('sequence');
            $t->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('approver_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $t->string('mode', 20)->default('sequential');
            $t->boolean('allow_return_for_revision')->default(true);
            $t->unsignedSmallInteger('escalate_after_hours')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['approval_flow_id', 'sequence']);
        });

        $this->createIfMissing('number_sequences', function (Blueprint $t) {
            $t->id();
            $t->string('key', 80)->unique();
            $t->unsignedBigInteger('current_value')->default(0);
            $t->timestamps();
        });

        // Add ownership columns to all newly normalized operational records.
        foreach ([
            'stock_counts', 'stock_count_items', 'software', 'licenses', 'license_assignments',
            'license_renewals', 'purchase_requests', 'purchase_request_items', 'quotations',
            'purchase_orders', 'receiving_reports', 'receiving_report_items', 'maintenance_schedules',
            'inventory_audit_items', 'approval_flows', 'approval_flow_steps',
        ] as $table) {
            $this->add($table, 'created_by', fn (Blueprint $t) => $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete());
            $this->add($table, 'updated_by', fn (Blueprint $t) => $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete());
        }

        // Retain compatibility with the existing simple approval screen while
        // linking each approval row to the normalized flow step when configured.
        if (Schema::hasTable('approvals') && Schema::hasTable('approval_flow_steps')) {
            // Foreign key is intentionally omitted here; the step can be removed
            // only by an administrator and historical approvals must survive.
        }

        if (DB::getDriverName() === 'mysql') {
            foreach (['audit_logs', 'asset_histories', 'stock_movements'] as $table) {
                DB::unprepared("DROP TRIGGER IF EXISTS trg_{$table}_no_update");
                DB::unprepared("DROP TRIGGER IF EXISTS trg_{$table}_no_delete");
                DB::unprepared("CREATE TRIGGER trg_{$table}_no_update BEFORE UPDATE ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'This ledger is append-only'");
                DB::unprepared("CREATE TRIGGER trg_{$table}_no_delete BEFORE DELETE ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'This ledger is append-only'");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            foreach (['audit_logs', 'asset_histories', 'stock_movements'] as $table) {
                DB::unprepared("DROP TRIGGER IF EXISTS trg_{$table}_no_update");
                DB::unprepared("DROP TRIGGER IF EXISTS trg_{$table}_no_delete");
            }
        }

        if (Schema::hasTable('assets') && Schema::hasColumn('assets', 'purchase_order_id')) {
            Schema::table('assets', function (Blueprint $t) {
                $t->dropConstrainedForeignId('purchase_order_id');
            });
        }

        foreach ([
            'number_sequences', 'approval_flow_steps', 'approval_flows', 'inventory_audit_items',
            'work_orders', 'maintenance_schedules', 'receiving_report_items', 'receiving_reports',
            'purchase_orders', 'quotations', 'purchase_request_items', 'purchase_requests',
            'license_renewals', 'license_assignments', 'licenses', 'software', 'stock_count_items',
            'stock_counts', 'stock_movements', 'depreciation_entries', 'asset_histories', 'login_histories',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        // Keep this migration's down operation intentionally conservative for
        // extension columns: deployments may already have populated them.
    }

    private function add(string $table, string $column, Closure $definition): void
    {
        if (Schema::hasTable($table) && ! Schema::hasColumn($table, $column)) {
            Schema::table($table, $definition);
        }
    }

    private function createIfMissing(string $table, Closure $definition): void
    {
        if (! Schema::hasTable($table)) {
            Schema::create($table, $definition);
        }
    }
};
