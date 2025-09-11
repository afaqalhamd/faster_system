<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Carrier;
use App\Models\User;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CarrierUserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create additional carrier-specific permissions
        $carrierPermissions = [
            [
                'name' => 'carrier.sales.view',
                'display_name' => 'View Carrier Sales',
                'group' => 'Delivery'
            ],
            [
                'name' => 'carrier.orders.view',
                'display_name' => 'View Carrier Orders',
                'group' => 'Delivery'
            ],
            [
                'name' => 'carrier.status.update',
                'display_name' => 'Update Delivery Status',
                'group' => 'Delivery'
            ]
        ];

        // Get or create delivery permission group
        $deliveryGroup = \App\Models\PermissionGroup::firstOrCreate(['name' => 'Delivery']);

        // Create permissions
        foreach ($carrierPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission['name']
            ], [
                'display_name' => $permission['display_name'],
                'permission_group_id' => $deliveryGroup->id,
                'status' => 1
            ]);
        }

        // Get the Delivery role
        $deliveryRole = Role::where('name', 'Delivery')->first();

        if (!$deliveryRole) {
            $deliveryRole = Role::create([
                'name' => 'Delivery',
                'display_name' => 'Delivery Personnel',
                'status' => 1
            ]);
        }

        // Assign permissions to delivery role
        $permissionNames = array_column($carrierPermissions, 'name');
        $deliveryRole->givePermissionTo($permissionNames);

        // Create users for each active carrier
        $carriers = Carrier::where('status', 1)->get();

        foreach ($carriers as $carrier) {
            // Check if a user already exists for this carrier
            $existingUser = User::where('carrier_id', $carrier->id)->first();

            if (!$existingUser) {
                $user = User::create([
                    'first_name' => $carrier->name,
                    'last_name' => 'Delivery',
                    'username' => 'delivery_' . Str::slug($carrier->name),
                    'email' => $carrier->email ?: 'delivery_' . Str::slug($carrier->name) . '@company.com',
                    'password' => Hash::make('delivery123'), // Default password
                    'role_id' => $deliveryRole->id,
                    'carrier_id' => $carrier->id,
                    'status' => 1,
                    'is_allowed_all_warehouses' => 0,
                ]);

                // Assign the delivery role to the user
                $user->assignRole($deliveryRole);

                echo "Created user: {$user->username} for carrier: {$carrier->name}\n";
            }
        }
    }
}
