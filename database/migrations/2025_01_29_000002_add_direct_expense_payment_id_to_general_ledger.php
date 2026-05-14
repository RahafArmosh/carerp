<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_ledger', function (Blueprint $table) {
            $table->unsignedBigInteger('direct_expense_payment_id')->nullable()->after('payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('general_ledger', function (Blueprint $table) {
            $table->dropColumn('direct_expense_payment_id');
        });
    }
};

