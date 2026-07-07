<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class RbacDemoUserSeeder extends Seeder
{
    /**
     * @var array<int, array{name: string, email: string, role: string, business: string, outlet: string}>
     */
    private const USERS = [
        ['name' => 'Admin KCF', 'email' => 'admin.kcf@kawipos.local', 'role' => 'admin', 'business' => 'KCF', 'outlet' => 'KCF-01'],
        ['name' => 'Cashier KCF', 'email' => 'cashier.kcf@kawipos.local', 'role' => 'cashier', 'business' => 'KCF', 'outlet' => 'KCF-01'],
        ['name' => 'Warehouse KCF', 'email' => 'warehouse.kcf@kawipos.local', 'role' => 'warehouse', 'business' => 'KCF', 'outlet' => 'KCF-01'],
        ['name' => 'Accounting KCF', 'email' => 'accounting.kcf@kawipos.local', 'role' => 'accounting', 'business' => 'KCF', 'outlet' => 'KCF-01'],
        ['name' => 'Admin WG', 'email' => 'admin.wg@kawipos.local', 'role' => 'admin', 'business' => 'WG', 'outlet' => 'WG-01'],
        ['name' => 'Cashier WG', 'email' => 'cashier.wg@kawipos.local', 'role' => 'cashier', 'business' => 'WG', 'outlet' => 'WG-01'],
        ['name' => 'Admin LBY', 'email' => 'admin.lby@kawipos.local', 'role' => 'admin', 'business' => 'LBY', 'outlet' => 'LBY-01'],
        ['name' => 'Cashier LBY', 'email' => 'cashier.lby@kawipos.local', 'role' => 'cashier', 'business' => 'LBY', 'outlet' => 'LBY-01'],
    ];

    public function run(): void
    {
        foreach (self::USERS as $demoUser) {
            $business = Business::query()->where('code', $demoUser['business'])->firstOrFail();
            $outlet = Branch::query()
                ->where('business_id', $business->id)
                ->where('code', $demoUser['outlet'])
                ->firstOrFail();
            $role = Role::query()
                ->where('slug', $demoUser['role'])
                ->where('guard_name', 'web')
                ->whereNull('business_id')
                ->whereNull('branch_id')
                ->firstOrFail();

            $user = User::query()->updateOrCreate(
                ['email' => $demoUser['email']],
                [
                    'name' => $demoUser['name'],
                    'password' => Hash::make('password'),
                    'current_business_id' => $business->id,
                    'current_branch_id' => $outlet->id,
                ],
            );

            $businessPivot = ['is_owner' => false];

            if (Schema::hasColumn('business_user', 'role_name')) {
                $businessPivot['role_name'] = $demoUser['role'];
            }

            $user->businesses()->sync([$business->id => $businessPivot]);
            $user->outlets()->sync([$outlet->id]);
            $user->syncRoles([$role]);
        }
    }
}
