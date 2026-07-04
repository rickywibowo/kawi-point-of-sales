# Tahap 52 - Frontend Purchasing Approve PO Action

Status: complete.

## Ringkasan

Tahap ini menambahkan action frontend untuk approval purchase order dari dashboard Purchasing.

## Perubahan

- Action `Approve PO` ditambahkan ke modul Purchasing.
- `Approve PO` submit ke `POST /api/purchase-orders/{purchaseOrder}/approve`.
- Action memakai purchase order berstatus `draft` pertama sebagai default.
- Purchasing dan inventory store reload setelah approval berhasil.

## Catatan Operasional

- Backend tidak membutuhkan payload approval khusus.
- Jika tidak ada PO draft, action memakai PO pertama sebagai fallback default.
- Permission backend tetap mengikuti `purchases.manage`.

## Verifikasi

- `npm run build`
- `php artisan test`
