<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Sub-products created/updated via "Import Item Master" (Spare Parts Stock Import) get import_source = 'item_master'.
     */
    public function up(): void
    {
        Schema::table('sub_products', function (Blueprint $table) {
            $table->string('import_source', 64)->nullable()->after('note')->comment('e.g. item_master for Spare Parts / Item Master import');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_products', function (Blueprint $table) {
            $table->dropColumn('import_source');
        });
    }
};
