# Tahap 56 - Frontend Inventory Recipe Action

Status: complete.

## Ringkasan

Tahap ini menambahkan action frontend untuk membuat recipe dari modul Inventory.

## Perubahan

- Action `New Recipe` ditambahkan ke modul Inventory.
- `New Recipe` submit ke `POST /api/recipes`.
- Payload recipe memakai product, ingredient product, yield quantity, ingredient quantity, unit cost, dan waste percentage.
- Inventory store reload setelah recipe berhasil dibuat.

## Catatan Operasional

- Action memakai produk pertama sebagai product hasil recipe.
- Ingredient default memakai produk kedua, atau produk pertama jika hanya ada satu produk.
- Recipe dibuat aktif dengan `version` default `1`.

## Verifikasi

- `npm run build`
- `php artisan test`
