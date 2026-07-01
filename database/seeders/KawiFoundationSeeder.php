<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class KawiFoundationSeeder extends Seeder
{
    private const PERMISSIONS = [
        'sales.create',
        'sales.discount',
        'sales.void',
        'sales.refund',
        'inventory.view',
        'inventory.adjust',
        'purchases.manage',
        'reports.view',
        'accounting.manage',
        'users.manage',
    ];

    private const ROLE_PERMISSIONS = [
        'platform-super-admin' => [
            'sales.create',
            'sales.discount',
            'sales.void',
            'sales.refund',
            'inventory.view',
            'inventory.adjust',
            'purchases.manage',
            'reports.view',
            'accounting.manage',
            'users.manage',
        ],
        'business-owner' => [
            'sales.create',
            'sales.discount',
            'sales.void',
            'sales.refund',
            'inventory.view',
            'inventory.adjust',
            'purchases.manage',
            'reports.view',
            'accounting.manage',
            'users.manage',
        ],
        'branch-manager' => [
            'sales.create',
            'sales.discount',
            'sales.void',
            'sales.refund',
            'inventory.view',
            'inventory.adjust',
            'purchases.manage',
            'reports.view',
        ],
        'cashier' => [
            'sales.create',
            'sales.discount',
        ],
        'inventory-staff' => [
            'inventory.view',
            'inventory.adjust',
        ],
        'purchasing' => [
            'inventory.view',
            'purchases.manage',
        ],
        'accountant' => [
            'reports.view',
            'accounting.manage',
        ],
        'viewer' => [
            'inventory.view',
            'reports.view',
        ],
    ];

    public function run(): void
    {
        $business = Business::query()->firstOrCreate(
            ['name' => 'KAWI Demo Business'],
            ['uuid' => (string) Str::uuid(), 'currency' => 'IDR', 'timezone' => 'Asia/Makassar'],
        );

        $branch = Branch::query()->firstOrCreate(
            ['business_id' => $business->id, 'code' => 'MAIN'],
            ['uuid' => (string) Str::uuid(), 'name' => 'Cabang Utama', 'address' => 'KAWI POS HQ'],
        );

        $permissions = collect(self::PERMISSIONS)->mapWithKeys(
            fn (string $name) => [$name => Permission::query()->firstOrCreate(['name' => $name])],
        );

        $roles = collect(self::ROLE_PERMISSIONS)->mapWithKeys(function (array $rolePermissions, string $slug) use ($business, $permissions) {
            $role = Role::query()->firstOrCreate(
                ['business_id' => $business->id, 'branch_id' => null, 'slug' => $slug],
                ['name' => str($slug)->replace('-', ' ')->title()->toString(), 'is_system' => true],
            );

            $role->permissions()->sync($permissions->only($rolePermissions)->pluck('id')->all());

            return [$slug => $role];
        });

        $owner = User::query()->firstOrCreate(
            ['email' => 'owner@kawi.test'],
            [
                'name' => 'KAWI Owner',
                'password' => Hash::make('password'),
                'current_business_id' => $business->id,
                'current_branch_id' => $branch->id,
            ],
        );

        $owner->businesses()->syncWithoutDetaching([
            $business->id => ['is_owner' => true],
        ]);

        $owner->roles()->syncWithoutDetaching([
            $roles['business-owner']->id => [
                'business_id' => $business->id,
                'branch_id' => null,
            ],
        ]);
    }
}
