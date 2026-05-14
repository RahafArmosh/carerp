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
        Schema::table('pick_list_items', function (Blueprint $table) {
            $table->decimal('picked_qty', 16, 2)->default(0)->after('req_qty')->comment('Quantity picked by user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pick_list_items', function (Blueprint $table) {
            //
        });
    }
};
