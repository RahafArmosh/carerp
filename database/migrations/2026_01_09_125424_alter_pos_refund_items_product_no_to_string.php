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
        // Schema::table('pos_refund_items', function (Blueprint $table) {
        //     // Change product_no from TEXT to VARCHAR(255) to allow indexing
        //     $table->string('product_no', 255)->nullable()->change();
        // });
        
        // // Add index after altering the column
        // Schema::table('pos_refund_items', function (Blueprint $table) {
        //     $table->index('product_no');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('pos_refund_items', function (Blueprint $table) {
        //     // Drop index first
        //     $table->dropIndex(['product_no']);
            
        //     // Change back to TEXT (though this is usually not needed)
        //     $table->text('product_no')->nullable()->change();
        // });
    }
};
