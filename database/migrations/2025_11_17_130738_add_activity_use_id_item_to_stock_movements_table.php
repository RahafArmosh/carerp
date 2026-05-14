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
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->enum('activity', ['PURCHASE', 'SALES'])->nullable()->after('sub_product_id');
            $table->unsignedBigInteger('use_id')->nullable()->after('activity')->comment('customer_id for SALES, vender_id for PURCHASE');
            $table->unsignedBigInteger('item')->nullable()->after('use_id')->comment('sub_product_id reference');
            
            // Add foreign key for use_id to customers or venders (we'll handle this in application logic)
            // Note: use_id can reference either customers.id or venders.id depending on activity
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['activity', 'use_id', 'item']);
        });
    }
};
