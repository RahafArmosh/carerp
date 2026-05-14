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
        Schema::create('general_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vid'); // Define vid as an integer
            $table->unsignedBigInteger('account');
            $table->foreign('account')->references('id')->on('chart_of_accounts');
            $table->string('type');
            $table->decimal('debit', 10, 2)->default(0);
            $table->decimal('credit', 10, 2)->default(0);
            $table->unsignedBigInteger('ref_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('payment_id')->nullable();
            $table->integer('sub_product_id')->nullable();
            $table->integer('deleted_qty')->default('1');
            $table->integer('created_by')->default('0');
            $table->decimal('balance', 10, 2)->default(0);
            $table->date('send_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_ledger');
    }
};
