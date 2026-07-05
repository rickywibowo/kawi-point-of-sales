# Tahap 70 - Filament Master and POS Setup Resources

Status: complete.

## Ringkasan

Tahap ini memperluas back office Filament agar CRUD master data dan setup operasional POS tidak lagi bergantung pada form custom Vue.

## Perubahan

- Resource Filament ditambahkan untuk:
  - Suppliers
  - Customers
  - Unit of Measures
  - Taxes
  - Warehouses
  - Dining Tables
  - Promotions
  - Kitchen Stations
- Helper `ScopesToBusiness` dan `ScopesToBranch` dibuat untuk membatasi listing Filament ke tenant aktif.
- `TenantContext` menambahkan helper `branchId`.
- Form supplier, customer, unit, tax, dan warehouse menyembunyikan business/branch teknis dan mengisi dari tenant aktif.
- Form/table dining table, promotion, dan kitchen station dibuat manual agar listing CRUD tidak kosong.

## Akses

```text
/admin
```

Resource baru muncul di grup:

- Master Data
- CRM
- Inventory
- POS Setup

## Catatan Arsitektur

- CRUD back office dilanjutkan di Filament.
- Vue tetap difokuskan untuk layar kasir dan operasional harian.
- Tahap berikutnya dapat menambahkan inventory document, purchasing, accounting, user access, dan audit resources.

## Verifikasi

- `php artisan route:list --path=admin`
- PHP lint semua file `app/Filament`
- `npm run build`
- `php artisan test`
