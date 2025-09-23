<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignSaleOrderPaymentPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the sale order payment view permission
        $saleOrderPaymentPermission = Permission::where('name', 'sale.order.payment.view')->first();

        if ($saleOrderPaymentPermission) {
            // Get all roles that have the regular sale order view permission
            $roles = Role::whereHas('permissions', function($query) {
                $query->where('name', 'sale.order.view');
            })->get();

            // Assign the sale order payment permission to these roles
            foreach ($roles as $role) {
                if (!$role->hasPermissionTo($saleOrderPaymentPermission)) {
                    $role->givePermissionTo($saleOrderPaymentPermission);
                    echo "Assigned 'sale.order.payment.view' permission to '{$role->name}' role\n";
                }
            }
        } else {
            echo "Permission 'sale.order.payment.view' not found\n";
        }
    }
}
