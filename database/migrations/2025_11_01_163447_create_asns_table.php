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
        Schema::create('asns', function (Blueprint $table) {
            $table->id();
            $table->string('asn_no')->unique();
            $table->unsignedBigInteger('supplier_id')->nullable(); // References vender
            $table->string('supplier_name')->nullable();
            $table->string('supplier_code')->nullable();
            $table->string('supplier_inv_no')->nullable();
            $table->string('container_no')->nullable();
            $table->string('dec_no')->nullable();
            $table->date('dec_date')->nullable();
            $table->date('asn_date');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('supplier_id')->references('id')->on('venders')->onDelete('set null');
            $table->index('asn_no');
            $table->index('supplier_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asns');
    }
};
