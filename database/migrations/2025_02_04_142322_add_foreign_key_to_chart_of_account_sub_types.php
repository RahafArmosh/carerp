<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Schema::table('chart_of_account_sub_types', function (Blueprint $table) {
        //     $table->foreign('type') // The column in 'chart_of_account_sub_types'
        //         ->references('id') // The referenced column in 'chart_of_account_types'
        //         ->on('chart_of_account_types')
        //         ->onDelete('cascade'); // Cascade delete
        // });
    }

    public function down()
    {
        Schema::table('chart_of_account_sub_types', function (Blueprint $table) {
            $table->dropForeign(['type']); // Drop the foreign key
        });
    }
};
