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
        Schema::table('simple_expense_payments', function (Blueprint $table) {
            $table->integer('created_by')->default(0)->after('amount_in_currency');
            $table->integer('status')->default(0)->comment('0 => Draft, 2 => Paid')->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simple_expense_payments', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'status']);
        });
    }
};
