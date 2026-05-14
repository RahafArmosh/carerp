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
        Schema::create('pro_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pro_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('sub_product_id')->nullable();
            $table->string('part_no')->nullable();
            $table->text('description')->nullable();
            $table->decimal('order_qty', 15, 2)->default(0);
            $table->decimal('supplied_qty', 15, 2)->default(0);
            $table->decimal('remaining_qty', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('pro_id')->references('id')->on('pros')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('product_services')->onDelete('set null');
            $table->foreign('sub_product_id')->references('id')->on('sub_products')->onDelete('set null');
            $table->index('pro_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pro_items');
    }
};
