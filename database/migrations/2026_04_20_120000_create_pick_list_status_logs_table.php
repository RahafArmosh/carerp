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
        Schema::create('pick_list_status_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pick_list_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->timestamp('changed_at')->nullable();
            $table->unsignedBigInteger('created_by')->default(0);
            $table->timestamps();

            $table->foreign('pick_list_id')->references('id')->on('pick_lists')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->index('pick_list_id');
            $table->index('created_by');
            $table->index('changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pick_list_status_logs');
    }
};
