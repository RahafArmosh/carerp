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
        Schema::table('asn_items', function (Blueprint $table) {
            $table->unsignedBigInteger('sub_product_id')->nullable()->after('asn_id');
            $table->unsignedBigInteger('bill_id')->nullable()->after('sub_product_id');
            
            $table->foreign('sub_product_id')->references('id')->on('sub_products')->onDelete('set null');
            $table->foreign('bill_id')->references('id')->on('bills')->onDelete('set null');
            $table->index('sub_product_id');
            $table->index('bill_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asn_items', function (Blueprint $table) {
            $table->dropForeign(['sub_product_id']);
            $table->dropForeign(['bill_id']);
            $table->dropIndex(['sub_product_id']);
            $table->dropIndex(['bill_id']);
            $table->dropColumn(['sub_product_id', 'bill_id']);
        });
    }
};
