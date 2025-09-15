<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Use firstOrCreate to avoid duplicate entry errors
        User::firstOrCreate([
            'id' => 1
        ], [
            'username' => 'Administrator',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'ad.fasterexpress@gmail.com',
            'password' => Hash::make('fasterexp25'),
            'status' => 1,
        ]);
    }
}
