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
        Schema::create('pros', function (Blueprint $table) {
            $table->id();
            $table->string('pro_no')->unique();
            $table->unsignedBigInteger('supplier_id')->nullable(); // References vender
            $table->string('supplier_name')->nullable();
            $table->string('supplier_code')->nullable();
            $table->date('po_date');
            $table->string('supplier_proforma_no')->nullable();
            $table->date('supplier_proforma_date')->nullable();
            $table->string('our_order_ref')->nullable();
            $table->string('supplier_ref')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('supplier_id')->references('id')->on('venders')->onDelete('set null');
            $table->index('pro_no');
            $table->index('supplier_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pros');
    }
};
