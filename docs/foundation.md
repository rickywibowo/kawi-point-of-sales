# KAWI POS Foundation

## Struktur Folder

- `app/Http/Controllers/Api`: endpoint API JSON.
- `app/Http/Middleware`: tenant isolation dan permission gate.
- `app/Http/Requests`: request validation.
- `app/Models`: entity Eloquent.
- `app/Models/Concerns`: trait reusable untuk UUID dan tenant scope.
- `app/Policies`: policy access per entity.
- `app/Services`: service layer untuk audit dan tenancy.
- `database/migrations`: database schema.
- `database/seeders`: role, permission, business, branch, dan user awal.
- `resources/js`: Vue 3 + Pinia frontend.
- `routes/api.php`: route API Sanctum.

## Entity Utama

- `User`: akun pengguna aplikasi.
- `Business`: tenant utama, mewakili satu bisnis.
- `Branch`: cabang atau outlet dalam business.
- `Role`: role scoped platform, business, atau branch.
- `Permission`: permission global yang ditempel ke role.
- `AuditLog`: jejak aktivitas penting.
- `PersonalAccessToken`: token Laravel Sanctum.

## Database Schema Awal

- `businesses`: `id`, `uuid`, `name`, `legal_name`, `tax_number`, `currency`, `timezone`, `is_active`.
- `branches`: `business_id`, `uuid`, `name`, `code`, `address`, `phone`, `is_active`.
- `business_user`: membership user ke business, termasuk flag owner.
- `users`: ditambah `current_business_id` dan `current_branch_id`.
- `permissions`: daftar permission seperti `sales.create` dan `reports.view`.
- `roles`: role dengan optional `business_id` dan `branch_id`.
- `permission_role`: mapping permission ke role.
- `role_user`: assignment role ke user dalam scope business/cabang.
- `audit_logs`: user, business, branch, action, entity, nilai sebelum/sesudah, IP, timestamp.
- `personal_access_tokens`: token API Sanctum.

## Daftar Migration

- `2026_07_01_000000_create_sanctum_personal_access_tokens_table.php`
- `2026_07_01_000001_create_tenancy_tables.php`
- `2026_07_01_000002_create_rbac_tables.php`
- `2026_07_01_000003_create_audit_logs_table.php`

## Role dan Permission

Role awal:

- Platform Super Admin
- Business Owner
- Branch Manager
- Cashier
- Inventory Staff
- Purchasing
- Accountant
- Viewer

Permission awal:

- `sales.create`
- `sales.discount`
- `sales.void`
- `sales.refund`
- `inventory.view`
- `inventory.adjust`
- `purchases.manage`
- `reports.view`
- `accounting.manage`
- `users.manage`

## Alur Authentication

1. Frontend mengirim `POST /api/auth/login` dengan email, password, dan optional `device_name`.
2. Backend validasi kredensial.
3. Backend menerbitkan Sanctum personal access token.
4. Frontend menyimpan token dan mengirim header `Authorization: Bearer <token>`.
5. Endpoint tenant wajib menerima `X-Business-Id` dan optional `X-Branch-Id`.
6. `GET /api/auth/me` mengembalikan user, business, dan branch aktif.
7. `POST /api/auth/logout` menghapus token aktif.

## Strategi Tenant Isolation

- Semua endpoint operasional wajib memakai middleware `auth:sanctum` dan `tenant`.
- Middleware `tenant` memastikan user adalah anggota business yang diminta.
- Jika `X-Branch-Id` dikirim, branch harus berada di business yang sama.
- Permission dicek dengan middleware `permission:<name>` menggunakan scope business/cabang.
- Model operasional tahap berikutnya harus memakai `business_id`, dan `branch_id` bila relevan.
- Trait `BelongsToTenant` menyediakan `scopeForTenant()` untuk query terisolasi.
- Policy Laravel dipakai untuk akses entity seperti `Business` dan `Branch`.

## Urutan Implementasi Tahap 1

1. Pasang Sanctum dan Pinia.
2. Buat schema tenancy, RBAC, audit, dan token.
3. Buat model dan relationship.
4. Buat service layer tenancy dan audit.
5. Buat request validation auth.
6. Buat middleware tenant dan permission.
7. Buat endpoint login, me, logout, dan sample protected permission.
8. Buat seed data role, permission, business, branch, dan owner.
9. Buat Pinia store awal untuk context POS.
10. Buat automated test untuk auth, tenant isolation, permission, dan audit.
