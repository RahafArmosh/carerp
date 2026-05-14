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
        Schema::create('custom_field_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('custom_field_id');
            $table->unsignedBigInteger('product_service_category_id');
            $table->timestamps();

            $table->foreign('custom_field_id')
                  ->references('id')
                  ->on('custom_fields')
                  ->onDelete('cascade');
            
            $table->foreign('product_service_category_id')
                  ->references('id')
                  ->on('product_service_categories')
                  ->onDelete('cascade');

            // Ensure unique combination
            $table->unique(['custom_field_id', 'product_service_category_id'], 'custom_field_category_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_field_category');
    }
};
