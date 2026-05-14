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
       Schema::create('alt_part_numbers', function (Blueprint $table) {
            $table->id();

            $table->string('part_number');
            $table->string('alternative_part_number');

            $table->unsignedInteger('priority')->default(1);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->unique(['part_number', 'alternative_part_number']);

            $table->index(['part_number', 'is_active']);
            $table->index('alternative_part_number');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alt_part_numbers');
    }
};
