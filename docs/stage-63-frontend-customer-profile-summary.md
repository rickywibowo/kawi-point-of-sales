# Tahap 63 - Frontend Customer Profile Summary

Status: complete.

## Ringkasan

Tahap ini menghubungkan dashboard Customer CRM ke endpoint profile customer agar admin bisa melihat ringkasan pelanggan aktif dari API.

## Perubahan

- Customer store menambahkan `customerProfile` untuk summary pelanggan aktif.
- Customer store menambahkan `recentSales` dari endpoint `GET /api/customers/{customer}`.
- Customer store memetakan loyalty transaction terbaru dari profile customer.
- Panel Customer CRM menampilkan active profile, average order value, last purchase, recent sale, dan loyalty terakhir.

## Catatan Operasional

- Setelah `GET /api/customers` berhasil, store otomatis mengambil profile customer pertama.
- Data demo tetap menjadi fallback bila API belum mengirim profile atau recent sale.
- Stage ini memakai endpoint customer profile yang sudah tersedia tanpa menambah kontrak backend baru.

## Verifikasi

- `npm run build`
- `php artisan test`
