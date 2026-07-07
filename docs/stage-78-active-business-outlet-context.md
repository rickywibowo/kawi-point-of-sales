# Stage 78 - Active Business and Outlet Context

Status: complete.

## Ringkasan

Tahap ini menambahkan context API berbasis session untuk active business dan active outlet. Setelah login, user dapat memilih business/outlet yang memang bisa diakses melalui `business_user` dan `outlet_user`. Modul berikutnya dapat memakai context aktif ini tanpa menampilkan selector `business_id` atau `outlet_id` pada form master data normal.

## Endpoint

- `GET /api/me/context-options`
- `POST /api/me/active-context`
- `GET /api/me/active-context`
- `DELETE /api/me/active-context`

Payload `POST /api/me/active-context`:

```json
{
  "business_id": 1,
  "outlet_id": 1
}
```

## Middleware

- `active.business`: wajib ada active business di session dan user masih punya akses ke business tersebut.
- `active.outlet`: wajib ada active business dan active outlet di session, outlet harus milik business aktif, dan user masih punya akses ke outlet tersebut.

Jika context hilang, middleware mengembalikan HTTP `428`. Jika context tidak valid atau tidak accessible, middleware mengembalikan HTTP `403`.

## Helper

Service `App\Services\Tenancy\ActiveContext` menyediakan:

- `businessId()`
- `outletId()`
- `business()`
- `outlet()`
- `hasBusiness()`
- `hasOutlet()`

## Future Module Guidance

- Business-scoped master data routes harus memakai middleware `active.business`.
- Outlet-scoped operational routes harus memakai middleware `active.outlet`.
- Form normal untuk categories, products, ingredients, recipes, payment methods, sales, dan inventory tidak perlu menampilkan `business_id` atau `outlet_id`.
- Di codebase saat ini, outlet direpresentasikan oleh model/table `branches`; response API tetap memakai istilah `outlet`.

## Verifikasi

```bash
php artisan test --filter=ActiveContextTest
php artisan test
```
