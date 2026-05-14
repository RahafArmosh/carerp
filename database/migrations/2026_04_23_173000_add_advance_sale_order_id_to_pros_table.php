<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pros', function (Blueprint $table) {
            if (!Schema::hasColumn('pros', 'advance_sale_order_id')) {
                $table->unsignedBigInteger('advance_sale_order_id')->nullable()->after('pro_no');
                $table->foreign('advance_sale_order_id')
                    ->references('id')
                    ->on('advance_sale_orders')
                    ->onDelete('set null');
                $table->index('advance_sale_order_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pros', function (Blueprint $table) {
            if (Schema::hasColumn('pros', 'advance_sale_order_id')) {
                $table->dropForeign(['advance_sale_order_id']);
                $table->dropIndex(['advance_sale_order_id']);
                $table->dropColumn('advance_sale_order_id');
            }
        });
    }
};

