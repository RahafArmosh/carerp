<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tax;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example seeding data
        Tax::create([
            'name' => 'VAT',
            'rate' => 5.0,
            'created_by' => 2, // Replace with a valid user ID
            'chart_account_id' => 27, // Replace with a valid Chart of Account ID
        ]);

        Tax::create([
            'name' => 'Service Tax',
            'rate' => 10.0,
            'created_by' => 2,
            'chart_account_id' => 27,
        ]);
    }
}
