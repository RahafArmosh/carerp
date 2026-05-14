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
        // Schema::create('pos_refund_items', function (Blueprint $table) {
        //     $table->id();
        //     $table->unsignedBigInteger('refund_id');
        //     $table->unsignedBigInteger('pos_products_id')->nullable();
        //     $table->string('product_no', 255)->nullable();
        //     $table->integer('quantity')->default('0');
        //     $table->decimal('return_price', 15, 2)->default('0.00');
        //     $table->unsignedBigInteger('combo_id')->nullable();
        //     $table->unsignedBigInteger('price_list_id')->nullable();
        //     $table->timestamps();
            
        //     $table->foreign('refund_id')->references('id')->on('pos_refunds')->onDelete('cascade');
        //     $table->index('refund_id');
        //     $table->index('pos_products_id');
        //     $table->index('product_no');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::dropIfExists('pos_refund_items');
    }
};
