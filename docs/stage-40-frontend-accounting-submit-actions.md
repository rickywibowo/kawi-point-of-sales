# Tahap 40 - Frontend Accounting Submit Actions

Status: complete.

## Ringkasan

Tahap ini menghubungkan action drawer modul Accounting ke endpoint API accounting dan rekonsiliasi pembayaran.

## Perubahan

- Store accounting frontend menyimpan account ID, payment settlement ID, dan provider import ID dari response API.
- Action `New Journal` submit ke `POST /api/journal-entries`.
- Action `Settlement` submit ke `POST /api/payment-settlements`.
- Action `Import Provider` submit ke `POST /api/payment-provider-imports`.
- Payload manual journal dibuat seimbang dengan dua line debit/kredit.
- Accounting store reload setelah submit accounting berhasil.

## Catatan Operasional

- `New Journal` memakai akun kas `1100` dan akun modal `3100` sebagai default bila tersedia dari API.
- `Settlement` membutuhkan sale payment yang belum pernah disettlement pada method dan periode yang dipilih.
- `Import Provider` membutuhkan settlement ID dengan method `card`, `transfer`, atau `qris`.

## Verifikasi

- `npm run build`
- `php artisan test`
