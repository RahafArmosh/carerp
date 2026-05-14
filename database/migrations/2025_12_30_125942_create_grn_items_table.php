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
        Schema::create('grn_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grn_id');
            $table->unsignedBigInteger('asn_item_id'); // Reference to ASN item
            $table->string('part_no')->nullable(); // Product number/barcode
            $table->text('description')->nullable();
            $table->decimal('qty', 15, 2)->default(0); // Expected quantity from ASN
            $table->decimal('received_qty', 15, 2)->default(0); // Actual received quantity
            $table->decimal('discrepancy', 15, 2)->default(0); // Calculated: received_qty - qty
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0); // Calculated: received_qty * unit_price
            $table->unsignedBigInteger('product_id')->nullable(); // Reference to product if matched
            $table->unsignedBigInteger('sub_product_id')->nullable(); // Reference to sub_product if matched
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('grn_id')->references('id')->on('grns')->onDelete('cascade');
            $table->foreign('asn_item_id')->references('id')->on('asn_items')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('product_services')->onDelete('set null');
            $table->foreign('sub_product_id')->references('id')->on('sub_products')->onDelete('set null');
            $table->index('grn_id');
            $table->index('asn_item_id');
            $table->index('part_no');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grn_items');
    }
};
