<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductServiceUnit;

class ProductServiceUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductServiceUnit::create([
            'name' => 'UN',
            'created_by' => 2, // Replace with a valid user ID
        ]);
    }
}
