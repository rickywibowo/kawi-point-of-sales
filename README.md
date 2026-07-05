# KAWI Point of Sale

KAWI POS adalah aplikasi point of sale berbasis Laravel, Sanctum, Vue, Pinia, dan Vite. Project ini mencakup POS kasir, inventory, purchasing, accounting, reports, customer loyalty, user access, audit log, dan offline conflict review.

## Stack

- Backend: Laravel 13, Laravel Sanctum
- Frontend: Vue 3, Pinia, Vite, Tailwind CSS
- Database lokal default: SQLite
- Test: PHPUnit via `php artisan test`

## Setup Lokal

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
```

Untuk mode development:

```bash
composer run dev
```

Alternatif manual:

```bash
php artisan serve
npm run dev
```

Dashboard tersedia di:

```text
http://127.0.0.1:8000
```

Jika memakai Laravel Herd, buka domain/site Herd yang mengarah ke folder project ini.

## Login Demo

```text
Email: owner@kawi.test
Password: password
```

Setelah login, dashboard otomatis menyimpan token Sanctum dan tenant context demo untuk business dan branch KAWI.

## Back Office Filament

Back office CRUD tersedia di:

```text
http://127.0.0.1:8000/admin
```

Gunakan credential demo yang sama:

```text
Email: owner@kawi.test
Password: password
```

Resource awal:

- Categories
- Products
- Suppliers
- Customers
- Unit of Measures
- Taxes
- Warehouses
- Dining Tables
- Promotions
- Kitchen Stations
- Recipes
- Stock Balances
- Stock Adjustments
- Stock Opnames
- Stock Transfers
- Production Orders
- Purchase Orders
- Goods Receipts
- Purchase Returns
- Supplier Payables
- Supplier Payments
- Businesses
- Branches

Panduan cara pakai tersedia di menu `Support > Help` atau:

```text
http://127.0.0.1:8000/admin/help
```

Vue dashboard tetap dipakai untuk layar operasional POS, sedangkan Filament dipakai untuk CRUD back office.

## Data Demo

Seeder utama menjalankan:

- Foundation: business, branch, owner, role, permission
- Master data: product, supplier, customer, tax, UOM
- Inventory: warehouse, stock balance, stock ledger
- POS: cashier shift, table, reservation, promotion, kitchen, delivery
- Purchasing: purchase order, goods receipt, payable
- Accounting: chart of accounts, journals, reports
- Offline: sync batch dan conflict demo

Reset data lokal:

```bash
php artisan migrate:fresh --seed
```

## Cara Tambah Produk dan Kategori

1. Login dashboard.
2. Buka modul `Produk`.
3. Klik `New Category` bila kategori belum ada.
4. Setelah tersimpan, lihat `Category ID` di sidebar Master Data.
5. Klik `New Product`.
6. Isi `Category ID`, nama produk, type, dan harga.
7. Simpan produk.

Listing kategori juga tampil di workspace modul `Produk`. Untuk menghapus kategori, gunakan action `Delete Category`. Kategori yang masih memiliki produk atau child category tidak bisa dihapus.

Panduan cepat juga tersedia di modul `Help` pada dashboard.

## Verifikasi

```bash
npm run build
php artisan test
```

Baseline terakhir Stage 71:

- Frontend build sukses.
- Laravel test sukses: 82 tests, 469 assertions.

## Dokumentasi Project

- Progress: `docs/progress.md`
- Milestone: `docs/milestones.md`
- Release readiness: `docs/release-readiness.md`
- Dokumentasi per tahap: `docs/stage-*.md`

## Git Tracking

Setiap tahap development dicatat dalam commit terpisah.

```bash
git log --oneline
git show <commit>
```
