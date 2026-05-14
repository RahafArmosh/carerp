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
        Schema::table('product_service_categories', function (Blueprint $table) {
            $table->enum('cost_calculation_method', ['actual', 'avg'])->default('actual')->after('is_manufacturer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_service_categories', function (Blueprint $table) {
            $table->dropColumn('cost_calculation_method');
        });
    }
};
