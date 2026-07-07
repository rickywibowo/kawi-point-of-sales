<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BusinessSeeder extends Seeder
{
    private const BUSINESSES = [
        [
            'name' => 'Kawi Chinese Food',
            'code' => 'KCF',
            'type' => 'restaurant',
        ],
        [
            'name' => 'Warung Guan',
            'code' => 'WG',
            'type' => 'restaurant',
        ],
        [
            'name' => 'Lumpia Busung Yeh',
            'code' => 'LBY',
            'type' => 'restaurant',
        ],
    ];

    public function run(): void
    {
        foreach (self::BUSINESSES as $business) {
            Business::query()->updateOrCreate(
                ['code' => $business['code']],
                [
                    'uuid' => Business::query()->where('code', $business['code'])->value('uuid') ?? (string) Str::uuid(),
                    'name' => $business['name'],
                    'type' => $business['type'],
                    'currency' => 'IDR',
                    'timezone' => 'Asia/Makassar',
                    'is_active' => true,
                ],
            );
        }
    }
}
