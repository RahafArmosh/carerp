<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing data to descriptive text
        DB::table('stock_movements')
            ->where('activity', 'PURCHASE')
            ->update(['activity' => 'Purchase from Bill']);
        
        DB::table('stock_movements')
            ->where('activity', 'SALES')
            ->update(['activity' => 'Sale via Invoice']);

        // Change the column from enum to string
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('activity', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert data back to enum values
        DB::table('stock_movements')
            ->where('activity', 'like', '%Purchase%')
            ->orWhere('activity', 'like', '%Profit%')
            ->update(['activity' => 'PURCHASE']);
        
        DB::table('stock_movements')
            ->where('activity', 'like', '%Sale%')
            ->orWhere('activity', 'like', '%Loss%')
            ->orWhere('activity', 'like', '%Return%')
            ->update(['activity' => 'SALES']);

        // Change back to enum
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->enum('activity', ['PURCHASE', 'SALES'])->nullable()->change();
        });
    }
};
