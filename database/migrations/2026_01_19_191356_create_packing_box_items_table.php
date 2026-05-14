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
        Schema::create('packing_box_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('packing_list_id');
            $table->string('box_no')->nullable();
            $table->string('part_no')->nullable();
            $table->text('description')->nullable();
            $table->decimal('qty', 16, 2)->default(0)->comment('Quantity packed in this box entry');
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('packing_list_id')->references('id')->on('packing_lists')->onDelete('cascade');
            
            // Indexes
            $table->index('packing_list_id');
            $table->index('box_no');
            $table->index('part_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_box_items');
    }
};
