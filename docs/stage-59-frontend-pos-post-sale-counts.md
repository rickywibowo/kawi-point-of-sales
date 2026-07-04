# Tahap 59 - Frontend POS Post-Sale Counts

Status: complete.

## Ringkasan

Tahap ini mengganti angka demo post-sale controls dengan perhitungan dari sale harian API.

## Perubahan

- POS store menghitung `voidedToday` dari `today_sales` berstatus `voided`.
- POS store menghitung `refundedToday` dari `today_sales` berstatus `refunded`.
- Panel Post-Sale Controls memakai counter yang berasal dari API.

## Catatan Operasional

- Jika API belum mengirim `today_sales`, data demo tetap menjadi fallback.
- Counter mengikuti data `GET /api/pos` untuk branch aktif.

## Verifikasi

- `npm run build`
- `php artisan test`
