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
        Schema::create('asn_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asn_id');
            $table->string('box_no')->nullable();
            $table->string('supplier_po_no')->nullable();
            $table->unsignedBigInteger('our_pro_id')->nullable(); // References PRO (id)
            $table->string('our_pro_no')->nullable(); // PRO number string for display
            $table->string('order_ref')->nullable();
            $table->string('part_no')->nullable();
            $table->text('description')->nullable();
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('received_qty', 15, 2)->default(0);
            $table->decimal('discrepancy', 15, 2)->default(0); // Calculated: received_qty - qty
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0); // Calculated: received_qty * unit_price
            $table->decimal('unit_weight', 15, 2)->default(0);
            $table->decimal('total_weight', 15, 2)->default(0); // Calculated: received_qty * unit_weight
            $table->string('hs_code')->nullable();
            $table->string('container_no')->nullable();
            $table->string('dec_no')->nullable();
            $table->date('dec_date')->nullable();
            $table->string('origin')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('asn_id')->references('id')->on('asns')->onDelete('cascade');
            $table->foreign('our_pro_id')->references('id')->on('pros')->onDelete('set null');
            $table->index('asn_id');
            $table->index('our_pro_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asn_items');
    }
};
