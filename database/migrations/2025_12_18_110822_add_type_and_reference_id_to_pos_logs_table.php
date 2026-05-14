<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pos_logs', function (Blueprint $table) {
            $table->string('type')->nullable()->after('action_type'); // e.g., 'warehouse', 'payment_method', 'combo', 'voucher', 'price_list', 'transfer', 'pos', 'pos_refund'
            $table->unsignedBigInteger('reference_id')->nullable()->after('type'); // ID of the related model (warehouse_id, payment_method_id, combo_id, etc.)
            
            // Add indexes for better query performance
            $table->index('type');
            $table->index('reference_id');
            $table->index(['type', 'reference_id']); // Composite index for filtering by type and id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_logs', function (Blueprint $table) {
            $table->dropIndex(['type', 'reference_id']);
            $table->dropIndex(['reference_id']);
            $table->dropIndex(['type']);
            $table->dropColumn(['type', 'reference_id']);
        });
    }
};
