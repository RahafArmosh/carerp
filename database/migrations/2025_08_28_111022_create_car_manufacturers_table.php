<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_manufacturers', function (Blueprint $table) {
            $table->id();

            // Car (sub-product)
            $table->unsignedBigInteger('car_id');

            // Accessory (sub-product)
            $table->unsignedBigInteger('accessory_id');

            // Extra fields
            $table->integer('quantity')->default(1);
            $table->decimal('sell_price', 12, 2)->nullable();
            $table->string('request_no')->nullable();
            $table->date('request_date')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('car_id')->references('id')->on('sub_products')->onDelete('cascade');
            $table->foreign('accessory_id')->references('id')->on('sub_products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_manufacturers');
    }
};

