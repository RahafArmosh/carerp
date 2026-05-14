<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some sample customers
        Customer::create([
            'customer_id' => '1',
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            // 'password' => Hash::make('password'), // Encrypt the password
            'contact' => '0987654321',
            'avatar' => 'path/to/avatar.jpg', // Replace with actual path
            'is_active' => true,
            'created_by' => 2, // Replace with a valid user ID
            'email_verified_at' => now(),
            'billing_name' => 'Jane Smith',
            'billing_country' => 'USA',
            'billing_state' => 'New York',
            'billing_city' => 'New York City',
            'billing_phone' => '0987654321',
            'billing_zip' => '10001',
            'billing_address' => '4321 Oak Street',
            'shipping_name' => 'Jane Smith',
            'shipping_country' => 'USA',
            'shipping_state' => 'New York',
            'shipping_city' => 'New York City',
            'shipping_phone' => '0987654321',
            'shipping_zip' => '10001',
            'shipping_address' => '4321 Oak Street',
            'chart_account_id' => 3, // Replace with a valid chart account ID
            'document' => '', // Replace with actual path
        ]);
    }
}
