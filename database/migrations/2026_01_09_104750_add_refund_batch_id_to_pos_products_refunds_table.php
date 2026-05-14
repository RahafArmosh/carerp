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
        Schema::table('pos_products_refunds', function (Blueprint $table) {
            $table->string('refund_batch_id')->nullable()->after('voucher_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_products_refunds', function (Blueprint $table) {
            $table->dropColumn('refund_batch_id');
        });
    }
};
