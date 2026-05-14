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
        Schema::table('pos_products', function (Blueprint $table) {

            $table->unsignedBigInteger('compo_id')->nullable()->after('product_id');
            $table->decimal('pricelist_price', 15, 2)->default('0.00')->after('price');
            $table->unsignedBigInteger('price_list_id')->nullable()->after('pricelist_price');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_products', function (Blueprint $table) {
            //
        });
    }
};
