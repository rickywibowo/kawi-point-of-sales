# Tahap 46 - Frontend POS Reservation Lifecycle

Status: complete.

## Ringkasan

Tahap ini melengkapi workflow reservasi meja dari action drawer POS.

## Perubahan

- Action `Seat Reservation` ditambahkan ke modul Kasir.
- Action `Cancel Reservation` ditambahkan ke modul Kasir.
- `Seat Reservation` submit ke `PATCH /api/table-reservations/{reservation}/seat`.
- `Cancel Reservation` submit ke `PATCH /api/table-reservations/{reservation}/cancel`.
- Action lifecycle reservasi memakai ID reservation dari POS store.
- POS store reload setelah seat atau cancel reservation berhasil.

## Catatan Operasional

- Action ini membutuhkan reservation ID dari response POS.
- Default reservation diambil dari reservasi berstatus `booked` bila tersedia.
- Jika belum ada reservasi aktif, buat dulu melalui action `Reserve Table`.

## Verifikasi

- `npm run build`
- `php artisan test`
