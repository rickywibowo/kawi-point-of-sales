# Tahap 45 - Frontend POS Table Actions

Status: complete.

## Ringkasan

Tahap ini memperluas action drawer modul Kasir untuk workflow meja dan reservasi.

## Perubahan

- Action `Table Status` ditambahkan ke modul Kasir.
- Action `Reserve Table` ditambahkan ke modul Kasir.
- `Table Status` submit ke `PATCH /api/dining-tables/{table}/status`.
- `Reserve Table` submit ke `POST /api/dining-tables/{table}/reservations`.
- POS store menyimpan ID table reservation dari API.
- POS store reload setelah action meja atau reservasi berhasil.

## Catatan Operasional

- `Table Status` membutuhkan dining table ID dari data POS.
- `Reserve Table` memakai meja available pertama sebagai default bila tersedia.
- Waktu reservasi default diarahkan ke satu jam setelah waktu browser.

## Verifikasi

- `npm run build`
- `php artisan test`
