<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_product_id')
                ->constrained('sub_products')
                ->cascadeOnDelete();
            $table->string('file_name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_product_images');
    }
};
