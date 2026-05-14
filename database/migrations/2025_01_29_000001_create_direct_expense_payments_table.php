<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direct_expense_payments', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->decimal('amount', 16, 2)->default('0.0');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('direct_expense_id');
            $table->unsignedBigInteger('vendor_id');
            $table->text('description')->nullable();
            $table->integer('payment_method')->default(0);
            $table->string('reference')->nullable();
            $table->string('add_receipt')->nullable();
            $table->integer('status')->comment('0 => draft, 2 => Received')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index('direct_expense_id');
            $table->index('vendor_id');
            $table->index('account_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_expense_payments');
    }
};

