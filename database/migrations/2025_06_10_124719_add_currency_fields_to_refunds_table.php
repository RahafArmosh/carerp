<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable()->after('amount');
            $table->decimal('currency_rate', 10, 4)->nullable()->after('currency_id');
            $table->decimal('amount_in_currency', 15, 2)->nullable()->after('currency_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropColumn(['currency_id', 'currency_rate', 'amount_in_currency']);
        });
    }
};
