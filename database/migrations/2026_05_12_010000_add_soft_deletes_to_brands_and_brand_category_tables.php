<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (! Schema::hasColumn('brands', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('brand_category', function (Blueprint $table) {
            if (! Schema::hasColumn('brand_category', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (Schema::hasColumn('brands', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('brand_category', function (Blueprint $table) {
            if (Schema::hasColumn('brand_category', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
