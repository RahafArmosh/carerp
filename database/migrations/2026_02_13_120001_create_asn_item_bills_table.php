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
        Schema::create('asn_item_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asn_item_id');
            $table->unsignedBigInteger('bill_id');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('asn_item_id')->references('id')->on('asn_items')->onDelete('cascade');
            $table->foreign('bill_id')->references('id')->on('bills')->onDelete('cascade');
            $table->index(['asn_item_id', 'bill_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asn_item_bills');
    }
};
