<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ContactPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions with display names
        $permissions = [
            [
                'name' => 'contact.view',
                'display_name' => 'عرض رسائل التواصل',
                'guard_name' => 'web'
            ],
            [
                'name' => 'contact.edit',
                'display_name' => 'تعديل رسائل التواصل',
                'guard_name' => 'web'
            ],
            [
                'name' => 'contact.delete',
                'display_name' => 'حذف رسائل التواصل',
                'guard_name' => 'web'
            ],
        ];

        $permissionNames = [];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
            $permissionNames[] = $permission['name'];
        }

        // Assign permissions to admin role
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissionNames);
        }
    }
}
