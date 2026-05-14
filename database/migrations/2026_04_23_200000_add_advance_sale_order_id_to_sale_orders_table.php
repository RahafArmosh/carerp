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
        Schema::table('sale_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_orders', 'advance_sale_order_id')) {
                $table->unsignedBigInteger('advance_sale_order_id')->nullable()->after('sale_order_no');
                $table->index('advance_sale_order_id');
                $table->foreign('advance_sale_order_id')->references('id')->on('advance_sale_orders')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sale_orders', 'advance_sale_order_id')) {
                $table->dropForeign(['advance_sale_order_id']);
                $table->dropIndex(['advance_sale_order_id']);
                $table->dropColumn('advance_sale_order_id');
            }
        });
    }
};
