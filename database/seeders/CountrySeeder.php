<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run()
    {
        $countries = [
            ['name' => 'United States'],
            ['name' => 'United Kingdom'],
            ['name' => 'Canada'],
            // Add more countries as needed
        ];

        foreach ($countries as $country) {
            Country::withoutGlobalScopes()->create(array_merge($country, ['created_by' => 1]));
        }
    }
}
