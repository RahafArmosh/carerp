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
        Schema::create('price_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained();
            $table->enum('apply_to', ['product', 'category', 'brand', 'sub_brand']);
            $table->unsignedBigInteger('target_id');
            $table->enum('price_mode', ['discount', 'formula', 'fixed']);
            $table->decimal('value', 10, 2);
            $table->boolean('apply_99')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_list_roles');
    }
};
