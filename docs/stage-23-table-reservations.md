# Tahap 23 - Table Reservations

Status: complete.

## Tujuan

Tahap ini menambahkan workflow reservasi meja untuk melengkapi dine-in table management.

## Backend

- Tabel `table_reservations` dibuat untuk menyimpan booking meja per business dan branch.
- Model `TableReservation` dibuat.
- Model `DiningTable` menambahkan relasi `reservations`.
- Request validation `StoreTableReservationRequest` dibuat.
- Endpoint berikut dibuat dengan permission `sales.create`:
  - `POST /api/dining-tables/{table}/reservations`
  - `PATCH /api/table-reservations/{reservation}/cancel`
  - `PATCH /api/table-reservations/{reservation}/seat`
- POS index mengembalikan `table_reservations` aktif untuk hari berjalan.

## Reservation Flow

- Reservasi hanya bisa dibuat untuk meja branch aktif.
- Party size tidak boleh melebihi kapasitas meja.
- Reservasi aktif dalam window 2 jam tidak boleh overlap di meja yang sama.
- Create reservation mengubah status meja menjadi `reserved`.
- Cancel reservation mengubah status reservation menjadi `cancelled` dan melepas meja jika tidak ada reservasi aktif lain.
- Seat reservation mengubah status reservation menjadi `seated` dan meja menjadi `occupied`.
- Audit log dibuat untuk created, cancelled, dan seated.

## Frontend

- Store POS menambahkan demo `tableReservations`.
- Dashboard POS menampilkan jumlah reservasi aktif dan reservasi berikutnya.

## Automated Test

- Cashier dapat membuat dan seat reservation.
- Cashier dapat cancel reservation dan meja kembali available.
- Reservasi overlap ditolak.
