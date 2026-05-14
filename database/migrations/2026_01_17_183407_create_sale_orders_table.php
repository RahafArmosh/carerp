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
        Schema::create('sale_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('sale_order_no')->default('0');
            $table->unsignedBigInteger('customer_id');
            $table->string('customer_trn_no')->nullable();
            $table->date('sales_order_date');
            $table->foreignId('currency_id')->nullable()->constrained();
            $table->decimal('exchange_rate', 10, 6)->default(1.0);
            $table->string('status')->default('draft')->comment('draft, sent, approved, converted');
            $table->unsignedBigInteger('invoice_id')->nullable()->comment('Invoice ID when converted to invoice');
            $table->unsignedBigInteger('created_by')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('customer_id');
            $table->index('sale_order_no');
            $table->index('status');
            $table->index('invoice_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_orders');
    }
};
