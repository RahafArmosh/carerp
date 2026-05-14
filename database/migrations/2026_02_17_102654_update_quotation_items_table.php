<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropForeign(['product_service_id']);
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->foreignId('product_service_id')
                ->nullable()
                ->change();
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->foreign('product_service_id')
                ->references('id')
                ->on('product_services')
                ->nullOnDelete();
        });

        DB::statement("
            ALTER TABLE quotation_items 
            MODIFY form_state ENUM(
                'increased',
                'decreased',
                'new',
                'canceled',
                'out of system'
            ) DEFAULT 'new'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE quotation_items 
            MODIFY form_state ENUM('new','edited','saved') DEFAULT 'new'
        ");
    }
};
