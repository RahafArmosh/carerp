<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('deal_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deal_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(1);
            $table->decimal('price', 15, 2)->default(0.00);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Foreign keys (optional if you want constraints)
            $table->foreign('deal_id')->references('id')->on('deals')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('product_services')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deal_products');
    }
};
