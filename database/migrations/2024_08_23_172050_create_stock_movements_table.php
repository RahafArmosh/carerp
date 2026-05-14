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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('product_services')->onDelete('cascade');
            $table->foreignId('sub_product_id')->constrained('sub_products')->onDelete('cascade');
            $table->foreignId('bill_id')->nullable()->constrained('bills')->onDelete('cascade'); // For stock in
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('cascade'); // For stock out
            $table->integer('qty_in')->nullable(); // Quantity in
            $table->integer('qty_out')->nullable(); // Quantity out
            $table->decimal('avg_cost', 10, 2)->nullable(); // Average cost for selling product
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
