<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PartyCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
                'email' => 'ahmed@example.com',
                'mobile' => '+966501234567',
                'password' => 'password123',
                'party_type' => 'customer',
                'status' => 1,
            ],
            [
                'first_name' => 'فاطمة',
                'last_name' => 'علي',
                'email' => 'fatima@example.com',
                'mobile' => '+966502345678',
                'password' => 'password123',
                'party_type' => 'customer',
                'status' => 1,
            ],
            [
                'first_name' => 'محمد',
                'last_name' => 'عبدالله',
                'email' => 'mohammed@example.com',
                'mobile' => '+966503456789',
                'password' => 'password123',
                'party_type' => 'customer',
                'status' => 1,
            ],
            [
                'first_name' => 'سارة',
                'last_name' => 'خالد',
                'email' => 'sara@example.com',
                'mobile' => '+966504567890',
                'password' => 'password123',
                'party_type' => 'customer',
                'status' => 1,
            ],
            [
                'first_name' => 'عمر',
                'last_name' => 'حسن',
                'email' => 'omar@example.com',
                'mobile' => '+966505678901',
                'password' => 'password123',
                'party_type' => 'customer',
                'status' => 1,
            ],
        ];

        foreach ($customers as $customer) {
            \App\Models\Party\Party::create($customer);
        }

        $this->command->info('Created ' . count($customers) . ' test customers');
    }
}
