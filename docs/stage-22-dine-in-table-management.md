# Tahap 22 - Dine-in Table Management

Status: complete.

## Tujuan

Tahap ini menambahkan pengelolaan meja dine-in agar POS dapat memproses transaksi makan di tempat dengan konteks meja dan status operasional outlet.

## Backend

- Tabel `dining_tables` dibuat untuk data meja per business dan branch.
- Kolom `dining_table_id` ditambahkan ke tabel `sales`.
- Model `DiningTable` dibuat.
- Model `Sale` menambahkan relasi `diningTable`.
- Request validation untuk create table dan update status table dibuat.
- Service `DiningTableService` dibuat untuk create table dan update status.
- Endpoint berikut dibuat dengan permission `sales.create`:
  - `POST /api/dining-tables`
  - `PATCH /api/dining-tables/{table}/status`
- POS index mengembalikan daftar `dining_tables`.

## POS Flow

- Sale tipe `dine_in` wajib menyertakan `dining_table_id`.
- Meja harus berada di branch aktif.
- Meja hanya bisa dipakai jika status `available` atau `reserved`.
- Setelah sale dine-in selesai, status meja berubah menjadi `cleaning`.
- Receipt digital menampilkan informasi meja.

## Seeder & Frontend

- Seeder POS menambahkan meja demo `T-01`, `T-02`, dan `VIP-01`.
- Store POS menambahkan demo dining tables.
- Dashboard POS menampilkan jumlah meja ready dan status meja.

## Automated Test

- Cashier dapat create dining table dan update status.
- POS index mengembalikan dining tables.
- Dine-in sale wajib memakai meja.
- Dine-in sale menandai meja menjadi `cleaning`.
- Receipt menampilkan informasi meja.
