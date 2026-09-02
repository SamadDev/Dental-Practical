<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the permission catalogue and the four clinic roles, then makes
 * sure every existing user has the Spatie role matching its legacy
 * `role` column. Idempotent — safe on every deploy.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles/permissions (important after deploys).
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Patients
            'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
            // Queue
            'queue.view', 'queue.manage',
            // Visits & checkout
            'visits.view', 'visits.create', 'visits.edit', 'visits.checkout', 'visits.xray', 'visits.pay_debt',
            'archive.view',
            // Aqsat (clinic financing)
            'aqsat.view', 'aqsat.create', 'aqsat.edit',
            // Payment plans (patient financing)
            'payment_plans.view', 'payment_plans.create', 'payment_plans.edit', 'payment_plans.pay',
            // Expenses
            'expenses.view', 'expenses.create', 'expenses.delete',
            // Inventory
            'inventory.view', 'inventory.move', 'inventory.adjust',
            // Vendors & purchase orders
            'vendors.view', 'vendors.create', 'vendors.edit', 'vendors.po',
            // Finance
            'cash_flow.view', 'cash_flow.manage',
            'dashboard.view',
            // Administration
            'users.manage',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        Role::findOrCreate('admin', 'web')
            ->syncPermissions($permissions);

        Role::findOrCreate('doctor', 'web')->syncPermissions([
            'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
            'queue.view', 'queue.manage',
            'visits.view', 'visits.create', 'visits.edit', 'visits.checkout', 'visits.xray',
            'archive.view',
            'aqsat.view', 'aqsat.create', 'aqsat.edit',
            'payment_plans.view', 'payment_plans.create', 'payment_plans.edit',
            'inventory.view',
            'dashboard.view',
        ]);

        Role::findOrCreate('receptionist', 'web')->syncPermissions([
            'patients.view', 'patients.create', 'patients.edit',
            'queue.view', 'queue.manage',
            'visits.view', 'visits.create', 'visits.checkout',
            'archive.view',
            'aqsat.view', 'aqsat.create', 'aqsat.edit',
            'payment_plans.view', 'payment_plans.create', 'payment_plans.edit',
            'expenses.view', 'expenses.create', 'expenses.delete',
            'inventory.view', 'inventory.move',
            'dashboard.view',
        ]);

        Role::findOrCreate('hygienist', 'web')->syncPermissions([
            'patients.view',
            'queue.view',
            'visits.view', 'visits.create', 'visits.edit', 'visits.xray',
            'archive.view',
            'inventory.view',
        ]);

        // Every user gets the Spatie role matching its legacy column.
        User::query()->each(function (User $user) {
            if (! $user->roles->count() && $user->role) {
                $user->assignSyncRole($user->role);
            }
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}