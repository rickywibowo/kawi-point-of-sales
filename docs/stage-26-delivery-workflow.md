# Tahap 26 - Delivery Workflow Foundation

Status: complete.

## Tujuan

Tahap ini menambahkan fondasi delivery workflow untuk transaksi POS tipe `delivery`.

## Backend

- Tabel `delivery_orders` dibuat.
- Kolom `delivery_fee_total` ditambahkan ke `sales`.
- Model `DeliveryOrder` dibuat.
- Service `DeliveryService` dibuat untuk:
  - Membuat delivery order otomatis dari sale delivery.
  - Update status delivery order.
  - Assign courier.
- Endpoint berikut dibuat dengan permission `sales.create`:
  - `GET /api/delivery-orders`
  - `PATCH /api/delivery-orders/{delivery}/status`
- POS index mengembalikan delivery orders aktif.

## Delivery Flow

- Sale tipe `delivery` wajib menyertakan recipient dan address.
- Delivery fee masuk ke `grand_total` sale.
- Sale delivery otomatis membuat delivery order `DO-{sale_number}`.
- Delivery order mendukung status:
  - `pending`
  - `assigned`
  - `picked_up`
  - `delivered`
  - `cancelled`
- Assign courier menyimpan nama dan nomor kurir.
- Delivered order tidak bisa mundur ke status lain.
- Receipt digital menampilkan detail delivery dan delivery fee.

## Frontend

- Store POS menambahkan demo `deliveryOrders`.
- Dashboard POS menampilkan delivery queue aktif.

## Automated Test

- Sale delivery membuat delivery order.
- Delivery fee masuk ke grand total.
- Endpoint delivery orders mengembalikan order aktif.
- Status delivery dapat diubah menjadi assigned.
- Receipt menampilkan detail delivery.
- Sale delivery tanpa alamat ditolak.
