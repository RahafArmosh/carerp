<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asn_items', function (Blueprint $table) {
            if (!Schema::hasColumn('asn_items', 'inventory_converted_at')) {
                $table->timestamp('inventory_converted_at')->nullable()->after('sub_product_id');
                $table->index('inventory_converted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('asn_items', function (Blueprint $table) {
            if (Schema::hasColumn('asn_items', 'inventory_converted_at')) {
                $table->dropIndex(['inventory_converted_at']);
                $table->dropColumn('inventory_converted_at');
            }
        });
    }
};

