# Tahap 18 - Receipt & Transaction History

Status: complete.

## Tujuan

Tahap ini menambahkan fondasi struk digital untuk transaksi POS:

- Receipt payload dari data sale.
- Item, modifier, payment, dan totals.
- Informasi business dan branch.
- QR payload untuk struk digital.
- Tenant dan branch isolation.

## Endpoint

```http
GET /api/sales/{sale}/receipt
```

Middleware:

- `auth:sanctum`
- `tenant`
- `permission:sales.create`

## Receipt Payload

Response berisi:

- `business`
- `branch`
- `sale`
- `items`
- `payments`
- `totals`
- `digital`

`digital.qr_payload` memakai format:

```text
KAWI-POS:{sale_uuid}
```

## Security

Receipt hanya bisa dibuka jika:

- Sale berada di business aktif.
- Jika header branch aktif dikirim, sale harus berasal dari branch tersebut.

## Frontend

Store POS dan dashboard awal menampilkan jumlah struk digital demo.

## Test

Automated test:

- `tests/Feature/Pos/PosTest.php`

Coverage:

- Cashier bisa melihat receipt digital.
- Receipt memuat business, branch, sale, item, payment, totals, dan QR payload.
- Receipt menolak sale di luar branch aktif.
