<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeTieredPricesToDecimalInComboOffersTable extends Migration
{
    public function up()
    {
        Schema::table('combo_offers', function (Blueprint $table) {
            $table->dropColumn('tiered_prices');
        });

        Schema::table('combo_offers', function (Blueprint $table) {
            // Add new decimal column instead
            $table->decimal('tiered_price', 8, 2)->nullable()->after('get_quantity');
        });
    }

    public function down()
    {
        Schema::table('combo_offers', function (Blueprint $table) {
            // Remove the decimal field
            $table->dropColumn('tiered_price');

            // Add back the JSON field
            $table->json('tiered_prices')->nullable()->after('get_quantity');
        });
    }
}
