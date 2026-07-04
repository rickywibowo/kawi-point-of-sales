# Tahap 53 - Frontend Accounting Operational Expense Action

Status: complete.

## Ringkasan

Tahap ini menambahkan action frontend untuk posting biaya operasional dari modul Accounting.

## Perubahan

- Action `Operational Expense` ditambahkan ke modul Accounting.
- `Operational Expense` submit ke `POST /api/operational-expenses`.
- Payload expense memakai expense account, cash account, category, payee, description, amount, payment method, dan reference.
- Accounting store reload setelah expense berhasil diposting.

## Catatan Operasional

- Action memakai akun bertipe `expense` pertama sebagai default.
- Cash account memakai akun kas `1100` sebagai default.
- Nomor expense otomatis dibuat jika field `expense_number` kosong.

## Verifikasi

- `npm run build`
- `php artisan test`
