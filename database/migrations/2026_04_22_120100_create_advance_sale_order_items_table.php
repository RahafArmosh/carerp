<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advance_sale_order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('advance_sale_order_id');
            $table->string('part_no')->nullable();
            $table->text('description')->nullable();
            $table->decimal('req_qty', 16, 2)->default(0);
            $table->decimal('stock_qty', 16, 2)->default(0);
            $table->decimal('picking_qty', 16, 2)->default(0);
            $table->decimal('packed_qty', 16, 2)->default(0);
            $table->decimal('discrepancy', 16, 2)->default(0);
            $table->decimal('unit_price', 16, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('advance_sale_order_id')->references('id')->on('advance_sale_orders')->onDelete('cascade');
            $table->index('advance_sale_order_id');
            $table->index('part_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_sale_order_items');
    }
};
