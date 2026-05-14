<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_stock_count_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id')->nullable()->index();
            $table->string('source_filename', 255);
            $table->string('import_mode', 16)->default('single');
            $table->string('status', 32)->default('recorded');
            $table->string('job_token', 64)->nullable()->index();
            $table->unsignedInteger('line_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouse_stock_count_import_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_stock_count_import_id');
            $table->foreign('warehouse_stock_count_import_id', 'wsci_lines_parent_fk')
                ->references('id')
                ->on('warehouse_stock_count_imports')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->string('product_no', 191);
            $table->unsignedBigInteger('sub_product_id')->nullable()->index();
            $table->integer('counted_qty');
            $table->integer('system_qty_before')->nullable();
            $table->unsignedInteger('excel_row')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_stock_count_import_lines');
        Schema::dropIfExists('warehouse_stock_count_imports');
    }
};
