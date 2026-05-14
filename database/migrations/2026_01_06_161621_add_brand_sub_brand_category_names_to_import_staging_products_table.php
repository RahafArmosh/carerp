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
        // Schema::table('import_staging_products', function (Blueprint $table) {
        //     $table->string('brand_name')->nullable()->after('product_name');
        //     $table->string('sub_brand_name')->nullable()->after('brand_name');
        //     $table->string('category_name')->nullable()->after('sub_brand_name');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('import_staging_products', function (Blueprint $table) {
        //     $table->dropColumn(['brand_name', 'sub_brand_name', 'category_name']);
        // });
    }
};
