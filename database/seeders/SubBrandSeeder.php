<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\VehicleModel;
use App\Models\Brand;

class SubBrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Assuming you have already seeded brands
         $toyotaBrand = Brand::where('name', 'Toyota')->first();
         $jimnyBrand = Brand::where('name', 'JIMNY')->first();
         $BardigianiBrand = Brand::where('name', 'Bardigiani')->first();

         // Create some sample sub-brands
         VehicleModel::create([
             'name' => 'prado',
             'brand_id' => $toyotaBrand->id,
             'created_by'=>2
         ]);

         VehicleModel::create([
             'name' => 'jimny',
             'brand_id' => $jimnyBrand->id,
             'created_by'=>2
         ]);
         VehicleModel::create([
            'name' => 'Tshirt',
            'brand_id' => $BardigianiBrand->id,
            'created_by'=>2
        ]);
        VehicleModel::create([
            'name' => 'Jeans',
            'brand_id' => $BardigianiBrand->id,
            'created_by'=>2
        ]);
        VehicleModel::create([
            'name' => 'Shorts',
            'brand_id' => $BardigianiBrand->id,
            'created_by'=>2
        ]);
        VehicleModel::create([
            'name' => 'Jacket',
            'brand_id' => $BardigianiBrand->id,
            'created_by'=>2
        ]);
        VehicleModel::create([
            'name' => 'Pants',
            'brand_id' => $BardigianiBrand->id,
            'created_by'=>2
        ]);

    }
}
