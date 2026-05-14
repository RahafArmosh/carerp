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
        Schema::table('sub_products', function (Blueprint $table) {
            $table->unsignedBigInteger('asn_id')->nullable()->after('bill_id');
            $table->foreign('asn_id')->references('id')->on('asns')->onDelete('set null');
            $table->index('asn_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_products', function (Blueprint $table) {
            $table->dropForeign(['asn_id']);
            $table->dropIndex(['asn_id']);
            $table->dropColumn('asn_id');
        });
    }
};
