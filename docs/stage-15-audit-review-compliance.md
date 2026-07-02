# Tahap 15 - Audit Review & Compliance

Status: complete.

## Tujuan

Tahap ini membuat audit log bisa ditinjau secara operasional:

- Review audit event per business aktif.
- Filter berdasarkan action, entity, user, dan tanggal.
- Summary action terbanyak.
- Security events untuk aktivitas sensitif.
- Tenant isolation agar audit business lain tidak bocor.

## Endpoint

```http
GET /api/audit-logs
```

Query filter:

- `action`
- `entity_type`
- `user_id`
- `date_from`
- `date_to`
- `per_page`

Middleware:

- `auth:sanctum`
- `tenant`
- `permission:users.manage`

## Response

Response berisi:

- `summary.total_events`
- `summary.unique_users`
- `summary.actions`
- `summary.recent_security_events`
- `audit_logs`

## Security Events

Event sensitif yang ditonjolkan:

- `user.invited`
- `role.assigned`
- `sale.voided`
- `sale.refunded`
- `journal.posted`

## Frontend

Store demo:

- `resources/js/stores/audit.js`

Dashboard awal menampilkan panel Audit Review:

- Total events.
- Unique users.
- Top action.
- Jumlah security events.

## Test

Automated test:

- `tests/Feature/Audit/AuditLogTest.php`

Coverage:

- Owner bisa review audit log dan summary.
- Filter action.
- Tenant isolation untuk audit log business lain.
