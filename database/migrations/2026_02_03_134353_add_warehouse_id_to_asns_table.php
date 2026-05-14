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
        Schema::table('asns', function (Blueprint $table) {
            if (!Schema::hasColumn('asns', 'warehouse_id')) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('asn_date');
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
                $table->index('warehouse_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asns', function (Blueprint $table) {
            if (Schema::hasColumn('asns', 'warehouse_id')) {
                $table->dropForeign(['warehouse_id']);
                $table->dropIndex(['warehouse_id']);
                $table->dropColumn('warehouse_id');
            }
        });
    }
};
