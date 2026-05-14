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
        Schema::table('pos_products_refunds', function (Blueprint $table) {
            $table->unsignedBigInteger('voucher_id')->nullable()->after('price_list_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_products_refunds', function (Blueprint $table) {
            $table->dropColumn('voucher_id');
        });
    }
};
