<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductServiceCategory;

class ProductServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example seeding data
        ProductServiceCategory::create([
            'name' => 'Cars',
            'type' => 'product', // Example type: product or service
            'created_by' => 2, // Replace with a valid user ID
            'sale_account_id' => 50, // Replace with a valid Sale Account ID
            'expense_account_id' => 59, // Replace with a valid Expense Account ID
            'purchase_account_id' => 5, // Replace with a valid Purchase Account ID
        ]);

        ProductServiceCategory::create([
            'name' => 'Accessories',
            'type' => 'Qty product',
            'created_by' => 2, // Replace with a valid user ID
            'sale_account_id' => 50, // Replace with a valid Sale Account ID
            'expense_account_id' => 59, // Replace with a valid Expense Account ID
            'purchase_account_id' => 5, // Replace with a valid Purchase Account ID
        ]);
        ProductServiceCategory::create([
            'name' => 'Clothing',
            'type' => 'Qty product',
            'created_by' => 2, // Replace with a valid user ID
            'sale_account_id' => 50, // Replace with a valid Sale Account ID
            'expense_account_id' => 59, // Replace with a valid Expense Account ID
            'purchase_account_id' => 5, // Replace with a valid Purchase Account ID
        ]);
    }
}
