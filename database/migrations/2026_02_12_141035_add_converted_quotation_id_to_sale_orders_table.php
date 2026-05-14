<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('converted_quotation_id')
                  ->nullable()
                  ->after('created_by');

            // optional foreign key
            $table->foreign('converted_quotation_id')
                  ->references('id')
                  ->on('quotations')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropForeign(['converted_quotation_id']);
            $table->dropColumn('converted_quotation_id');
        });
    }
};
