# Tahap 48 - Frontend POS Kitchen Item Status

Status: complete.

## Ringkasan

Tahap ini menambahkan action status untuk item kitchen ticket dari dashboard frontend.

## Perubahan

- POS store menyimpan daftar `kitchenTicketItems` dari response POS.
- Action `Kitchen Item Status` ditambahkan ke modul Kasir.
- `Kitchen Item Status` submit ke `PATCH /api/kitchen-ticket-items/{item}/status`.
- POS store reload setelah update status item kitchen berhasil.

## Catatan Operasional

- Action ini memakai kitchen item aktif pertama sebagai default.
- Status yang diterima backend: `preparing`, `ready`, `served`, dan `cancelled`.
- Update ticket status tetap tersedia lewat action `Kitchen Status`.

## Verifikasi

- `npm run build`
- `php artisan test`
