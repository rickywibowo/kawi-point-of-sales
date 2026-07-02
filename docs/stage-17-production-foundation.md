# Tahap 17 - Production Foundation

Status: complete.

## Tujuan

Tahap ini menambahkan fondasi produksi menu atau bahan setengah jadi berbasis recipe:

- Production order.
- Pemakaian bahan berdasarkan recipe.
- Output produk aktual.
- Actual yield versus planned yield.
- Waste quantity.
- Stock ledger untuk konsumsi bahan dan hasil produksi.

## Database

Migration baru:

- `2026_07_02_000008_create_production_tables.php`

Tabel baru:

- `production_orders`
- `production_order_items`

## Endpoint

```http
POST /api/production-orders
```

Middleware:

- `auth:sanctum`
- `tenant`
- `permission:inventory.adjust`

## Stock Ledger

Posting produksi membuat:

- `production_consumption` untuk bahan keluar.
- `production_output` untuk produk hasil masuk.

## Yield dan Waste

Input:

- `planned_quantity`
- `actual_quantity`

Sistem menghitung:

```text
waste_quantity = max(planned_quantity - actual_quantity, 0)
```

## Frontend

Inventory dashboard menampilkan ringkasan production order terakhir.

## Test

Automated test:

- `tests/Feature/Inventory/InventoryTest.php`

Coverage:

- Production order mengurangi stok ingredient.
- Production order menambah stok output.
- Production order membuat ledger consumption/output.
- Recipe dari business lain ditolak.
