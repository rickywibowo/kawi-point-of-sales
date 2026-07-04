# Tahap 57 - Frontend Customer Update Action

Status: complete.

## Ringkasan

Tahap ini menambahkan action frontend untuk update data customer dari modul Customers.

## Perubahan

- Action `Update Customer` ditambahkan ke modul Customers.
- `Update Customer` submit ke `PATCH /api/customers/{customer}`.
- Payload update memakai name, phone, email, notes, dan status active.
- Customer store reload setelah update berhasil.

## Catatan Operasional

- Action memakai customer pertama dari API sebagai default.
- Field email dan notes bersifat opsional.
- Backend tetap memvalidasi customer berada di business aktif.

## Verifikasi

- `npm run build`
- `php artisan test`
