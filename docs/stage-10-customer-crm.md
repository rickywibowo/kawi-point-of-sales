# Tahap 10 - Customer CRM Foundation

Status: complete.

## Tujuan

Tahap ini memperkuat modul pelanggan agar POS tidak hanya menyimpan `customer_id`, tetapi juga punya fondasi CRM sederhana:

- Lookup pelanggan untuk kasir.
- Pembuatan dan update profil pelanggan.
- Ringkasan transaksi pelanggan.
- Riwayat transaksi terakhir.
- Validasi tenant isolation saat pelanggan dipakai di transaksi POS.

## Backend

File utama:

- `app/Services/Customers/CustomerService.php`
- `app/Http/Controllers/Api/CustomerController.php`
- `app/Http/Requests/Customers/StoreCustomerRequest.php`
- `app/Http/Requests/Customers/UpdateCustomerRequest.php`

Endpoint:

```http
GET /api/customers
POST /api/customers
GET /api/customers/{customer}
PATCH /api/customers/{customer}
```

Semua endpoint customer berada di middleware:

- `auth:sanctum`
- `tenant`
- `permission:sales.create`

## Customer Profile

`GET /api/customers/{customer}` mengembalikan:

- Data customer.
- `transaction_count`.
- `lifetime_spend`.
- `average_order_value`.
- `last_purchase_at`.
- `receivable_balance`.
- `loyalty_points`.
- `recent_sales`.

## Tenant Isolation

Customer hanya bisa diakses jika `business_id` sama dengan business aktif dari header `X-Business-Id`.

Transaksi POS juga memvalidasi `customer_id`. Jika customer berasal dari business lain, API mengembalikan validation error `customer_id`.

## Frontend

Store demo:

- `resources/js/stores/customers.js`

Dashboard awal menampilkan panel Customer CRM berisi ringkasan member, loyalty point, jumlah transaksi, dan lifetime spend.

## Test

Automated test:

- `tests/Feature/Customers/CustomerTest.php`

Coverage:

- Create/search/update customer.
- Audit log customer.
- Customer profile dengan ringkasan transaksi.
- Tenant isolation untuk endpoint customer.
- POS menolak customer dari business lain.
