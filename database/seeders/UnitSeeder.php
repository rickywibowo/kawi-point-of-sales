<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        Business::query()->orderBy('id')->each(function (Business $business): void {
            foreach ($this->units() as $unit) {
                Unit::query()->updateOrCreate(
                    [
                        'business_id' => $business->id,
                        'name' => $unit['name'],
                    ],
                    [
                        'symbol' => $unit['symbol'],
                        'type' => $unit['type'],
                        'is_active' => true,
                    ],
                );
            }
        });
    }

    private function units(): array
    {
        return [
            ['name' => 'Porsi', 'symbol' => 'porsi', 'type' => 'quantity'],
            ['name' => 'Pcs', 'symbol' => 'pcs', 'type' => 'quantity'],
            ['name' => 'Gram', 'symbol' => 'g', 'type' => 'weight'],
            ['name' => 'Kilogram', 'symbol' => 'kg', 'type' => 'weight'],
            ['name' => 'Mililiter', 'symbol' => 'ml', 'type' => 'volume'],
            ['name' => 'Liter', 'symbol' => 'L', 'type' => 'volume'],
        ];
    }
}
