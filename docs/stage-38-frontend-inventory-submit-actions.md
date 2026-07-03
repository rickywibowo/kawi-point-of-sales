# Tahap 38 - Frontend Inventory Submit Actions

Status: complete.

## Ringkasan

Tahap ini menghubungkan action drawer modul Inventori ke endpoint API inventory yang sudah tersedia.

## Perubahan

- Store inventory frontend menyimpan daftar warehouse, `warehouseId`, `productId`, dan `recipeId` dari response API.
- Action `Stock Opname` submit ke `POST /api/stock-opnames`.
- Action `Transfer Stock` submit ke `POST /api/stock-transfers`.
- Action `Production` submit ke `POST /api/production-orders`.
- Payload drawer mengikuti validasi backend untuk warehouse, product, recipe, dan quantity.
- Inventory store reload setelah submit inventory berhasil.

## Catatan Operasional

- `Stock Opname` dapat memakai default produk stok pertama dari data API.
- `Production` dapat memakai default recipe pertama dari data API.
- `Transfer Stock` membutuhkan `To Warehouse ID` yang berbeda dari gudang asal sesuai validasi backend.

## Verifikasi

- `npm run build`
- `php artisan test`
