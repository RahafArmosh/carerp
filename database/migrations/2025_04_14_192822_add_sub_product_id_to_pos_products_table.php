<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pos_products', function (Blueprint $table) {
            $table->unsignedBigInteger('sub_product_id')->nullable()->after('product_id');

            // Optional: Add foreign key constraint if needed
            $table->foreign('sub_product_id')->references('id')->on('sub_products')->onDelete('set null');
        });
    }
    public function down()
    {
        Schema::table('pos_products', function (Blueprint $table) {
            $table->dropForeign(['sub_product_id']);
            $table->dropColumn('sub_product_id');
        });
    }
};
