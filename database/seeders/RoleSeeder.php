<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role as SpatieRole;
use App\Models\User;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin role
        $adminRole = SpatieRole::firstOrCreate([
            'name' => 'Admin',
        ], [
            'guard_name' => 'web',
        ]);

        // Create Delivery role
        $deliveryRole = SpatieRole::firstOrCreate([
            'name' => 'Delivery',
        ], [
            'guard_name' => 'web',
        ]);

        $user = User::find(1);
        if ($user) {
            // Assign Admin role to user
            $user->assignRole($adminRole);
        }
    }
}
