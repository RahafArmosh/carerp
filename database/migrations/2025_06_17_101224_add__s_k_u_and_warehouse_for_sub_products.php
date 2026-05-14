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
        //sub_products
        Schema::table('sub_products', function (Blueprint $table) {
            $table->string('SP_sku')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
        });
    }
 
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_products', function (Blueprint $table) {
            //
        });
    }
    
 };