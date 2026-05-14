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
        Schema::table('pos_payments', function (Blueprint $table) {
            $table->decimal('total_user_payment', 15, 2)->default('0.00')->after('amount')->comment('Total amount paid by customer (may be more than POS total for change)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_payments', function (Blueprint $table) {
            $table->dropColumn('total_user_payment');
        });
    }
};
