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
        Schema::create('pick_lists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sales_order_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('packing_ref')->nullable();
            $table->date('pick_list_date');
            $table->unsignedBigInteger('picked_by')->nullable()->comment('User ID who picked the items');
            $table->unsignedBigInteger('created_by')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('sales_order_id')->references('id')->on('sale_orders')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            
            // Indexes
            $table->index('sales_order_id');
            $table->index('customer_id');
            $table->index('pick_list_date');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pick_lists');
    }
};
