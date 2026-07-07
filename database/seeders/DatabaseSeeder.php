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
        $this->call([
            RolePermissionSeeder::class,
            BusinessSeeder::class,
            OutletSeeder::class,
            UnitSeeder::class,
            AccountSeeder::class,
            DefaultUserSeeder::class,
            RbacDemoUserSeeder::class,
        ]);

        // Legacy demo seeders were removed from the active baseline after Stage 80.
    }
}
