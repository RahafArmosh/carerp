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
        Schema::create('task_masters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_predefined')->default(true);
            $table->unsignedBigInteger('created_by_employee_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('created_by')->default(0);
            $table->timestamps();

            $table->index(['created_by', 'is_active']);
            $table->index(['created_by', 'is_predefined']);
            $table->index('department_id');

            $table->foreign('created_by_employee_id')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_masters');
    }
};
