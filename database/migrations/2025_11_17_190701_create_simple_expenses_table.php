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
        Schema::create('simple_expenses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('expense_id')->default('0');
            $table->integer('vender_id');
            $table->date('expense_date');
            $table->date('due_date');
            $table->integer('status')->comment('0 => draft, 1 => send to Approve, 2 => approve , 4 => send , 6 => receive')->default(0);
            $table->integer('payment_status')->comment('0 => not paid, 2 => Partially paid , 4 => paid')->default('0');
            $table->string('type')->default('Expense');
            $table->string('user_type')->default('vendor');
            $table->date('send_date')->nullable();
            $table->string('tax_id', '50')->nullable();
            $table->integer('category_id');
            $table->foreignId('currency_id')->nullable()->constrained();
            $table->decimal('exchange_rate', 10, 2)->default(1);
            $table->integer('created_by')->default('0');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simple_expenses');
    }
};
