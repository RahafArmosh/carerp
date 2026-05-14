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
        Schema::create('packing_lists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('packing_list_no')->default('0');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('sale_order_id')->nullable();
            $table->unsignedBigInteger('pick_list_id')->nullable();
            $table->string('packing_ref')->nullable();
            $table->date('packing_list_date');
            $table->unsignedBigInteger('packed_by')->nullable()->comment('User ID who packed the items');
            $table->string('status')->default('draft')->comment('draft, packed, shipped, delivered');
            $table->unsignedBigInteger('created_by')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('sale_order_id')->references('id')->on('sale_orders')->onDelete('set null');
            $table->foreign('pick_list_id')->references('id')->on('pick_lists')->onDelete('set null');
            
            // Indexes
            $table->index('customer_id');
            $table->index('sale_order_id');
            $table->index('pick_list_id');
            $table->index('packing_list_no');
            $table->index('status');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_lists');
    }
};
