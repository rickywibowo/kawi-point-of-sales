# Stage 71 - Filament Inventory, Purchasing, and Production Resources

Status: complete.

## Ringkasan

Tahap ini memperluas back office Filament dari master data ke dokumen operasional inventory, production, dan purchasing.

## Perubahan

- Menambahkan Filament resource:
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
- Resource dikelompokkan ke navigation group:
  - Inventory
  - Production
  - Purchasing
- Resource memakai scope business atau branch sesuai model tenancy.
- Form header dokumen menyembunyikan `business_id`, `branch_id`, dan `uuid` yang harus otomatis.
- Dropdown relasi disediakan untuk supplier, warehouse, product, recipe, goods receipt, payable, dan cash account.
- Tabel Production Orders dan Supplier Payments diisi manual agar listing langsung usable.

## Verifikasi

```bash
php artisan route:list --path=admin
npm run build
php artisan test
```

Hasil:

- Admin route terdaftar: 66 route.
- Frontend build sukses.
- Laravel test sukses: 82 tests, 469 assertions.

## Catatan

Resource ini berfokus pada CRUD header dokumen dan listing back office. Detail item dokumen seperti item PO, item receipt, item opname, dan item production masih dapat ditingkatkan dengan Relation Manager Filament pada tahap lanjutan bila ingin editing detail dalam satu layar.
