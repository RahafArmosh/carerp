<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stores how much qty was taken from this sub-product when booked for a sale order,
     * so we can distribute discrepancy return correctly (return to each sub-product its share).
     */
    public function up(): void
    {
        Schema::table('sub_products', function (Blueprint $table) {
            $table->decimal('so_qty_reserved', 16, 2)->nullable()->after('sale_order_id')
                ->comment('Qty reserved from this sub-product for the linked sale order (Qty products only)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_products', function (Blueprint $table) {
            $table->dropColumn('so_qty_reserved');
        });
    }
};
