<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    private const BUSINESS_CODES = ['KCF', 'WG', 'LBY'];

    public function run(): void
    {
        $businesses = Business::query()
            ->whereIn('code', self::BUSINESS_CODES)
            ->orderBy('code')
            ->get()
            ->keyBy('code');

        $outlets = Branch::query()
            ->whereIn('business_id', $businesses->pluck('id'))
            ->whereIn('code', ['KCF-01', 'WG-01', 'LBY-01'])
            ->orderBy('code')
            ->get();

        $defaultBusiness = $businesses->get('KCF');
        $defaultOutlet = $outlets->firstWhere('code', 'KCF-01');

        $owner = User::query()->updateOrCreate(
            ['email' => 'owner@kawipos.local'],
            [
                'name' => 'Owner',
                'password' => Hash::make('password'),
                'current_business_id' => $defaultBusiness?->id,
                'current_branch_id' => $defaultOutlet?->id,
            ],
        );

        $owner->businesses()->syncWithoutDetaching(
            $businesses
                ->mapWithKeys(fn (Business $business) => [$business->id => ['is_owner' => true]])
                ->all(),
        );

        $owner->outlets()->syncWithoutDetaching($outlets->pluck('id')->all());

        $ownerRole = Role::query()
            ->where('slug', 'owner')
            ->where('guard_name', 'web')
            ->whereNull('business_id')
            ->whereNull('branch_id')
            ->firstOrFail();

        $owner->assignRole($ownerRole);
    }
}
