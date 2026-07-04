# Tahap 44 - Frontend POS Setup Actions

Status: complete.

## Ringkasan

Tahap ini menambahkan action setup operasional POS dari dashboard frontend.

## Perubahan

- Action `New Promo` ditambahkan ke modul Kasir dan submit ke `POST /api/promotions`.
- Action `New Table` ditambahkan ke modul Kasir dan submit ke `POST /api/dining-tables`.
- Action `Kitchen Station` ditambahkan ke modul Kasir dan submit ke `POST /api/kitchen-stations`.
- POS store menyimpan ID promo, dining table, dan kitchen station dari API.
- POS store reload setelah action setup POS berhasil.

## Catatan Operasional

- `New Promo` memakai default promo aktif.
- `New Table` membuat meja dengan status awal `available`.
- `Kitchen Station` membuat station aktif untuk routing KDS.

## Verifikasi

- `npm run build`
- `php artisan test`
