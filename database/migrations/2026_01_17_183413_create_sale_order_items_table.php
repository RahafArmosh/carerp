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
        Schema::create('sale_order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sale_order_id');
            $table->string('part_no')->nullable();
            $table->text('description')->nullable();
            $table->decimal('req_qty', 16, 2)->default(0)->comment('Required Quantity');
            $table->decimal('stock_qty', 16, 2)->default(0)->comment('Stock Quantity');
            $table->decimal('packed_qty', 16, 2)->default(0)->comment('Packed Quantity');
            $table->decimal('discrepancy', 16, 2)->default(0)->comment('Discrepancy (packed_qty - req_qty)');
            $table->decimal('unit_price', 16, 2)->default(0);
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('sub_product_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('sale_order_id')->references('id')->on('sale_orders')->onDelete('cascade');
            
            // Indexes
            $table->index('sale_order_id');
            $table->index('part_no');
            $table->index('product_id');
            $table->index('sub_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_order_items');
    }
};
