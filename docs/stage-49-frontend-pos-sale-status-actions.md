# Tahap 49 - Frontend POS Sale Status Actions

Status: complete.

## Ringkasan

Tahap ini menambahkan action post-sale untuk void dan refund sale dari dashboard frontend.

## Perubahan

- POS store menyimpan daftar `sales` dari response POS.
- Action `Void Sale` ditambahkan ke modul Kasir.
- Action `Refund Sale` ditambahkan ke modul Kasir.
- `Void Sale` submit ke `POST /api/sales/{sale}/void`.
- `Refund Sale` submit ke `POST /api/sales/{sale}/refund`.
- POS store reload setelah void atau refund sale berhasil.

## Catatan Operasional

- Action memakai sale berstatus `completed` pertama sebagai default.
- Field `reason` dikirim sebagai alasan void atau refund.
- Hak akses backend tetap mengikuti permission `sales.void` dan `sales.refund`.

## Verifikasi

- `npm run build`
- `php artisan test`
