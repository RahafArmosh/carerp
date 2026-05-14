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
        Schema::create('bill_status_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bill_id');
            $table->integer('status');
            $table->integer('payment_status');
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();
            $table->foreign('bill_id')->references('id')->on('bills')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_status_changes');
    }
};
