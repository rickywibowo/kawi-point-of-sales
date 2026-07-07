<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'manage business',
        'manage outlet',
        'manage product',
        'manage inventory',
        'manage sales',
        'view report',
        'manage expense',
        'manage user',
    ];

    private const ROLE_PERMISSIONS = [
        'owner' => self::PERMISSIONS,
        'admin' => self::PERMISSIONS,
        'cashier' => [
            'manage sales',
            'view report',
        ],
        'warehouse' => [
            'manage inventory',
            'manage product',
        ],
        'accounting' => [
            'view report',
            'manage expense',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(self::PERMISSIONS)->mapWithKeys(
            fn (string $name) => [
                $name => Permission::query()->updateOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['description' => str($name)->headline()->toString()],
                ),
            ],
        );

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissionNames) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $roleName, 'guard_name' => 'web', 'business_id' => null, 'branch_id' => null],
                ['name' => $roleName, 'is_system' => true],
            );

            DB::table('role_has_permissions')->where('role_id', $role->id)->delete();

            foreach ($permissionNames as $permissionName) {
                $role->givePermissionTo($permissions[$permissionName]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
