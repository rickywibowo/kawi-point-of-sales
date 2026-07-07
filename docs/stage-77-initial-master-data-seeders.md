# Stage 77 - Initial Master Data Seeders

Status: complete.

## Ringkasan

Tahap ini menambahkan seed awal untuk multi-business dan multi-outlet KAWI POS tanpa membuat data produk, inventory, sales, atau recipe.

## Perubahan

- Menambahkan kolom `code` dan `type` pada `businesses`.
- Menambahkan pivot `outlet_user` untuk relasi user ke outlet. Di aplikasi saat ini outlet direpresentasikan oleh model `Branch`.
- Menambahkan relation `Business`, `Branch`, dan `User` yang dibutuhkan oleh seed awal.
- Menambahkan seeder terpisah:
  - `RolePermissionSeeder`
  - `BusinessSeeder`
  - `OutletSeeder`
  - `DefaultUserSeeder`
- `DatabaseSeeder` sekarang memanggil seed awal dalam urutan role/permission, business, outlet, lalu user.
- Data demo lama untuk produk/inventory/sales sudah dikeluarkan dari baseline aktif.
- Seeder memakai `updateOrCreate` dan `syncWithoutDetaching` agar aman dijalankan berulang.

## Data Awal

Businesses:

- Kawi Chinese Food (`KCF`, restaurant)
- Warung Guan (`WG`, restaurant)
- Lumpia Busung Yeh (`LBY`, restaurant)

Outlets:

- Kawi Chinese Food - Main Outlet (`KCF-01`)
- Warung Guan - Main Outlet (`WG-01`)
- Lumpia Busung Yeh - Main Outlet (`LBY-01`)

Roles:

- owner
- admin
- cashier
- warehouse
- accounting

Permissions:

- manage business
- manage outlet
- manage product
- manage inventory
- manage sales
- view report
- manage expense
- manage user

Default user:

- Owner
- `owner@kawipos.local`
- password: `password`

## Verifikasi

```bash
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder --force
php artisan db:seed --class=DatabaseSeeder --force
php artisan test --filter=InitialMasterSeederTest
php artisan test
```

Hasil:

- Initial master seeder test sukses: 1 test, 9 assertions.
- Full Laravel test suite sukses: 85 tests, 485 assertions.
