# Tahap 55 - Frontend Inventory Stock Adjustment Action

Status: complete.

## Ringkasan

Tahap ini menambahkan action frontend untuk posting stock adjustment dari modul Inventory.

## Perubahan

- Action `Stock Adjustment` ditambahkan ke modul Inventory.
- `Stock Adjustment` submit ke `POST /api/stock-adjustments`.
- Payload adjustment memakai warehouse, product, quantity delta, unit cost, dan notes.
- Inventory store reload setelah adjustment berhasil diposting.

## Catatan Operasional

- Action memakai stock balance pertama sebagai default.
- Nomor adjustment otomatis dibuat jika field `adjustment_number` kosong.
- `quantity_delta` dapat bernilai positif atau negatif, tetapi tidak boleh nol sesuai validasi backend.

## Verifikasi

- `npm run build`
- `php artisan test`
