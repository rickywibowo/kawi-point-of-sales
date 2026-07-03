# Tahap 24 - Promotion & Voucher Engine

Status: complete.

## Tujuan

Tahap ini menambahkan fondasi promo/voucher agar POS dapat menerapkan kode diskon pada transaksi.

## Backend

- Tabel `promotions` dibuat.
- Kolom promo ditambahkan ke `sales`:
  - `promotion_id`
  - `promotion_code`
  - `promotion_discount_total`
- Model `Promotion` dibuat.
- Request validation `StorePromotionRequest` dibuat.
- Service `PromotionService` dibuat untuk create promo, validasi kode, hitung diskon, dan update usage.
- Endpoint `POST /api/promotions` dibuat dengan permission `sales.create`.
- POS index mengembalikan promo aktif.

## Promo Rules

- Promo mendukung tipe `percent` dan `fixed`.
- Promo dapat memakai minimum subtotal.
- Promo percent dapat dibatasi dengan maximum discount.
- Promo dapat dibatasi usage limit.
- Promo divalidasi berdasarkan status aktif, tanggal mulai, tanggal selesai, dan usage limit.
- Sale menyimpan kode promo dan total diskon promo.

## POS & Receipt

- Request sale menerima `promotion_code`.
- Promotion discount digabung ke `discount_total`.
- `grand_total` dihitung setelah promotion discount.
- Usage count promo bertambah setelah sale sukses.
- Receipt digital menampilkan `promotion_code` dan `promotion_discount_total`.

## Frontend

- Store POS menambahkan demo promotions.
- Dashboard POS menampilkan jumlah promo aktif dan promo pertama.

## Automated Test

- Cashier dapat create promotion.
- Sale dapat memakai promotion code.
- Usage count promo bertambah setelah sale sukses.
- Receipt menampilkan promotion code dan discount.
- Promo ditolak jika subtotal tidak memenuhi minimum.
