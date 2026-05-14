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
        if (Schema::hasTable('direct_expense_payments')) {
            Schema::table('direct_expense_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('direct_expense_payments', 'payment_date')) {
                    $table->date('payment_date')->nullable()->after('date');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('direct_expense_payments')) {
            Schema::table('direct_expense_payments', function (Blueprint $table) {
                if (Schema::hasColumn('direct_expense_payments', 'payment_date')) {
                    $table->dropColumn('payment_date');
                }
            });
        }
    }
};


