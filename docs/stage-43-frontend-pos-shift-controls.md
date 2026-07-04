# Tahap 43 - Frontend POS Shift Controls

Status: complete.

## Ringkasan

Tahap ini memperluas action drawer modul Kasir untuk kontrol shift harian.

## Perubahan

- Action `Cash Movement` ditambahkan ke modul Kasir.
- Action `Close Shift` ditambahkan ke modul Kasir.
- `Cash Movement` submit ke `POST /api/cashier-shifts/{shift}/cash-movements`.
- `Close Shift` submit ke `POST /api/cashier-shifts/{shift}/close`.
- POS store menyimpan `expectedCash` dari active shift.
- POS store mengosongkan shift ID saat API tidak lagi mengembalikan active shift.
- POS store reload setelah cash movement atau close shift berhasil.

## Catatan Operasional

- `Cash Movement` dan `Close Shift` membutuhkan shift aktif.
- `Close Shift` memakai payload minimal `actual_cash` dan `notes`.
- Drawer counts tetap bisa ditambahkan pada tahap berikutnya bila dibutuhkan untuk audit denominasi kas detail.

## Verifikasi

- `npm run build`
- `php artisan test`
