# KAWI POS Release Readiness

Status: MVP local demo ready.

## Local Run Checklist

1. Install dependency backend dan frontend.

```bash
composer install
npm install
```

2. Siapkan environment.

```bash
copy .env.example .env
php artisan key:generate
```

3. Reset dan isi database demo.

```bash
php artisan migrate:fresh --seed
```

4. Build asset frontend.

```bash
npm run build
```

5. Jalankan app.

```bash
composer run dev
```

Dashboard lokal:

```text
http://127.0.0.1:8000
```

Back office Filament:

```text
http://127.0.0.1:8000/admin
```

## Demo Login

```text
Email: owner@kawi.test
Password: password
```

## Demo Flow

- Login dashboard memakai credential demo.
- Buka modul Kasir dan cek POS cart, promo, table, reservation, kitchen, delivery, held transaction, dan post-sale controls.
- Buka modul Help untuk melihat panduan cepat operasional.
- Buka modul Produk, cek listing kategori, buat kategori lewat `New Category`, lalu gunakan `Category ID` saat `New Product`.
- Uji `Delete Category` pada kategori kosong. Kategori yang masih punya produk harus ditolak.
- Buka `/admin` untuk CRUD kategori, produk, supplier, customer, UOM, tax, warehouse, table, promotion, kitchen station, inventory, production, purchasing, payable, dan supplier payment via Filament back office.
- Jalankan action POS ringan seperti `Cash Movement`, `New Promo`, atau `View Receipt` bila data seed tersedia.
- Buka modul Inventori dan cek stock balance, recipe, stock adjustment, opname, transfer, dan production.
- Buka modul Purchasing dan cek PO, goods receipt, return supplier, payable, dan supplier payment.
- Buka modul Accounting dan cek trial balance, expense, settlement, dan provider import.
- Buka modul Laporan dan cek sales, stock value, top product, payment method, payable, dan settlement variance.
- Buka modul Pelanggan dan cek profile summary, recent sale, dan loyalty terakhir.
- Buka modul Pengaturan dan cek user access serta audit log terbaru.

## Verification Checklist

```bash
npm run build
php artisan test
```

Expected baseline:

- Frontend build sukses.
- Laravel test sukses: 82 tests, 469 assertions.

## API Notes

- API memakai Sanctum token dari login dashboard.
- Tenant context dikirim melalui business dan branch demo yang disimpan frontend.
- Dashboard tetap punya fallback demo data bila belum login atau API belum connected.
- Refresh dashboard menampilkan waktu refresh terakhir atau modul yang gagal saat refresh parsial.

## Release Notes

- Semua milestone utama 1 sampai 7 berstatus complete.
- Back office Filament tersedia untuk master data, POS setup, inventory, production, dan purchasing header.
- Stage terakhir sebelum dokumen ini: Stage 71 - Filament Inventory, Purchasing, and Production Resources.
- Action `Segment`, `Export`, dan `Print` masih bisa dijadikan enhancement berikutnya bila dibutuhkan workflow backend khusus.
