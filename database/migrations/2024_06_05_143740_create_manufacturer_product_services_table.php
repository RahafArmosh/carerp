<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('manufacturer_product_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained('product_services')->onDelete('cascade');
            $table->foreignId('subproduct_id')->constrained('product_services')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufacturer_product_services');
    }
};
