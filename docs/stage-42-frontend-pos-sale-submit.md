# Tahap 42 - Frontend POS Sale Submit

Status: complete.

## Ringkasan

Tahap ini menghubungkan action `New Sale` pada modul Kasir ke endpoint sale completion.

## Perubahan

- POS index API mengembalikan `active_shift` untuk user dan branch aktif.
- POS store frontend menyimpan shift ID, daftar produk POS, dan daftar warehouse POS dari API.
- Cart item dari API menyimpan `productId` bila tersedia.
- Action `New Sale` submit ke `POST /api/sales`.
- Payload sale memakai shift aktif, warehouse, produk, quantity, unit price, payment method, payment amount, dan idempotency key.
- POS store reload setelah sale berhasil.

## Catatan Operasional

- `New Sale` membutuhkan shift aktif. Jalankan action `Open Shift` lebih dulu bila belum ada shift terbuka.
- Payment amount default dibuat lebih tinggi dari harga item agar memenuhi validasi paid total saat produk terkena pajak.
- Sale default bertipe `takeaway` untuk payload awal yang paling sederhana.

## Verifikasi

- `npm run build`
- `php artisan test`
