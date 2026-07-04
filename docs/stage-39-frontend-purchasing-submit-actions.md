# Tahap 39 - Frontend Purchasing Submit Actions

Status: complete.

## Ringkasan

Tahap ini menghubungkan action drawer modul Purchasing ke endpoint API purchasing yang sudah tersedia.

## Perubahan

- Store master data frontend menyimpan supplier ID dan product ID/cost dari response API.
- Store purchasing frontend menyimpan ID purchase order, goods receipt, purchase return, dan supplier payable.
- Action `New PO` submit ke `POST /api/purchase-orders`.
- Action `Goods Receipt` submit ke `POST /api/goods-receipts`.
- Action `Pay Supplier` submit ke `POST /api/supplier-payables/{payable}/payments`.
- Payload drawer mengikuti validasi backend untuk supplier, warehouse, product, payable, quantity, dan unit cost.
- Purchasing dan inventory store reload setelah submit purchasing berhasil.

## Catatan Operasional

- `New PO` dan `Goods Receipt` memakai supplier, warehouse, dan produk pertama dari data API sebagai default.
- `Pay Supplier` membutuhkan payable yang masih terbuka atau partial agar validasi backend menerima payment.

## Verifikasi

- `npm run build`
- `php artisan test`
