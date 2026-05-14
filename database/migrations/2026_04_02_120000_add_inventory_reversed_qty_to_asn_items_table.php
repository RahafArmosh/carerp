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
        if (!Schema::hasTable('asn_items')) {
            return;
        }

        Schema::table('asn_items', function (Blueprint $table) {
            if (!Schema::hasColumn('asn_items', 'inventory_reversed_qty')) {
                $table->decimal('inventory_reversed_qty', 15, 2)->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('asn_items')) {
            return;
        }

        Schema::table('asn_items', function (Blueprint $table) {
            if (Schema::hasColumn('asn_items', 'inventory_reversed_qty')) {
                $table->dropColumn('inventory_reversed_qty');
            }
        });
    }
};
