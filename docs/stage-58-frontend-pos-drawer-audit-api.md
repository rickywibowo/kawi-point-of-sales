# Tahap 58 - Frontend POS Drawer Audit API

Status: complete.

## Ringkasan

Tahap ini menghubungkan data cash drawer audit dari POS API ke POS store frontend.

## Perubahan

- POS store menyimpan daftar `drawerAudits` dari response `GET /api/pos`.
- POS store memetakan audit terbaru ke `drawerAudit`.
- Panel POS menggunakan status, expected cash, counted cash, variance, dan denomination breakdown dari API.

## Catatan Operasional

- Jika API belum mengirim `cash_drawer_audits`, data demo tetap dipakai sebagai fallback.
- Denomination breakdown mengikuti payload JSON dari backend.
- Mapping ini menyiapkan UI kasir untuk audit tutup shift yang lebih nyata.

## Verifikasi

- `npm run build`
- `php artisan test`
