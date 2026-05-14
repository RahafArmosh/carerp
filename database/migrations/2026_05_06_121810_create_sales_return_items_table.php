<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sales_return_id');
            $table->unsignedBigInteger('invoice_product_id');
            $table->unsignedBigInteger('sub_product_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 16, 2)->default(0);
            $table->decimal('unit_price', 16, 2)->default(0);
            $table->unsignedBigInteger('created_by')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
    }
};
