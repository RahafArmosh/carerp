<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advance_sale_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('advance_sale_order_items', 'stock_qty')) {
                $table->dropColumn('stock_qty');
            }
            if (Schema::hasColumn('advance_sale_order_items', 'picking_qty')) {
                $table->dropColumn('picking_qty');
            }
            if (Schema::hasColumn('advance_sale_order_items', 'packed_qty')) {
                $table->dropColumn('packed_qty');
            }
            if (Schema::hasColumn('advance_sale_order_items', 'discrepancy')) {
                $table->dropColumn('discrepancy');
            }
            if (!Schema::hasColumn('advance_sale_order_items', 'converted_qty')) {
                $table->decimal('converted_qty', 16, 2)->default(0)->after('req_qty');
            }
        });

        Schema::table('advance_sale_orders', function (Blueprint $table) {
            if (Schema::hasColumn('advance_sale_orders', 'invoice_id')) {
                $table->dropColumn('invoice_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('advance_sale_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('advance_sale_orders', 'invoice_id')) {
                $table->unsignedBigInteger('invoice_id')->nullable()->after('status');
                $table->index('invoice_id');
            }
        });

        Schema::table('advance_sale_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('advance_sale_order_items', 'converted_qty')) {
                $table->dropColumn('converted_qty');
            }
            if (!Schema::hasColumn('advance_sale_order_items', 'stock_qty')) {
                $table->decimal('stock_qty', 16, 2)->default(0)->after('req_qty');
            }
            if (!Schema::hasColumn('advance_sale_order_items', 'picking_qty')) {
                $table->decimal('picking_qty', 16, 2)->default(0)->after('stock_qty');
            }
            if (!Schema::hasColumn('advance_sale_order_items', 'packed_qty')) {
                $table->decimal('packed_qty', 16, 2)->default(0)->after('picking_qty');
            }
            if (!Schema::hasColumn('advance_sale_order_items', 'discrepancy')) {
                $table->decimal('discrepancy', 16, 2)->default(0)->after('packed_qty');
            }
        });
    }
};

