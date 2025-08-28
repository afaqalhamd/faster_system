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

        User::create([
                                    'id' => 1,
                                    'username' => 'Administrator',
                                    'first_name' => 'Super',
                                    'last_name' => 'Admin',
                                    'email' => 'fasterxpress@gmail.com',
                                    'password' => Hash::make('Admin@123'),
                                    'status' => 1,
                                ]);
    }
}
