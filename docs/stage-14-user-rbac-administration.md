# Tahap 14 - User & RBAC Administration

Status: complete.

## Tujuan

Tahap ini menambahkan fondasi administrasi user dan role agar permission `users.manage` bisa dipakai secara operasional:

- Melihat directory user, role, permission, dan branch.
- Mengundang user ke business aktif.
- Assign role ke user dengan scope business/cabang.
- Audit log untuk invite user dan assign role.

## Endpoint

```http
GET /api/user-access
POST /api/user-access/users
POST /api/user-access/users/{user}/roles
```

Semua endpoint memakai middleware:

- `auth:sanctum`
- `tenant`
- `permission:users.manage`

## Tenant Isolation

Validasi yang dilakukan:

- User harus menjadi member business aktif sebelum role assignment.
- Role harus global atau milik business aktif.
- Branch assignment harus milik business aktif.
- Role dari business lain ditolak.
- Branch dari business lain ditolak.

## Service Layer

File utama:

- `app/Services/Administration/UserAccessService.php`
- `app/Http/Controllers/Api/UserAccessController.php`
- `app/Http/Requests/Administration/InviteUserRequest.php`
- `app/Http/Requests/Administration/AssignRoleRequest.php`

## Audit

Audit action:

- `user.invited`
- `role.assigned`

## Frontend

Current baseline:

- API remains available for user access administration.
- Vue/Nuxt user access UI is paused.

Dashboard awal menampilkan panel User Access:

- Jumlah user.
- Jumlah role.
- Jumlah permission.

## Test

Automated test:

- `tests/Feature/Administration/UserAccessTest.php`

Coverage:

- Directory user access.
- Invite user dengan role awal.
- Assign role ke existing user.
- Reject branch dari business lain.
- Reject role dari business lain.
