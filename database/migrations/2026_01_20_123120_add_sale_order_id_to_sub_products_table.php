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
        Schema::table('sub_products', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_order_id')->nullable()->after('pos_id');
            $table->foreign('sale_order_id')->references('id')->on('sale_orders')->onDelete('set null');
            $table->index('sale_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_products', function (Blueprint $table) {
            $table->dropForeign(['sale_order_id']);
            $table->dropIndex(['sale_order_id']);
            $table->dropColumn('sale_order_id');
        });
    }
};
