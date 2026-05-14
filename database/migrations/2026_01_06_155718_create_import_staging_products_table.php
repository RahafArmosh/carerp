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
        Schema::create('import_staging_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_session_id'); // To group products from same import
            $table->unsignedBigInteger('created_by');
            
            // Bill header data (stored as JSON)
            $table->json('bill_data')->nullable();
            
            // Product row data
            $table->string('sku')->nullable();
            $table->unsignedBigInteger('product_id')->nullable(); // Matched product ID
            $table->string('product_name')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('sub_brand_name')->nullable();
            $table->string('category_name')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('sale_price', 16, 2)->default(0);
            $table->decimal('purchase_price', 16, 2)->default(0);
            $table->decimal('discount', 16, 2)->default(0);
            $table->string('product_no')->nullable();
            
            // Custom fields (stored as JSON)
            $table->json('custom_fields')->nullable();
            
            // Status flags
            $table->enum('status', ['FOUND', 'MISSING'])->default('MISSING');
            $table->text('status_message')->nullable();
            
            // Original row data (for reference)
            $table->json('original_row_data')->nullable();
            $table->integer('row_number')->nullable(); // Original row number in Excel
            
            $table->timestamps();
            
            // Indexes
            $table->index('import_session_id');
            $table->index('created_by');
            $table->index('status');
            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_staging_products');
    }
};
