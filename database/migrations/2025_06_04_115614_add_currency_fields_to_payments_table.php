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
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable()->after('amount');
            $table->decimal('currency_rate', 15, 6)->nullable()->after('currency_id');
            $table->decimal('amount_in_currency', 20, 6)->nullable()->after('currency_rate');

            // Optional: If you want to link to a currencies table
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop in reverse order
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'currency_rate', 'amount_in_currency']);
        });
    }
};
