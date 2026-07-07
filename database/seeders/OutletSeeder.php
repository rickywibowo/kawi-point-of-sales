<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Business;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OutletSeeder extends Seeder
{
    private const OUTLETS = [
        [
            'business_code' => 'KCF',
            'name' => 'Kawi Chinese Food - Main Outlet',
            'code' => 'KCF-01',
        ],
        [
            'business_code' => 'WG',
            'name' => 'Warung Guan - Main Outlet',
            'code' => 'WG-01',
        ],
        [
            'business_code' => 'LBY',
            'name' => 'Lumpia Busung Yeh - Main Outlet',
            'code' => 'LBY-01',
        ],
    ];

    public function run(): void
    {
        foreach (self::OUTLETS as $outlet) {
            $business = Business::query()->where('code', $outlet['business_code'])->firstOrFail();

            Branch::query()->updateOrCreate(
                ['business_id' => $business->id, 'code' => $outlet['code']],
                [
                    'uuid' => Branch::query()
                        ->where('business_id', $business->id)
                        ->where('code', $outlet['code'])
                        ->value('uuid') ?? (string) Str::uuid(),
                    'name' => $outlet['name'],
                    'is_active' => true,
                ],
            );
        }
    }
}
