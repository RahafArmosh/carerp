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
        Schema::table('bank_transfers', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable()->after('amount');
            $table->decimal('currency_rate', 15, 8)->nullable()->after('currency_id');

            // Add foreign key constraint
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_transfers', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'currency_rate']);
        });
    }
};
