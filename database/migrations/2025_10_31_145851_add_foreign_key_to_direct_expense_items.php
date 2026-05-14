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
        Schema::table('direct_expense_items', function (Blueprint $table) {
            $table->foreign('direct_expense_id')->references('id')->on('direct_expenses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('direct_expense_items', function (Blueprint $table) {
            $table->dropForeign(['direct_expense_id']);
        });
    }
};
