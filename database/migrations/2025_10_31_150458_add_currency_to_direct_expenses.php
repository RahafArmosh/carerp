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
        // Schema::table('direct_expenses', function (Blueprint $table) {
        //     $table->foreignId('currency_id')->nullable()->after('tax_id')->constrained();
        //     $table->decimal('exchange_rate', 10, 2)->default(0)->after('currency_id');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('direct_expenses', function (Blueprint $table) {
        //     $table->dropForeign(['currency_id']);
        //     $table->dropColumn(['currency_id', 'exchange_rate']);
        // });
    }
};
