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
        Schema::create('pos_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action_type'); // e.g., 'add_to_cart', 'remove_from_cart', 'update_quantity', 'create_order', 'update_discount', 'apply_voucher', 'process_payment', 'delete_order', 'select_customer', 'select_warehouse', 'print_receipt'
            $table->unsignedBigInteger('pos_id')->nullable(); // Reference to pos table
            $table->unsignedBigInteger('user_id'); // Who performed the action
            $table->integer('warehouse_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable(); // ProductService id
            $table->string('product_no')->nullable(); // Product number
            $table->integer('quantity')->nullable(); // Quantity changed
            $table->json('old_value')->nullable(); // Previous state/data
            $table->json('new_value')->nullable(); // New state/data
            $table->text('description')->nullable(); // Additional details
            $table->string('ip_address', 45)->nullable(); // IP address for audit
            $table->integer('created_by')->default('0'); // For multi-tenant support
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('action_type');
            $table->index('pos_id');
            $table->index('user_id');
            $table->index('warehouse_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_logs');
    }
};
