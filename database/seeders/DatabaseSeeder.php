<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(KawiFoundationSeeder::class);
        $this->call(AccountingSeeder::class);
        $this->call(MasterDataSeeder::class);
        $this->call(InventorySeeder::class);
        $this->call(PosSeeder::class);
        $this->call(PurchasingSeeder::class);
    }
}
