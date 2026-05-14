<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Create temporary table with new structure
        Schema::create('direct_expenses_new', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->default('0');
            $table->unsignedBigInteger('vendor_id');
            $table->string('tax_id', 50)->nullable();
            $table->foreignId('currency_id')->nullable()->constrained();
            $table->decimal('exchange_rate', 10, 2)->default(0);
            $table->integer('payment_status')->comment('0 => not paid, 2 => Partially paid , 4 => paid')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('expense_number');
            $table->index('created_by');
        });

        // Step 2: Migrate existing data if table exists
        if (Schema::hasTable('direct_expenses') && Schema::hasColumn('direct_expenses', 'sub_product_id')) {
            $existingExpenses = DB::table('direct_expenses')->get();
            
            // Group by vendor, created_by, and created_at to create headers
            $grouped = $existingExpenses->groupBy(function ($item) {
                return $item->vendor_id . '-' . $item->created_by . '-' . substr($item->created_at, 0, 10);
            });

            foreach ($grouped as $group => $items) {
                $firstItem = $items->first();
                $isPaid = isset($firstItem->is_paid) && $firstItem->is_paid ? 4 : 0;
                
                $headerId = DB::table('direct_expenses_new')->insertGetId([
                    'vendor_id' => $firstItem->vendor_id,
                    'expense_number' => 'EXP-' . $firstItem->id,
                    'tax_id' => null,
                    'currency_id' => null,
                    'exchange_rate' => 0,
                    'payment_status' => $isPaid,
                    'created_by' => $firstItem->created_by,
                    'created_at' => $firstItem->created_at,
                    'updated_at' => $firstItem->updated_at,
                ]);

                // Migrate items to items table (will be created in next migration)
                foreach ($items as $item) {
                    if (Schema::hasTable('direct_expense_items')) {
                        DB::table('direct_expense_items')->insert([
                            'direct_expense_id' => $headerId,
                            'sub_product_id' => $item->sub_product_id,
                            'amount' => $item->amount,
                            'description' => $item->description,
                            'chart_account_id' => $item->chart_account_id,
                            'created_at' => $item->created_at,
                            'updated_at' => $item->updated_at,
                        ]);
                    }
                }
            }

            // Step 3: Drop old table
            Schema::dropIfExists('direct_expenses');
        }

        // Step 4: Rename new table
        Schema::rename('direct_expenses_new', 'direct_expenses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert: Create old structure
        Schema::create('direct_expenses_old', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('sub_product_id');
            $table->decimal('amount', 18, 4);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('chart_account_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->boolean('is_paid')->default(false);
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('sub_product_id');
            $table->index('chart_account_id');
            $table->index('created_by');
        });

        // Migrate data back if items table exists
        if (Schema::hasTable('direct_expenses') && Schema::hasTable('direct_expense_items')) {
            $headers = DB::table('direct_expenses')->get();
            foreach ($headers as $header) {
                $items = DB::table('direct_expense_items')->where('direct_expense_id', $header->id)->get();
                foreach ($items as $item) {
                    DB::table('direct_expenses_old')->insert([
                        'vendor_id' => $header->vendor_id,
                        'sub_product_id' => $item->sub_product_id,
                        'amount' => $item->amount,
                        'description' => $item->description,
                        'chart_account_id' => $item->chart_account_id,
                        'created_by' => $header->created_by,
                        'is_paid' => $header->payment_status == 4,
                        'created_at' => $header->created_at ?? now(),
                        'updated_at' => $header->updated_at ?? now(),
                    ]);
                }
            }
        }

        Schema::dropIfExists('direct_expenses');
        Schema::rename('direct_expenses_old', 'direct_expenses');
    }
};
