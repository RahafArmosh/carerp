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
        Schema::create('grns', function (Blueprint $table) {
            $table->id();
            $table->string('grn_no');
            $table->unsignedBigInteger('asn_id'); // Reference to ASN
            $table->unsignedBigInteger('supplier_id')->nullable(); // References vender
            $table->string('supplier_name')->nullable();
            $table->date('grn_date');
            $table->string('status')->default('draft'); // draft, received, completed, cancelled
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('asn_id')->references('id')->on('asns')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('venders')->onDelete('set null');
            $table->index('grn_no');
            $table->index('asn_id');
            $table->index('supplier_id');
            $table->index('created_by');
            $table->index('status');
            
            // Unique constraint: grn_no per creator
            $table->unique(['grn_no', 'created_by'], 'grns_grn_no_created_by_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grns');
    }
};
