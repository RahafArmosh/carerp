<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bill_products', function (Blueprint $table) {
            $table->index('sub_product_id');
        });
    }

    public function down()
    {
        Schema::table('bill_products', function (Blueprint $table) {
            $table->dropIndex(['sub_product_id']);
        });
    }
};
