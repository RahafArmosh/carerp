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
       Schema::create('pricing_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pricing_list_type_id');
            $table->unsignedBigInteger('product_service_id');
            $table->unsignedBigInteger('warehouse_id');

            $table->decimal('current_price', 15, 4);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            
            $table->unique(
                ['pricing_list_type_id', 'product_service_id', 'warehouse_id'],
                'pricing_lists_unique_key'
            );


            $table->foreign('pricing_list_type_id')->references('id')->on('pricing_list_types');
            $table->foreign('product_service_id')->references('id')->on('product_services');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_lists');
    }
};
