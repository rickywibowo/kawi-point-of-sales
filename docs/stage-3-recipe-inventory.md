# Tahap 3 - Recipe dan Inventory

## Scope

Tahap ini menyiapkan fondasi recipe/BOM dan inventory berbasis stock ledger.

- Recipe
- Recipe items
- Unit conversion
- Warehouse per business/cabang
- Stock ledger
- Stock balance
- Stock adjustment
- Stock opname header
- Stock transfer header

## Database Schema

- `unit_conversions`
  - Scope: `business_id`
  - Menyimpan konversi antar-unit.
- `warehouses`
  - Scope: `business_id`, optional `branch_id`
  - Menyimpan gudang cabang atau central.
- `recipes`
  - Scope: `business_id`
  - Menghubungkan produk hasil ke BOM, yield, waste, version, dan computed cost.
- `recipe_items`
  - Scope: `business_id`
  - Menyimpan ingredient product, quantity, unit, waste, dan line cost.
- `stock_ledgers`
  - Scope: `business_id`, optional `branch_id`, `warehouse_id`
  - Menyimpan semua movement: opening balance, adjustment, sales consumption, receipt, transfer, waste.
- `stock_balances`
  - Scope: `business_id`, optional `branch_id`, `warehouse_id`
  - Cache saldo dari ledger untuk query cepat.
- `stock_adjustments`
  - Dokumen adjustment posted.
- `stock_adjustment_items`
  - Detail delta stok dan unit cost.
- `stock_opnames`
  - Header fondasi opname.
- `stock_transfers`
  - Header fondasi transfer stok.

## API Endpoint

Semua endpoint memakai:

- `auth:sanctum`
- `tenant`
- Header `X-Business-Id`
- Optional header `X-Branch-Id`

Endpoint:

- `GET /api/inventory`
  - Permission: `inventory.view`
  - Mengembalikan warehouse, stock balance, ledger terakhir, dan recipe.
- `POST /api/recipes`
  - Permission: `inventory.adjust`
  - Membuat recipe dan menghitung `computed_cost` dari item.
- `POST /api/stock-adjustments`
  - Permission: `inventory.adjust`
  - Membuat adjustment posted, menulis stock ledger, dan update stock balance.

## Prinsip Ledger

- Stock balance tidak diubah langsung oleh controller.
- Perubahan stok harus melewati service dan database transaction.
- Setiap adjustment menghasilkan baris `stock_ledgers`.
- `stock_balances` adalah cache posisi stok terkini dari ledger.
- Source document disimpan lewat `source_type`, `source_id`, dan `reference_number`.

## Tenant Isolation

- Warehouse harus berada dalam business aktif.
- Product dan ingredient recipe harus berada dalam business aktif.
- Query inventory memakai `business_id` dan optional `branch_id`.
- Adjustment lintas business ditolak lewat validation service.

## Seed Data

Seeder membuat:

- Warehouse: Gudang Cabang Utama
- Unit conversion contoh
- Recipe: Recipe KAWI Rice Bowl
- Opening stock ledger
- Stock balance awal untuk produk demo

## Test

Test Tahap 3 mencakup:

- Inventory index hanya menampilkan data tenant aktif.
- Stock adjustment membuat ledger dan mengupdate balance.
- Recipe menolak ingredient dari business lain.
- Recipe menghitung cost dari item dan waste.
