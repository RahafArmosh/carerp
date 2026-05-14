<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->integer('bank_account_id')->nullable()->after('converted_invoice_id');
        });
    }

    public function down()
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn('bank_account_id');
        });
    }
};

