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
        Schema::table('sale_order_items', function (Blueprint $table) {
            $table->decimal('picking_qty', 16, 2)->default(0)->after('stock_qty')->comment('Picked Quantity from Pick List');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_order_items', function (Blueprint $table) {
            $table->dropColumn('picking_qty');
        });
    }
};
