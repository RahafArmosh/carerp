<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\ProductServiceUnit;
use App\Models\Tax;
use App\Models\Brand;
use App\Models\VehicleModel;

class ProductServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Assuming you have already seeded the related models
         $category = ProductServiceCategory::where('name', 'Cars')->first();
         $unit = ProductServiceUnit::where('name', 'UN')->first();
         $tax = Tax::where('name', 'VAT')->first();
         $brand = Brand::where('name', 'Toyota')->first();
         $subBrand = VehicleModel::where('name', 'prado')->first();

         // Create some sample product services
         ProductService::create([
             'name' => 'prado 1',
             'sku' => 'SGS21-001',
             'sale_price' => 100000.00,
             'sale_price_base' => 100000.00,
             'purchase_price' => 50000.00,
             'tax_id' => $tax->id,
             'category_id' => $category->id,
             'unit_id' => $unit->id,
             'type' => 'product',
             'created_by' => 2, // Replace with a valid user ID
             'brand_id' => $brand->id,
             'sub_brand_id' => $subBrand->id,
         ]);

         // Assuming you have already seeded the related models
         $category = ProductServiceCategory::where('name', 'Cars')->first();
         $unit = ProductServiceUnit::where('name', 'UN')->first();
         $tax = Tax::where('name', 'VAT')->first();
         $brand = Brand::where('name', 'JIMNY')->first();
         $subBrand = VehicleModel::where('name', 'Jimny')->first();

         // Create some sample product services
         ProductService::create([
             'name' => 'Jimny 1',
             'sku' => 'SGS21-002',
             'sale_price' => 6000000,
             'sale_price_base' => 6000000,
             'purchase_price' => 3000000,
             'tax_id' => $tax->id,
             'category_id' => $category->id,
             'unit_id' => $unit->id,
             'type' => 'product',
             'created_by' => 2, // Replace with a valid user ID
             'brand_id' => $brand->id,
             'sub_brand_id' => $subBrand->id,
         ]);

         // Assuming you have already seeded the related models
         $category = ProductServiceCategory::where('name', 'Accessories')->first();
         $unit = ProductServiceUnit::where('name', 'UN')->first();
         $tax = Tax::where('name', 'VAT')->first();
         $brand = Brand::where('name', 'Toyota')->first();
         $subBrand = VehicleModel::where('name', 'prado')->first();

         // Create some sample product services
         ProductService::create([
             'name' => 'Window',
             'sku' => 'SGS21-003',
             'sale_price' => 5000,
             'sale_price_base' => 5000,
             'purchase_price' => 2000,
             'tax_id' => $tax->id,
             'category_id' => $category->id,
             'unit_id' => $unit->id,
             'type' => 'product',
             'created_by' => 2, // Replace with a valid user ID
             'brand_id' => $brand->id,
             'sub_brand_id' => $subBrand->id,
         ]);
    }
}
