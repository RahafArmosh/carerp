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
            $table->decimal('combo_price', 15, 2)->nullable()->after('price')->comment('Combo price per item if combo is applied');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_products', function (Blueprint $table) {
            $table->dropColumn('combo_price');
        });
    }
};
