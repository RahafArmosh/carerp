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
        Schema::table('journal_items', function (Blueprint $table) {
            $table->unsignedBigInteger('sub_product_id')->nullable();
            $table->foreign('sub_product_id')->references('id')->on('sub_products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_items', function (Blueprint $table) {
            $table->dropForeign(['sub_product_id']);
            $table->dropColumn('sub_product_id');
        });
    }
};
