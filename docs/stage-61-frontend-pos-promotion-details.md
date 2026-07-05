# Tahap 61 - Frontend POS Promotion Details

Status: complete.

## Ringkasan

Tahap ini memperkaya tampilan promo aktif di dashboard POS dengan detail aturan promo dari POS API.

## Perubahan

- POS store menyimpan `minimumSubtotal`, `maximumDiscount`, `startsOn`, `endsOn`, dan `isActive` dari response `GET /api/pos`.
- Data demo promo disesuaikan agar tetap menampilkan detail promo saat API belum tersedia.
- Panel POS menampilkan nilai promo, minimum transaksi, maksimum diskon, dan periode berlaku.

## Catatan Operasional

- Mapping membaca field backend `minimum_subtotal`, `maximum_discount`, `starts_on`, `ends_on`, dan `is_active`.
- Promo tanpa tanggal berlaku ditampilkan sebagai `Tanpa batas tanggal`.
- Stage ini membuat kasir lebih mudah memvalidasi promo sebelum checkout.

## Verifikasi

- `npm run build`
- `php artisan test`
