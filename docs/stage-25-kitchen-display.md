# Tahap 25 - Kitchen Display System Foundation

Status: complete.

## Tujuan

Tahap ini menambahkan fondasi Kitchen Display System (KDS) agar transaksi POS otomatis mengirim item ke dapur sebagai kitchen ticket.

## Backend

- Tabel `kitchen_tickets` dibuat.
- Tabel `kitchen_ticket_items` dibuat.
- Model `KitchenTicket` dan `KitchenTicketItem` dibuat.
- Service `KitchenService` dibuat untuk:
  - Membuat ticket otomatis dari sale.
  - Update status kitchen ticket.
  - Update status kitchen ticket item.
  - Sinkronisasi status ticket dari status item.
- Endpoint berikut dibuat dengan permission `sales.create`:
  - `GET /api/kitchen-tickets`
  - `PATCH /api/kitchen-tickets/{ticket}/status`
  - `PATCH /api/kitchen-ticket-items/{item}/status`
- POS index mengembalikan kitchen tickets aktif.

## KDS Flow

- Sale completed otomatis membuat kitchen ticket dengan nomor `KOT-{sale_number}`.
- Setiap sale item menjadi kitchen ticket item.
- Kitchen item mendukung status `pending`, `preparing`, `ready`, `served`, dan `cancelled`.
- Kitchen ticket mendukung status `open`, `preparing`, `ready`, `served`, dan `cancelled`.
- Saat ticket ditandai `served`, semua item aktif ikut menjadi `served`.
- Audit log dibuat untuk ticket created, ticket status updated, dan item status updated.

## Frontend

- Store POS menambahkan demo `kitchenTickets`.
- Dashboard POS menampilkan kitchen queue aktif.

## Automated Test

- Completed sale otomatis membuat kitchen ticket.
- Endpoint kitchen tickets mengembalikan ticket aktif.
- Kitchen dapat update status item.
- Kitchen dapat update status ticket menjadi served.
