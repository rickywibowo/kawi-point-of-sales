# Tahap 50 - Frontend POS Receipt Action

Status: complete.

## Ringkasan

Tahap ini menambahkan action frontend untuk mengambil digital receipt dari sale harian.

## Perubahan

- Action `View Receipt` ditambahkan ke modul Kasir.
- `View Receipt` submit sebagai GET ke `GET /api/sales/{sale}/receipt`.
- Feedback action menampilkan nomor receipt dan grand total.
- POS store menyimpan daftar `receipts` dari response sale harian.

## Catatan Operasional

- Action memakai sale berstatus `completed` pertama sebagai default.
- Endpoint receipt tetap mengikuti permission backend `sales.create`.
- Tahap ini belum menambahkan print layout khusus; hasil receipt diringkas di feedback drawer.

## Verifikasi

- `npm run build`
- `php artisan test`
