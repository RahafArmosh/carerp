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
        Schema::create('asn_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asn_id');
            $table->unsignedBigInteger('bill_id');
            $table->timestamps();

            $table->foreign('asn_id')->references('id')->on('asns')->onDelete('cascade');
            $table->foreign('bill_id')->references('id')->on('bills')->onDelete('cascade');
            $table->unique('bill_id'); // one bill belongs to one ASN
            $table->index('asn_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asn_bills');
    }
};
