<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direct_expense_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable()->after('amount');
            $table->decimal('currency_rate', 15, 6)->nullable()->after('currency_id');
        });
    }

    public function down(): void
    {
        Schema::table('direct_expense_payments', function (Blueprint $table) {
            $table->dropColumn(['currency_id', 'currency_rate']);
        });
    }
};


