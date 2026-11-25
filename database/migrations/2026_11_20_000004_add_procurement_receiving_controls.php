<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('procurements', function (Blueprint $table) {
            $table->unsignedInteger('quantity_received')->default(0)->after('quantity');
            $table->unsignedInteger('quantity_rejected')->default(0)->after('quantity_received');
            $table->foreignId('received_by_user_id')->nullable()->after('received_at')->constrained('users')->nullOnDelete();
            $table->text('inspection_remarks')->nullable()->after('inspection_result');
        });
    }

    public function down(): void
    {
        Schema::table('procurements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('received_by_user_id');
            $table->dropColumn(['quantity_received', 'quantity_rejected', 'inspection_remarks']);
        });
    }
};
