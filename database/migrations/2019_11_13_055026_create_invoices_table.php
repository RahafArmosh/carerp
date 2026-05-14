<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'invoices', function (Blueprint $table){
            $table->bigIncrements('id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('customer_id');
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('send_date')->nullable();
            $table->integer('category_id');
            $table->text('ref_number')->nullable();
            $table->integer('status')->comment('0 => draft,1=>send to Approve , 2 => approve , 4 => send , 6 => receive')->default(0);
            $table->integer('payment_status')->comment('0 => not paid, 2 => Partially paid , 4 => paid')->default('0');
            $table->integer('shipping_display')->default('1');
            $table->integer('discount_apply')->default('0');
            $table->integer('created_by')->default('0');
            $table->integer('salesman_id')->default('0');
            $table->string('tax_id','50')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained();
            $table->decimal('exchange_rate', 10, 2)->default(0);
            $table->string('type')->default('regular'); // Add this line for the 'type' column
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
        Schema::dropIfExists('invoices');
    }
}
