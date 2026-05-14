<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('product_services', 'rent_price')) {
            Schema::table('product_services', function (Blueprint $table) {
                $table->dropColumn('rent_price');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('product_services', 'rent_price')) {
            Schema::table('product_services', function (Blueprint $table) {
                $table->decimal('rent_price', 16, 2)->default(0)->after('purchase_price');
            });
        }
    }
};
