<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Brand;
use App\Models\ProductServiceCategory;
class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Create some sample brands
         $CarsCategory = ProductServiceCategory::where('name', 'Cars')->first();
         $AccessCategory = ProductServiceCategory::where('name', 'Accessories')->first();
         $ClothCategory = ProductServiceCategory::where('name', 'Clothing')->first();

         $brand1 = Brand::create([
             'name' => 'Toyota',
             'created_by' => 2
         ]);

         $brand2 = Brand::create([
             'name' => 'JIMNY',
             'created_by' => 2
         ]);

         $brand3 = Brand::create([
            'name' => 'Bardigiani',
            'created_by' => 2
        ]);

         // Attach categories to the brands
         $brand1->categories()->attach($CarsCategory->id);
         $brand1->categories()->attach($AccessCategory->id);
         $brand2->categories()->attach($CarsCategory->id);
         $brand2->categories()->attach($AccessCategory->id);
         $brand3->categories()->attach($ClothCategory->id);

    }
}
