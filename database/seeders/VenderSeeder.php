<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Vender;
use Illuminate\Support\Facades\Hash;
class VenderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Create some sample venders
         Vender::create([
            'vender_id' => '1',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => Hash::make('password'), // Encrypt the password
            'contact' => '1234567890',
            'avatar' => '', // Replace with actual path
            'is_active' => true,
            'created_by' => 2, // Replace with a valid user ID
            'email_verified_at' => now(),
            'billing_name' => 'John Doe',
            'billing_country' => 'USA',
            'billing_state' => 'California',
            'billing_city' => 'Los Angeles',
            'billing_phone' => '1234567890',
            'billing_zip' => '90001',
            'billing_address' => '1234 Elm Street',
            'shipping_name' => 'John Doe',
            'shipping_country' => 'USA',
            'shipping_state' => 'California',
            'shipping_city' => 'Los Angeles',
            'shipping_phone' => '1234567890',
            'shipping_zip' => '90001',
            'shipping_address' => '1234 Elm Street',
            'chart_account_id' => 15, // Replace with a valid chart account ID
        ]);
    }
}
