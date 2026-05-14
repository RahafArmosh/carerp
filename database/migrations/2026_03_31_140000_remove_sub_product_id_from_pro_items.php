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
        if (!Schema::hasTable('pro_items') || !Schema::hasColumn('pro_items', 'sub_product_id')) {
            return;
        }

        Schema::table('pro_items', function (Blueprint $table) {
            try {
                $table->dropForeign(['sub_product_id']);
            } catch (\Throwable $th) {
                // Ignore if foreign key is missing or has a non-standard name.
            }
        });

        Schema::table('pro_items', function (Blueprint $table) {
            $table->dropColumn('sub_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('pro_items') || Schema::hasColumn('pro_items', 'sub_product_id')) {
            return;
        }

        Schema::table('pro_items', function (Blueprint $table) {
            $table->unsignedBigInteger('sub_product_id')->nullable()->after('product_id');
            $table->foreign('sub_product_id')->references('id')->on('sub_products')->onDelete('set null');
        });
    }
};
