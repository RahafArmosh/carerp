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
        Schema::create('sub_products', function (Blueprint $table) {
            $table->id();
            $table->string('chassis_no')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('product_services')->onDelete('cascade');
            $table->integer('bill_id');
            $table->integer('invoice_id')->nullable();
            $table->integer('pos_id')->nullable();
            $table->string('product_no')->nullable();
            $table->integer('quantity')->default('1');
            // $table->foreignId('exterior_color_id')->nullable()->constrained('colors');
            // $table->foreignId('interior_color_id')->nullable()->constrained('colors');
            $table->decimal('sale_price', 10, 2);
            $table->decimal('purchase_price', 10, 2);
            $table->integer('created_by')->default('0');
            $table->boolean('flag')->comment('0 => ordered, 1 => purchased , 2 => cancelled')->default(0);
            $table->boolean('booked')->comment('0 => free, 1 => booked , 2 => sold , 3 => delivered')->default(0);
            // $table->unsignedBigInteger('country_id');
            $table->timestamps();

            // $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_products');
    }
};
