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
        Schema::create('pricing_list_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pricing_list_id');
                $table->decimal('price', 15, 4);
                $table->unsignedBigInteger('created_by');
                $table->timestamps();

                $table->foreign('pricing_list_id')->references('id')->on('pricing_lists')->onDelete('cascade');
            });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_list_histories');
    }
};
