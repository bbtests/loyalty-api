<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Models for permissions
        $models = [
            'user',
            'loyalty point',
            'achievement',
            'user achievement',
            'badge',
            'user badge',
            'transaction',
            'cashback payment',
            'event',
        ];

        $actions = ['create', 'edit', 'delete', 'list', 'view'];

        $permissions = [];

        foreach ($models as $model) {
            foreach ($actions as $action) {
                $permission = "{$action} {$model}";
                Permission::firstOrCreate(['name' => $permission]);
                $permissions[] = $permission;
            }
        }

        $roles = [
            'super admin' => $permissions,
            'admin' => $permissions,
            'manager' => array_filter($permissions, function ($perm) {
                // Managers can't delete users or achievements
                if (str_starts_with($perm, 'delete user') || str_starts_with($perm, 'delete achievement')) {
                    return false;
                }

                return true;
            }),
            'user' => [
                'view user', 'edit user',
                'view loyalty point',
                'view achievement',
                'view badge',
                'view transaction',
                'view cashback payment',
                'view event',
            ],
        ];

        foreach ($roles as $role => $perms) {
            $roleModel = Role::firstOrCreate(['name' => $role]);
            $roleModel->syncPermissions($perms);
        }
    }
}
