<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Child rows represent qty split off to a new sub-product when partially converting an ASN line to bill.
     * They are excluded from ASN header totals (see Asn model helpers).
     */
    public function up(): void
    {
        Schema::table('asn_items', function (Blueprint $table) {
            $table->unsignedBigInteger('split_from_asn_item_id')->nullable()->after('asn_id');
            $table->foreign('split_from_asn_item_id')
                ->references('id')
                ->on('asn_items')
                ->nullOnDelete();
            $table->index('split_from_asn_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asn_items', function (Blueprint $table) {
            $table->dropForeign(['split_from_asn_item_id']);
            $table->dropIndex(['split_from_asn_item_id']);
            $table->dropColumn('split_from_asn_item_id');
        });
    }
};
