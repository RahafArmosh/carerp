<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Define an array of currencies with their details
        $currencies = [
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'Dhs', 'exchange_rate' => 3.67],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 3.67],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 3.67],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'exchange_rate' => 5.00],
        ];

        // Insert the currencies into the database
        foreach ($currencies as $currency) {
            Currency::withoutGlobalScopes()->updateOrCreate(
                ['created_by' => 0, 'code' => $currency['code']],
                array_merge($currency, ['created_by' => 0])
            );
        }
    }
}
