# Tahap 41 - Frontend Customer & User Submit Actions

Status: complete.

## Ringkasan

Tahap ini menghubungkan action drawer modul Customer CRM dan User Access ke endpoint API yang sudah tersedia.

## Perubahan

- Store customer frontend menyimpan `customer.id` dari paginator API.
- Store user access frontend menyimpan `user.id`, `role.id`, dan `branch.id`.
- Action `Loyalty` submit ke `POST /api/customers/{customer}/loyalty-transactions`.
- Action `Invite User` submit ke `POST /api/user-access/users`.
- Action `Assign Role` submit ke `POST /api/user-access/users/{user}/roles`.
- Customer dan user access store reload setelah submit berhasil.

## Catatan Operasional

- `Loyalty` memakai customer pertama dari data API sebagai default.
- `Invite User` dapat langsung membawa role dan branch awal bila tersedia.
- `Assign Role` membutuhkan user ID, role ID, dan branch ID dari directory user access.
- Action `Segment` dan `Audit` masih draft-only karena belum ada endpoint POST khusus untuk workflow tersebut.

## Verifikasi

- `npm run build`
- `php artisan test`
