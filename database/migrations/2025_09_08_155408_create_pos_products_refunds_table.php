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
        Schema::create('pos_products_refunds', function (Blueprint $table) {
            $table->id();
            $table->integer('pos_id')->default('0');
            $table->unsignedBigInteger('pos_products_id')->nullable();
            $table->integer('quantity')->default('0');
            $table->decimal('return_price',15,2)->default('0.00');
            $table->text('description')->nullable();
            $table->text('product_no')->nullable();
            $table->unsignedBigInteger('combo_id')->nullable();
            $table->unsignedBigInteger('price_list_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_products_refunds');
    }
};
