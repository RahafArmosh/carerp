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
        Schema::create('employee_daily_log_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_daily_log_id');
            $table->unsignedBigInteger('task_master_id')->nullable();
            $table->string('task_name');
            $table->unsignedSmallInteger('hours')->default(0);
            $table->unsignedTinyInteger('minutes')->default(0);
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->integer('created_by')->default(0);
            $table->timestamps();

            $table->index(['created_by', 'task_master_id']);
            $table->index('employee_daily_log_id');

            $table->foreign('employee_daily_log_id')
                ->references('id')
                ->on('employee_daily_logs')
                ->cascadeOnDelete();

            $table->foreign('task_master_id')
                ->references('id')
                ->on('task_masters')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_daily_log_tasks');
    }
};
