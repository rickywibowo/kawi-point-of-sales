# Tahap 2 - Master Data

## Scope

Tahap ini menyiapkan master data inti untuk POS:

- Produk
- Kategori dan subkategori
- Varian produk
- Modifier dan topping
- Unit of measure
- Supplier
- Customer
- Pajak
- Harga produk per cabang

## Database Schema

- `unit_of_measures`
  - Scope: `business_id`
  - Menyimpan satuan dasar seperti PCS dan GRAM.
- `taxes`
  - Scope: `business_id`
  - Menyimpan pajak seperti PPN 11%.
- `categories`
  - Scope: `business_id`
  - Mendukung `parent_id` untuk subkategori.
- `suppliers`
  - Scope: `business_id`
  - Fondasi purchasing.
- `customers`
  - Scope: `business_id`
  - Fondasi riwayat pelanggan, piutang, loyalty.
- `products`
  - Scope: `business_id`
  - Mendukung tipe `goods`, `food`, `beverage`, dan `service`.
  - Mendukung SKU, barcode, base price, cost price, tax, category, dan stock tracking.
- `branch_product_prices`
  - Scope: `business_id` dan `branch_id`
  - Menyimpan harga berbeda per cabang.
- `product_variants`
  - Scope: `business_id`
  - Menyimpan variasi produk dengan price/cost delta.
- `modifier_groups`
  - Scope: `business_id`
  - Menyimpan grup modifier seperti topping.
- `modifiers`
  - Scope: `business_id`
  - Menyimpan item modifier seperti Extra Sambal.
- `modifier_group_product`
  - Mapping produk ke modifier group.

## API Endpoint

Semua endpoint di bawah memakai:

- `auth:sanctum`
- `tenant`
- Header `X-Business-Id`
- Optional header `X-Branch-Id`

Endpoint:

- `GET /api/master-data`
  - Permission: `inventory.view`
  - Mengembalikan unit, tax, kategori, supplier, customer, modifier group, dan produk.
- `POST /api/categories`
  - Permission: `inventory.adjust`
  - Membuat kategori/subkategori dalam business aktif.
- `POST /api/products`
  - Permission: `inventory.adjust`
  - Membuat produk dan optional harga per cabang.

## Tenant Isolation

- Semua query master data memakai `business_id`.
- `branch_product_prices` memastikan `branch_id` berasal dari business aktif.
- Category, unit, dan tax yang dipakai produk harus berasal dari business aktif.
- Data business lain tidak muncul di `GET /api/master-data`.

## Seed Data

Seeder membuat:

- Unit: PCS, GRAM
- Tax: PPN 11%
- Category: Makanan, Minuman, Kopi
- Supplier: Supplier Bahan Baku KAWI
- Customer: Walk-in Customer
- Product: KAWI Rice Bowl, KAWI Iced Coffee
- Modifier: Extra Sambal, Extra Shot

## Test

Test Tahap 2 mencakup:

- Master data index hanya menampilkan data business aktif.
- Category dan product bisa dibuat dalam scope business aktif.
- Branch price dari business lain ditolak.
- Audit log tercatat saat produk dibuat.
