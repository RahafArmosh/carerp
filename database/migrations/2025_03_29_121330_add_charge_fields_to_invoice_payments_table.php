<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->decimal('charge', 16, 2)->default(0.00)->after('amount');
            $table->integer('bank_charge_account_id')->nullable()->after('charge');
        });
    }

    public function down()
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->dropColumn(['charge', 'bank_charge_account_id']);
        });
    }
};
