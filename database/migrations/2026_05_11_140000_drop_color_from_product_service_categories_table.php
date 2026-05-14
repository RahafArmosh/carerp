<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_service_categories', function (Blueprint $table) {
            if (Schema::hasColumn('product_service_categories', 'color')) {
                $table->dropColumn('color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_service_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('product_service_categories', 'color')) {
                $table->string('color')->default('#fc544b');
            }
        });
    }
};
