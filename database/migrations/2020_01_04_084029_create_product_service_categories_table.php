<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductServiceCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_service_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('type')->default(0);

            $table->integer('purchase_account_id')->default(0);
            $table->integer('sale_account_id')->default('0');
            $table->integer('expense_account_id')->default('0');
            $table->integer('rent_account_id')->default('0');

            $table->string('color')->default('#fc544b');
            $table->boolean('rentable')->default(true);
            $table->integer('created_by')->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_service_categories');
    }
}
