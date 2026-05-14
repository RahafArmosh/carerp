<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quotation_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('parent_id')->nullable();

            $table->foreignId('product_service_id')
                ->constrained('product_services')
                ->cascadeOnDelete();

            $table->string('description')->nullable();
            $table->integer('re_quantity')->default(0);
            $table->integer('av_quantity')->default(0);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 12, 2);

            $table->boolean('is_alternative')->default(false);
            $table->boolean('is_selected')->default(true);

            $table->enum('form_state', ['new', 'edited', 'saved'])->default('new');
            $table->unsignedBigInteger('updated_by')->nullable();


            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')
                ->on('quotation_items')
                ->cascadeOnDelete();
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
