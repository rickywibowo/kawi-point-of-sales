# Stage 79 - Filament Active Business and Outlet Context

Status: complete.

## Ringkasan

Tahap ini menjadikan Filament sebagai UI utama untuk pemilihan active business dan active outlet. Vue/Nuxt frontend development dipause untuk sementara; pengembangan berikutnya difokuskan ke Laravel + Filament.

## Perubahan

- Halaman Filament `ManageActiveContext` ditambahkan di menu `Administration`.
- Label navigasi: `Active Business / Outlet`.
- User hanya dapat memilih business dari `business_user`.
- User hanya dapat memilih outlet dari `outlet_user`.
- Outlet memakai model `Branch` karena di codebase saat ini outlet direpresentasikan oleh `branches`.
- Outlet dropdown difilter berdasarkan business yang dipilih.
- Context disimpan di session:
  - `active_business_id`
  - `active_outlet_id`
- Middleware Filament `EnsureFilamentActiveContext` ditambahkan.
- Jika user masuk Filament tanpa context, user diarahkan ke halaman `ManageActiveContext`.
- Jika context invalid atau akses user dicabut, session context dibersihkan dan user diarahkan kembali ke halaman context.
- Helper `ActiveContext` sekarang memiliki method `clear()`.
- Filament `TenantContext` sekarang membaca session active context sebelum fallback ke current user context.
- Resource `Businesses` dibatasi ke business yang accessible oleh user.

## Resource Guidance

Business-scoped resources:

- categories
- units
- products
- ingredients
- product_recipes
- payment_methods

Rules:

- Table query filter by `active_business_id`.
- Create form auto-fill `business_id` dari `ActiveContext`.
- Edit form tidak boleh mengubah `business_id`.
- Normal forms tidak menampilkan `business_id`.

Outlet-scoped resources:

- sales
- stock_movements
- stock_balances
- cash_sessions

Rules:

- Table query filter by `active_business_id` dan `active_outlet_id`.
- Create form auto-fill `business_id` dan `outlet_id` dari `ActiveContext`.
- Normal forms tidak menampilkan `business_id` atau `outlet_id`.

## Verifikasi

```bash
php artisan test --filter=FilamentActiveContextTest
php artisan test
php artisan route:list --path=admin
```
