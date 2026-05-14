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
        Schema::table('proposal_products', function (Blueprint $table) {
            $table->decimal('exchange_price', 15, 4)->nullable()->after('price');
            $table->decimal('exchange_discount', 15, 4)->nullable()->after('discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposal_products', function (Blueprint $table) {
            $table->dropColumn(['exchange_price', 'exchange_discount']);
        });
    }
}; 