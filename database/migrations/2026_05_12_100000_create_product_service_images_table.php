<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_service_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_service_id')
                ->constrained('product_services')
                ->cascadeOnDelete();
            $table->string('file_name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_service_images');
    }
};
