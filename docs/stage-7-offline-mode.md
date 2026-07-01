# Tahap 7 - Offline Mode

## Scope

Tahap ini menyiapkan MVP offline POS:

- IndexedDB helper untuk cache katalog dan queue sales.
- Pinia offline queue/status.
- Backend offline sales sync endpoint.
- Idempotency key untuk mencegah duplicate sale.
- Conflict table untuk payload yang gagal divalidasi.
- Conflict review endpoint.

## Database Schema

- `offline_sync_batches`
  - Scope: `business_id`, `branch_id`
  - Menyimpan batch sync dari kasir/device.
- `offline_sync_conflicts`
  - Scope: `business_id`, `branch_id`
  - Menyimpan payload offline yang gagal divalidasi backend.

## API Endpoint

Semua endpoint memakai `auth:sanctum`, `tenant`, dan permission `sales.create`.

- `POST /api/offline/sales/sync`
  - Body berisi `batch_key` dan daftar sales envelope.
  - Masing-masing sale wajib punya `client_uuid` dan `payload`.
- `GET /api/offline/conflicts`
  - Mengembalikan daftar conflict untuk review.

## Sync Rules

- `idempotency_key` diambil dari payload, `offline_uuid`, atau `client_uuid`.
- Jika sale dengan idempotency key sudah ada, payload dianggap synced tanpa membuat sale baru.
- Jika validasi/business rule gagal, payload masuk ke `offline_sync_conflicts`.
- Batch menyimpan `received_count`, `synced_count`, dan `conflict_count`.
- Stock final tetap dihitung dan divalidasi backend melalui `PosService`.

## Frontend

- `resources/js/services/offlineDb.js`
  - Native IndexedDB helper.
  - Store: `salesQueue`, `catalogCache`.
- `resources/js/stores/offline.js`
  - Status online/offline.
  - Queue count.
  - Conflict count.
  - Helper queue sale dan mark synced.

## Test

Test Tahap 7 mencakup:

- Offline sync sale sukses membuat sale.
- Replay batch/idempotency tidak membuat duplicate sale.
- Payload invalid masuk conflict.
- Conflict endpoint mengembalikan conflict aktif.
