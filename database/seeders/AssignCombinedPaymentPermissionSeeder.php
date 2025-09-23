<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignCombinedPaymentPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the combined payment permission
        $combinedPaymentPermission = Permission::where('name', 'sale.combined.payment.in.view')->first();

        if ($combinedPaymentPermission) {
            // Get all roles that have the regular sale invoice view permission
            $roles = Role::whereHas('permissions', function($query) {
                $query->where('name', 'sale.invoice.view');
            })->get();

            // Assign the combined payment permission to these roles
            foreach ($roles as $role) {
                if (!$role->hasPermissionTo($combinedPaymentPermission)) {
                    $role->givePermissionTo($combinedPaymentPermission);
                    echo "Assigned 'sale.combined.payment.in.view' permission to '{$role->name}' role\n";
                }
            }

            // Also assign to roles that have sale order view permission
            $orderRoles = Role::whereHas('permissions', function($query) {
                $query->where('name', 'sale.order.view');
            })->get();

            foreach ($orderRoles as $role) {
                if (!$role->hasPermissionTo($combinedPaymentPermission)) {
                    $role->givePermissionTo($combinedPaymentPermission);
                    echo "Assigned 'sale.combined.payment.in.view' permission to '{$role->name}' role\n";
                }
            }
        } else {
            echo "Permission 'sale.combined.payment.in.view' not found\n";
        }
    }
}
