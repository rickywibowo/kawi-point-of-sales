<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Business;
use App\Models\CashierShift;
use App\Models\HeldTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PosSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $branch = Branch::query()->where('business_id', $business->id)->where('code', 'MAIN')->firstOrFail();
        $owner = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();

        CashierShift::query()->firstOrCreate(
            ['business_id' => $business->id, 'shift_number' => 'SHIFT-SEED-001'],
            [
                'branch_id' => $branch->id,
                'user_id' => $owner->id,
                'uuid' => (string) Str::uuid(),
                'opening_cash' => 250000,
                'expected_cash' => 250000,
                'actual_cash' => 250000,
                'cash_difference' => 0,
                'status' => 'closed',
                'opened_at' => now()->subDay(),
                'closed_at' => now()->subDay()->addHours(8),
                'notes' => 'Seed shift historis',
            ],
        );

        HeldTransaction::query()->firstOrCreate(
            ['business_id' => $business->id, 'hold_number' => 'HOLD-SEED-001'],
            [
                'branch_id' => $branch->id,
                'cashier_id' => $owner->id,
                'uuid' => (string) Str::uuid(),
                'payload' => [
                    'items' => [
                        ['product_id' => $product->id, 'quantity' => 1, 'notes' => 'Demo hold'],
                    ],
                ],
                'held_at' => now(),
            ],
        );
    }
}
