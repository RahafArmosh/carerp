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
        Schema::create('pick_list_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pick_list_id');
            $table->string('bin_location')->nullable();
            $table->string('part_no')->nullable();
            $table->text('description')->nullable();
            $table->decimal('req_qty', 16, 2)->default(0)->comment('Required Quantity');
            $table->boolean('tick')->default(false)->comment('Tick/Checkbox to mark as picked');
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('pick_list_id')->references('id')->on('pick_lists')->onDelete('cascade');
            
            // Indexes
            $table->index('pick_list_id');
            $table->index('part_no');
            $table->index('bin_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pick_list_items');
    }
};
