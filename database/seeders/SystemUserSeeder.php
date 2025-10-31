<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SystemUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if system user already exists
        $systemUser = User::find(1);

        if (!$systemUser) {
            // Create system user with ID = 1
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            User::create([
                'id' => 1,
                'name' => 'System',
                'username' => 'system',
                'email' => 'system@example.com',
                'password' => Hash::make('system@123456'), // Change this password
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->command->info('✅ System user created successfully with ID = 1');
        } else {
            $this->command->info('ℹ️  System user already exists');
        }
    }
}
