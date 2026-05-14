<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 4)->change();
            $table->decimal('total_price', 17, 4)->change();
        });
    }
    
    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->change();
            $table->decimal('total_price', 12, 2)->change();
        });
    }
};
