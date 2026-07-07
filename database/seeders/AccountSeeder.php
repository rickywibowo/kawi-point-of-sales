<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Business;
use App\Models\OutletAccountMapping;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        Business::query()->orderBy('id')->each(function (Business $business): void {
            foreach ($this->accounts() as $account) {
                Account::query()->updateOrCreate(
                    ['business_id' => $business->id, 'code' => $account['code']],
                    [
                        'name' => $account['name'],
                        'type' => $account['type'],
                        'normal_balance' => $this->normalBalance($account['type']),
                        'is_cash' => in_array($account['code'], ['1-1000', '1-1010', '1-1020'], true),
                        'is_cash_account' => in_array($account['code'], ['1-1000', '1-1010', '1-1020'], true),
                        'is_system' => true,
                        'is_active' => true,
                    ],
                );
            }

            Branch::query()
                ->where('business_id', $business->id)
                ->orderBy('id')
                ->each(fn (Branch $outlet) => $this->createMappings($business, $outlet));
        });
    }

    private function createMappings(Business $business, Branch $outlet): void
    {
        $accounts = Account::query()
            ->where('business_id', $business->id)
            ->whereIn('code', ['1-1000', '1-1010', '1-1020', '6-9000', '7-9000'])
            ->get()
            ->keyBy('code');

        foreach ($this->mappingCodes() as $purpose => $accountCode) {
            $account = $accounts[$accountCode] ?? null;

            if (! $account) {
                continue;
            }

            OutletAccountMapping::query()->updateOrCreate(
                [
                    'business_id' => $business->id,
                    'outlet_id' => $outlet->id,
                    'account_purpose' => $purpose,
                ],
                [
                    'account_id' => $account->id,
                    'is_active' => true,
                ],
            );
        }
    }

    private function accounts(): array
    {
        return [
            ['code' => '1-1000', 'name' => 'Cash', 'type' => 'asset'],
            ['code' => '1-1010', 'name' => 'Bank', 'type' => 'asset'],
            ['code' => '1-1020', 'name' => 'QRIS Receivable', 'type' => 'asset'],
            ['code' => '1-1100', 'name' => 'Inventory', 'type' => 'asset'],
            ['code' => '2-1000', 'name' => 'Accounts Payable', 'type' => 'liability'],
            ['code' => '3-1000', 'name' => 'Owner Capital', 'type' => 'equity'],
            ['code' => '3-9000', 'name' => 'Opening Balance Equity', 'type' => 'equity'],
            ['code' => '4-1000', 'name' => 'Sales Food', 'type' => 'income'],
            ['code' => '4-1010', 'name' => 'Sales Beverage', 'type' => 'income'],
            ['code' => '4-1020', 'name' => 'Sales Other', 'type' => 'income'],
            ['code' => '5-1000', 'name' => 'COGS Food', 'type' => 'cogs'],
            ['code' => '5-1010', 'name' => 'COGS Beverage', 'type' => 'cogs'],
            ['code' => '6-1000', 'name' => 'Rent Expense', 'type' => 'expense'],
            ['code' => '6-1010', 'name' => 'Salary Expense', 'type' => 'expense'],
            ['code' => '6-1020', 'name' => 'Utilities Expense', 'type' => 'expense'],
            ['code' => '6-1030', 'name' => 'Payment Fee Expense', 'type' => 'expense'],
            ['code' => '6-9000', 'name' => 'Cash Shortage Expense', 'type' => 'expense'],
            ['code' => '7-1000', 'name' => 'Other Income', 'type' => 'other_income'],
            ['code' => '7-9000', 'name' => 'Cash Overage Income', 'type' => 'other_income'],
        ];
    }

    private function mappingCodes(): array
    {
        return [
            'cash' => '1-1000',
            'bank' => '1-1010',
            'qris_receivable' => '1-1020',
            'cash_shortage' => '6-9000',
            'cash_overage' => '7-9000',
        ];
    }

    private function normalBalance(string $type): string
    {
        return in_array($type, ['asset', 'cogs', 'expense', 'other_expense'], true) ? 'debit' : 'credit';
    }
}
