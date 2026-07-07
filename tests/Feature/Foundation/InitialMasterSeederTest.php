<?php

namespace Tests\Feature\Foundation;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InitialMasterSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_master_seeders_are_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, Business::query()->whereIn('code', ['KCF', 'WG', 'LBY'])->count());
        $this->assertSame(3, Branch::query()->whereIn('code', ['KCF-01', 'WG-01', 'LBY-01'])->count());
        $this->assertSame(5, Role::query()
            ->whereNull('business_id')
            ->whereNull('branch_id')
            ->whereIn('slug', ['owner', 'admin', 'cashier', 'warehouse', 'accounting'])
            ->count());
        $this->assertSame(8, Permission::query()
            ->whereIn('name', [
                'manage business',
                'manage outlet',
                'manage product',
                'manage inventory',
                'manage sales',
                'view report',
                'manage expense',
                'manage user',
            ])
            ->count());

        $owner = User::query()->where('email', 'owner@kawipos.local')->firstOrFail();

        $this->assertSame('Owner', $owner->name);
        $this->assertTrue(Hash::check('password', $owner->password));
        $this->assertTrue($owner->hasRole('owner'));
        $this->assertSame(3, $owner->businesses()->count());
        $this->assertSame(3, $owner->outlets()->count());
    }
}
