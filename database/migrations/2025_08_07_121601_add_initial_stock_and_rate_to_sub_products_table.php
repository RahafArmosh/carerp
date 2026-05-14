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
        Schema::table('sub_products', function (Blueprint $table) {
            $table->decimal('initial_stock', 15, 2)->default(0)->after('quantity'); // or any other column
            $table->decimal('initial_rate', 15, 2)->default(0)->after('initial_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_products', function (Blueprint $table) {
            $table->dropColumn(['initial_stock', 'initial_rate']);
        });
    }
};
