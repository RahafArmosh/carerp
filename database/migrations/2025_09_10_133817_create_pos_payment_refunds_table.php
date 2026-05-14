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
        Schema::create('pos_payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->integer('pos_id')->default('0');
            $table->decimal('amount',15,2)->default('0.00');
            $table->text('description')->nullable();
            $table->integer('creator_id')->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_payment_refunds');
    }
};
