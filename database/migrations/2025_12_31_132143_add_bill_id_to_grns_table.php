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
        Schema::table('grns', function (Blueprint $table) {
            $table->unsignedBigInteger('bill_id')->nullable()->after('asn_id');
            $table->foreign('bill_id')->references('id')->on('bills')->onDelete('set null');
            $table->index('bill_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grns', function (Blueprint $table) {
            $table->dropForeign(['bill_id']);
            $table->dropIndex(['bill_id']);
            $table->dropColumn('bill_id');
        });
    }
};
