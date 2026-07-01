<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Business;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();

        AccountingPeriod::query()->firstOrCreate(
            ['business_id' => $business->id, 'name' => now()->format('Y-m')],
            [
                'starts_on' => now()->startOfMonth()->toDateString(),
                'ends_on' => now()->endOfMonth()->toDateString(),
                'status' => 'open',
            ],
        );

        foreach ($this->accounts() as $account) {
            Account::query()->firstOrCreate(
                ['business_id' => $business->id, 'code' => $account['code']],
                $account,
            );
        }
    }

    private function accounts(): array
    {
        return [
            ['code' => '1100', 'name' => 'Kas', 'type' => 'asset', 'normal_balance' => 'debit', 'is_cash' => true],
            ['code' => '1200', 'name' => 'Bank', 'type' => 'asset', 'normal_balance' => 'debit', 'is_cash' => true],
            ['code' => '1300', 'name' => 'Persediaan', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1400', 'name' => 'Pajak Masukan', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1500', 'name' => 'Piutang Usaha', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '2100', 'name' => 'Utang Usaha', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2200', 'name' => 'Pajak Keluaran', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '3100', 'name' => 'Modal Pemilik', 'type' => 'equity', 'normal_balance' => 'credit'],
            ['code' => '4100', 'name' => 'Penjualan', 'type' => 'revenue', 'normal_balance' => 'credit'],
            ['code' => '5100', 'name' => 'Harga Pokok Penjualan', 'type' => 'cost_of_goods_sold', 'normal_balance' => 'debit'],
            ['code' => '6100', 'name' => 'Beban Operasional', 'type' => 'expense', 'normal_balance' => 'debit'],
        ];
    }
}
