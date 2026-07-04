# Tahap 47 - Frontend POS Status Actions

Status: complete.

## Ringkasan

Tahap ini menambahkan action status operasional untuk kitchen ticket dan delivery order.

## Perubahan

- POS store menyimpan ID kitchen ticket dari API.
- POS store menyimpan ID delivery order dari API.
- Action `Kitchen Status` ditambahkan ke modul Kasir.
- Action `Delivery Status` ditambahkan ke modul Kasir.
- `Kitchen Status` submit ke `PATCH /api/kitchen-tickets/{ticket}/status`.
- `Delivery Status` submit ke `PATCH /api/delivery-orders/{delivery}/status`.
- POS store reload setelah update status kitchen atau delivery berhasil.

## Catatan Operasional

- `Kitchen Status` memakai ticket aktif pertama sebagai default.
- `Delivery Status` memakai delivery order aktif pertama sebagai default.
- Update status item kitchen per item belum disambungkan di tahap ini karena membutuhkan item ID yang belum ditampilkan di store ringkas.

## Verifikasi

- `npm run build`
- `php artisan test`
