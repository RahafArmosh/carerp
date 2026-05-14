<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CustomField;
use App\Models\ProductServiceCategory;
class CustomFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the custom fields to be inserted
        $customFields = [
            [
                'name' => 'Gender',
                'type' => 'dropdown',
                'module' => 'sub-product',
                'created_by' => 2, // Assuming 1 is the ID of the user creating this field
                'category_id'=> ProductServiceCategory::where('name', 'Clothing')->first()->id,
                'options' => json_encode(["Men","Women"]),
            ],
            [
                'name' => 'color',
                'type' => 'dropdown',
                'module' => 'sub-product',
                'created_by' => 2, // Assuming 1 is the ID of the user creating this field
                'category_id'=> ProductServiceCategory::where('name', 'Clothing')->first()->id,
                'options' => json_encode(["White","Sand","Black","Forest Green","Light","Dark","Navy Blue","Green","Beige","Navy Blue","Blue","Cream"]),
            ],
            [
                'name' => 'size',
                'type' => 'dropdown',
                'module' => 'sub-product',
                'created_by' => 2, // Assuming 1 is the ID of the user creating this field
                'category_id'=> ProductServiceCategory::where('name', 'Clothing')->first()->id,
                'options' => json_encode(["XS","S","M","L","XL","XXL"]),
            ],
            [
                'name' => 'style',
                'type' => 'dropdown',
                'module' => 'sub-product',
                'created_by' => 2, // Assuming 1 is the ID of the user creating this field
                'category_id'=> ProductServiceCategory::where('name', 'Clothing')->first()->id,
                'options' => json_encode(["Tshirt","Jeans","Shorts","Jacket","Pants"]),
            ],
            [
                'name' => 'number size',
                'type' => 'text',
                'module' => 'sub-product',
                'created_by' => 2, // Assuming 1 is the ID of the user creating this field
                'category_id'=> ProductServiceCategory::where('name', 'Clothing')->first()->id,
                'options' => null,
            ],


            [
                'name' => 'chassis_no',
                'type' => 'text',
                'module' => 'sub-product',
                'created_by' => 2, // Assuming 1 is the ID of the user creating this field
                'category_id'=> ProductServiceCategory::where('name', 'Cars')->first()->id,
                'options' => null,
            ],
            [
                'name' => 'interior Color',
                'type' => 'dropdown',
                'module' => 'sub-product',
                'created_by' => 2, // Assuming 1 is the ID of the user creating this field
                'category_id'=> ProductServiceCategory::where('name', 'Cars')->first()->id,
                'options' => json_encode(["red","black","white"]),
            ],
            [
                'name' => 'exterior Color',
                'type' => 'dropdown',
                'module' => 'sub-product',
                'created_by' => 2, // Assuming 1 is the ID of the user creating this field
                'category_id'=> ProductServiceCategory::where('name', 'Cars')->first()->id,
                'options' => json_encode(["red","black","white"]),
            ],
            [
                'name' => 'width',
                'type' => 'number',
                'module' => 'sub-product',
                'created_by' => 2, // Assuming 1 is the ID of the user creating this field
                'category_id'=> ProductServiceCategory::where('name', 'Accessories')->first()->id,
                'options' => null,
            ],


            // Add more custom fields as needed
        ];

        // Insert custom fields into the database
        foreach ($customFields as $field) {
            CustomField::updateOrCreate(
                ['name' => $field['name'], 'module' => $field['module']],
                $field
            );
        }
    }
}
