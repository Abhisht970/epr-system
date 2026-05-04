<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Vendor module
            'vendors.view', 'vendors.create', 'vendors.update', 'vendors.verify',
            'vendors.approve', 'vendors.reject', 'vendors.suspend',
            // Trainer module
            'trainers.view', 'trainers.create', 'trainers.update',
            'trainers.verify', 'trainers.assign',
            // Candidate module
            'candidates.view', 'candidates.create', 'candidates.update',
            'candidates.verify', 'candidates.transition_status',
            // Scheme module
            'schemes.view', 'schemes.create', 'schemes.update', 'schemes.close',
            // Finance / invoices / payments
            'invoices.view', 'invoices.create', 'invoices.submit',
            'invoices.approve', 'invoices.reject', 'invoices.pay',
            // Audit
            'audit.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $rolePerms = [
            User::ROLE_ADMIN => $permissions,
            User::ROLE_VENDOR => [
                'vendors.view', 'vendors.update',
                'candidates.view', 'candidates.create', 'candidates.update',
                'invoices.view', 'invoices.create', 'invoices.submit',
                'schemes.view',
            ],
            User::ROLE_TRAINER => [
                'trainers.view', 'trainers.update',
                'candidates.view', 'schemes.view',
            ],
            User::ROLE_CANDIDATE => [
                'candidates.view',
            ],
            User::ROLE_FINANCE => [
                'invoices.view', 'invoices.approve', 'invoices.reject', 'invoices.pay',
                'audit.view',
            ],
            User::ROLE_INSPECTOR => [
                'vendors.view', 'vendors.verify',
            ],
        ];

        foreach ($rolePerms as $roleName => $perms) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($perms);
        }
    }
}
