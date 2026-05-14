<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'custom_fields', function (Blueprint $table){
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('type');
            $table->string('module');
            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')->references('id')->on('product_service_categories')->onDelete('cascade');
            $table->integer('created_by');
            $table->timestamps();
        }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_fields');
    }
}
