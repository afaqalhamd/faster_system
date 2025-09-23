<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UpdatePaymentPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:update-payment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update payment permissions to use the new sale.payment.in.view permission';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get the new permission
        $newPermission = Permission::where('name', 'sale.payment.in.view')->first();

        if (!$newPermission) {
            $this->error('New permission sale.payment.in.view not found!');
            return 1;
        }

        // Get all roles that have the old permission
        $rolesWithOldPermission = Role::whereHas('permissions', function ($query) {
            $query->where('name', 'sale.invoice.view');
        })->get();

        $this->info("Found " . $rolesWithOldPermission->count() . " roles with sale.invoice.view permission");

        // Assign the new permission to these roles
        foreach ($rolesWithOldPermission as $role) {
            $role->givePermissionTo($newPermission);
            $this->info("Assigned sale.payment.in.view permission to role: " . $role->name);
        }

        $this->info('Permission update completed successfully!');
        return 0;
    }
}
