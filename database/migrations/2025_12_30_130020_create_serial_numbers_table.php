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
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grn_id'); // Many serial numbers belong to one GRN
            $table->unsignedBigInteger('grn_item_id')->nullable(); // Optional: link to specific GRN item
            $table->string('serial_number')->unique(); // Unique serial number
            $table->string('part_no')->nullable(); // Product number/barcode
            $table->unsignedBigInteger('product_id')->nullable(); // Reference to product
            $table->unsignedBigInteger('sub_product_id')->nullable(); // Reference to sub_product
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('grn_id')->references('id')->on('grns')->onDelete('cascade');
            $table->foreign('grn_item_id')->references('id')->on('grn_items')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('product_services')->onDelete('set null');
            $table->foreign('sub_product_id')->references('id')->on('sub_products')->onDelete('set null');
            $table->index('grn_id');
            $table->index('grn_item_id');
            $table->index('serial_number');
            $table->index('part_no');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_numbers');
    }
};
