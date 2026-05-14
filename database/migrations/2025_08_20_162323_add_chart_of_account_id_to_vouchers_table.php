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
        // Schema::table('vouchers', function (Blueprint $table) {
        //     // Step 1: Add the column as nullable first
        //     $table->foreignId('chart_of_account_id')
        //           ->nullable()
        //           ->after('id'); // adjust position if needed
        // });
    
        // Schema::table('vouchers', function (Blueprint $table) {
        //     // Step 2: Add the foreign key constraint separately
        //     $table->foreign('chart_of_account_id')
        //           ->references('id')
        //           ->on('chart_of_accounts')
        //           ->nullOnDelete(); // or cascadeOnDelete() depending on logic
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('vouchers', function (Blueprint $table) {
        //     $table->dropForeign(['chart_of_account_id']);
        //     $table->dropColumn('chart_of_account_id');
        // });
    }
};
