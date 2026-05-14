<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bill_products', function (Blueprint $table) {
            $table->index('bill_id'); // Adds index
        });
    }

    public function down()
    {
        Schema::table('bill_products', function (Blueprint $table) {
            $table->dropIndex(['bill_id']); // Rolls back index
        });
    }
};
