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
        Schema::create('car_accessory_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('car_id');
            $table->unsignedBigInteger('accessory_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('sell_price', 12, 2)->nullable();
            $table->timestamps();
        
            $table->foreign('request_id')->references('id')->on('car_accessory_requests')->onDelete('cascade');
            $table->foreign('car_id')->references('id')->on('sub_products')->onDelete('cascade');
            $table->foreign('accessory_id')->references('id')->on('sub_products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_accessory_request_items');
    }
};
