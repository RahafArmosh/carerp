<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advance_sale_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('advance_sale_order_no')->default('0');
            $table->unsignedBigInteger('customer_id');
            $table->string('customer_trn_no')->nullable();
            $table->date('sales_order_date');
            $table->foreignId('currency_id')->nullable()->constrained();
            $table->decimal('exchange_rate', 10, 6)->default(1.0);
            $table->string('tax_id')->nullable();
            $table->string('status')->default('draft')->comment('draft, sent, approved, converted');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('created_by')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('advance_sale_order_no');
            $table->index('status');
            $table->index('invoice_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_sale_orders');
    }
};
