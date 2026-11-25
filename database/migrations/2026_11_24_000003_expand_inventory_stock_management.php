<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_item_locations')) {
            Schema::create('inventory_item_locations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
                $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedInteger('quantity_on_hand')->default(0);
                $table->timestamps();
                $table->index(['inventory_item_id', 'location_id']);
                $table->index(['office_id', 'location_id']);
            });
        }

        if (! Schema::hasTable('inventory_batches')) {
            Schema::create('inventory_batches', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
                $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
                $table->string('batch_no', 100);
                $table->date('expiry_date')->nullable();
                $table->unsignedInteger('quantity_on_hand')->default(0);
                $table->decimal('unit_cost', 14, 2)->default(0);
                $table->date('received_at')->nullable();
                $table->timestamps();
                $table->index(['inventory_item_id', 'expiry_date']);
                $table->index(['office_id', 'location_id', 'expiry_date']);
            });
        }

        if (! Schema::hasTable('stock_reservations')) {
            Schema::create('stock_reservations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
                $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedInteger('quantity');
                $table->unsignedInteger('fulfilled_qty')->default(0);
                $table->string('status', 24)->default('active')->index();
                $table->string('reserved_for', 180)->nullable();
                $table->text('purpose')->nullable();
                $table->foreignId('reserved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamp('fulfilled_at')->nullable();
                $table->timestamp('released_at')->nullable();
                $table->timestamps();
                $table->index(['inventory_item_id', 'status', 'location_id']);
            });
        }

        if (! Schema::hasTable('stock_transfers')) {
            Schema::create('stock_transfers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
                $table->string('reference_no', 80)->unique();
                $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('quantity');
                $table->foreignId('from_location_id')->constrained('locations')->restrictOnDelete();
                $table->foreignId('to_location_id')->constrained('locations')->restrictOnDelete();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 24)->default('completed')->index();
                $table->text('remarks')->nullable();
                $table->timestamp('transferred_at')->nullable();
                $table->timestamps();
                $table->index(['inventory_item_id', 'transferred_at']);
            });
        }

        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table): void {
                if (! Schema::hasColumn('stock_movements', 'batch_id')) {
                    $table->foreignId('batch_id')->nullable()->after('stock_item_id')->constrained('inventory_batches')->nullOnDelete();
                }
                if (! Schema::hasColumn('stock_movements', 'location_id')) {
                    $table->foreignId('location_id')->nullable()->after('batch_id')->constrained()->nullOnDelete();
                }
                if (! Schema::hasColumn('stock_movements', 'to_location_id')) {
                    $table->foreignId('to_location_id')->nullable()->after('location_id')->constrained('locations')->nullOnDelete();
                }
                if (! Schema::hasColumn('stock_movements', 'reference_text')) {
                    $table->string('reference_text', 120)->nullable()->after('reference_id');
                }
            });
        }

        if (Schema::hasTable('receiving_report_items')) {
            Schema::table('receiving_report_items', function (Blueprint $table): void {
                if (! Schema::hasColumn('receiving_report_items', 'batch_no')) $table->string('batch_no', 100)->nullable()->after('registered_as');
                if (! Schema::hasColumn('receiving_report_items', 'expiry_date')) $table->date('expiry_date')->nullable()->after('batch_no');
            });
        }

        if (Schema::hasTable('stock_count_items')) {
            Schema::table('stock_count_items', function (Blueprint $table): void {
                if (! Schema::hasColumn('stock_count_items', 'location_id')) {
                    $table->foreignId('location_id')->nullable()->after('stock_item_id')->constrained()->nullOnDelete();
                }
            });
            // One item may now be counted separately at more than one location.
            // MySQL may be using the old composite UNIQUE index to support the
            // stock_count_id foreign key. Add a replacement non-unique index
            // first, so the FK remains valid when the UNIQUE index is removed.
            $existingIndexes = Schema::getIndexes('stock_count_items');
            $legacyUniqueName = null;
            $hasStockCountLeadingIndex = false;

            foreach ($existingIndexes as $index) {
                $columns = $index['columns'] ?? [];

                if (($index['unique'] ?? false) && $columns === ['stock_count_id', 'stock_item_id']) {
                    $legacyUniqueName = $index['name'];
                    continue;
                }

                if (($columns[0] ?? null) === 'stock_count_id') {
                    $hasStockCountLeadingIndex = true;
                }
            }

            if (! $hasStockCountLeadingIndex) {
                Schema::table('stock_count_items', function (Blueprint $table): void {
                    $table->index('stock_count_id', 'stock_count_items_stock_count_id_index');
                });
            }

            if ($legacyUniqueName !== null) {
                Schema::table('stock_count_items', function (Blueprint $table) use ($legacyUniqueName): void {
                    $table->dropUnique($legacyUniqueName);
                });
            }
            $hasLocationUnique = collect(Schema::getIndexes('stock_count_items'))->contains(fn ($index) => ($index['name'] ?? '') === 'stock_count_item_location_unique');
            if (! $hasLocationUnique) {
                Schema::table('stock_count_items', fn (Blueprint $table) => $table->unique(['stock_count_id', 'stock_item_id', 'location_id'], 'stock_count_item_location_unique'));
            }
        }

        // Preserve every existing balance by treating the item's current
        // location as its initial stockholding location (including NULL).
        if (Schema::hasTable('inventory_items') && Schema::hasTable('inventory_item_locations')) {
            DB::table('inventory_items')->orderBy('id')->chunkById(300, function ($items): void {
                foreach ($items as $item) {
                    $query = DB::table('inventory_item_locations')->where('inventory_item_id', $item->id);
                    if ($item->location_id === null) {
                        $query->whereNull('location_id');
                    } else {
                        $query->where('location_id', $item->location_id);
                    }
                    if (! $query->exists()) {
                        DB::table('inventory_item_locations')->insert([
                            'office_id' => $item->office_id ?? null,
                            'inventory_item_id' => $item->id,
                            'location_id' => $item->location_id,
                            'quantity_on_hand' => (int) $item->quantity_on_hand,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
        }

        // Reconstruct positive lot balances from historical signed ledger rows.
        if (Schema::hasTable('stock_movements') && Schema::hasTable('inventory_batches')) {
            $lots = DB::table('stock_movements')
                ->select('stock_item_id', 'batch_no', 'expiry_date', DB::raw('MAX(unit_cost) AS unit_cost'), DB::raw('SUM(qty) AS balance'))
                ->whereNotNull('batch_no')
                ->groupBy('stock_item_id', 'batch_no', 'expiry_date')
                ->havingRaw('SUM(qty) > 0')
                ->get();
            foreach ($lots as $lot) {
                $item = DB::table('inventory_items')->where('id', $lot->stock_item_id)->first();
                if (! $item) continue;
                $exists = DB::table('inventory_batches')->where('inventory_item_id', $lot->stock_item_id)->where('batch_no', $lot->batch_no)->where('expiry_date', $lot->expiry_date)->exists();
                if (! $exists) {
                    DB::table('inventory_batches')->insert([
                        'office_id' => $item->office_id ?? null,
                        'inventory_item_id' => $lot->stock_item_id,
                        'location_id' => $item->location_id,
                        'batch_no' => $lot->batch_no,
                        'expiry_date' => $lot->expiry_date,
                        'quantity_on_hand' => (int) $lot->balance,
                        'unit_cost' => $lot->unit_cost ?? 0,
                        'received_at' => today()->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('receiving_report_items')) {
            Schema::table('receiving_report_items', function (Blueprint $table): void {
                if (Schema::hasColumn('receiving_report_items', 'expiry_date')) $table->dropColumn('expiry_date');
                if (Schema::hasColumn('receiving_report_items', 'batch_no')) $table->dropColumn('batch_no');
            });
        }
        if (Schema::hasTable('stock_count_items') && Schema::hasColumn('stock_count_items', 'location_id')) {
            $indexes = Schema::getIndexes('stock_count_items');
            if (collect($indexes)->contains(fn ($index) => ($index['name'] ?? '') === 'stock_count_item_location_unique')) {
                Schema::table('stock_count_items', fn (Blueprint $table) => $table->dropUnique('stock_count_item_location_unique'));
            }
            if (! collect(Schema::getIndexes('stock_count_items'))->contains(fn ($index) => ($index['unique'] ?? false) && ($index['columns'] ?? []) === ['stock_count_id', 'stock_item_id'])) {
                Schema::table('stock_count_items', fn (Blueprint $table) => $table->unique(['stock_count_id', 'stock_item_id']));
            }
            Schema::table('stock_count_items', fn (Blueprint $table) => $table->dropConstrainedForeignId('location_id'));
        }
        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table): void {
                foreach (['batch_id', 'location_id', 'to_location_id'] as $column) {
                    if (Schema::hasColumn('stock_movements', $column)) $table->dropConstrainedForeignId($column);
                }
                if (Schema::hasColumn('stock_movements', 'reference_text')) $table->dropColumn('reference_text');
            });
        }
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('inventory_item_locations');
    }
};
