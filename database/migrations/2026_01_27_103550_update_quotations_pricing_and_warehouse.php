<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {

            // 1️⃣ Drop old columns
            if (Schema::hasColumn('quotations', 'pick_from_location')) {
                $table->dropColumn('pick_from_location');
            }

            if (Schema::hasColumn('quotations', 'price_group')) {
                $table->dropColumn('price_group');
            }

            // 2️⃣ Add warehouse_id (replacing pick_from_location)
            if (!Schema::hasColumn('quotations', 'warehouse_id')) {
                $table->foreignId('warehouse_id')
                    ->nullable()
                    ->constrained('warehouses')
                    ->nullOnDelete();
            }

            // 3️⃣ Add price_group as FK to pricing_list_types
            $table->foreignId('price_group')
                ->nullable()
                ->after('warehouse_id')
                ->constrained('pricing_list_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {

            // Drop new FKs
            if (Schema::hasColumn('quotations', 'warehouse_id')) {
                $table->dropForeign(['warehouse_id']);
                $table->dropColumn('warehouse_id');
            }

            if (Schema::hasColumn('quotations', 'price_group')) {
                $table->dropForeign(['price_group']);
                $table->dropColumn('price_group');
            }

            // Restore old columns
            $table->enum('pick_from_location', ['ML', 'FZ'])
                ->nullable()
                ->after('delivery_location');

            $table->unsignedBigInteger('price_group')
                ->nullable()
                ->after('pick_from_location');
        });
    }
};
