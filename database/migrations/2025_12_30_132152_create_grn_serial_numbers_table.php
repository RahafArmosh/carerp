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
        Schema::create('grn_serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grn_id');
            $table->unsignedBigInteger('serial_number_id');
            $table->unsignedBigInteger('grn_item_id')->nullable(); // Optional: link to specific GRN item
            $table->timestamps();
            
            $table->foreign('grn_id')->references('id')->on('grns')->onDelete('cascade');
            $table->foreign('serial_number_id')->references('id')->on('serial_numbers')->onDelete('cascade');
            $table->foreign('grn_item_id')->references('id')->on('grn_items')->onDelete('set null');
            
            $table->index('grn_id');
            $table->index('serial_number_id');
            $table->index('grn_item_id');
            
            // Prevent duplicate associations
            $table->unique(['grn_id', 'serial_number_id'], 'grn_serial_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grn_serial_numbers');
    }
};
