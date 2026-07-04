# Tahap 51 - Frontend Purchasing Return Action

Status: complete.

## Ringkasan

Tahap ini menambahkan action frontend untuk membuat purchase return dari goods receipt.

## Perubahan

- Purchasing store menyimpan `supplierId` dan item pertama dari goods receipt.
- Action `Return Supplier` ditambahkan ke modul Purchasing.
- `Return Supplier` submit ke `POST /api/purchase-returns`.
- Payload return memakai goods receipt, receipt item, product, quantity, unit cost, dan reason.
- Purchasing dan inventory store reload setelah return supplier berhasil.

## Catatan Operasional

- Action memakai goods receipt pertama sebagai default.
- Nomor return otomatis dibuat jika field `return_number` kosong.
- Backend tetap memvalidasi receipt item dan jumlah return tidak boleh melebihi quantity received.

## Verifikasi

- `npm run build`
- `php artisan test`
