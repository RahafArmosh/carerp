<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'bills', function (Blueprint $table){
            $table->bigIncrements('id');
            $table->string('bill_id')->default('0');
            $table->integer('vender_id');
            $table->date('bill_date');
            $table->date('due_date');
            $table->integer('order_number')->default('0');
            $table->integer('status')->comment('0 => draft, 1 => send to Approve, 2 => approve , 4 => send , 6 => receive')->default(0);
            $table->integer('payment_status')->comment('0 => not paid, 2 => Partially paid , 4 => paid')->default('0');
            $table->string('type')->nullable();
            $table->string('user_type')->nullable();
            $table->integer('shipping_display')->default('1');
            $table->date('send_date')->nullable();
            $table->integer('discount_apply')->default('0');
            $table->string('tax_id','50')->nullable();
            $table->integer('category_id');
            $table->integer('warehouse_id')->default(0);
            $table->foreignId('currency_id')->nullable()->constrained();
            $table->decimal('exchange_rate', 10, 2)->default(0);
            $table->integer('created_by')->default('0');
            $table->integer('salesman_id')->default('0');
            $table->timestamps();
            $table->softDeletes();
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
        Schema::dropIfExists('bills');
    }
}
