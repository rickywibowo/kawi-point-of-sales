# Stage 76 - Spatie RBAC and Context Selection

Status: complete.

## Ringkasan

Tahap ini memindahkan fondasi RBAC ke Spatie Laravel Permission dan menambahkan pemilihan business/branch sebagai context session. Dengan pola ini, user memilih business dan branch terlebih dulu, lalu form Product, Category, dan CRUD branch-level otomatis memakai context aktif.

## Perubahan

- Menambahkan dependency `spatie/laravel-permission`.
- Menambahkan config `config/permission.php` dengan model custom `App\Models\Role` dan `App\Models\Permission`.
- Model `Role` dan `Permission` sekarang extend model Spatie.
- Model `User` memakai trait `HasRoles` dari Spatie dengan relation role custom yang tetap menyimpan `business_id` dan `branch_id`.
- Migration kompatibilitas menambahkan `guard_name`, `role_has_permissions`, `model_has_roles`, dan `model_has_permissions`.
- Data RBAC lama dari `permission_role` dan `role_user` disalin ke tabel Spatie.
- Endpoint context ditambahkan:
  - `GET /api/auth/contexts`
  - `POST /api/auth/context`
- Response login dan `/api/auth/me` mengembalikan daftar context business/branch yang boleh dipilih user.
- Tenant resolver fallback ke `current_branch_id` user jika header branch tidak dikirim.
- Branch-scoped user ditolak jika mencoba memakai branch di luar role assignment.
- Halaman Filament `Administration > Context` ditambahkan di `/admin/context`.
- Form Filament branch-level kembali otomatis memakai branch context aktif, bukan memilih Branch berulang di setiap form.

## Aturan Akses Context

- Platform super admin dapat melihat dan memilih semua business/branch aktif.
- Business owner dapat memilih semua branch dalam business miliknya.
- Role business-level dapat memilih semua branch dalam business terkait.
- Role branch-level hanya dapat memilih branch yang diberikan di assignment role.

## Cara Pakai

1. Login ke dashboard/API.
2. Ambil pilihan context dari response login atau `GET /api/auth/contexts`.
3. Pilih context dengan `POST /api/auth/context`.
4. Untuk back office, buka `Administration > Context` atau `/admin/context`, lalu pilih business/branch aktif.
5. Setelah context tersimpan, buka Filament atau dashboard; Product, Category, Warehouse, dan dokumen branch-level otomatis memakai branch aktif.

## Verifikasi

```bash
php artisan migrate --force
php artisan route:list --path=admin/context
php artisan test
npm run build
```

Hasil:

- Migration Spatie sukses.
- Route `/admin/context` tersedia.
- Laravel test sukses: 84 tests, 476 assertions.
- Frontend build sukses.
