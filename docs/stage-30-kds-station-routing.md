# Tahap 30 - KDS Station Routing & Slip Payload

## Tujuan

Meningkatkan Kitchen Display System dari antrean umum menjadi antrean berbasis station agar dapur panas, bar, pastry, atau station lain bisa menerima item yang relevan.

## Backend

- Tabel `kitchen_stations` dibuat untuk master station per business dan branch.
- Kolom `products.kitchen_station_id` ditambahkan untuk routing default produk.
- Kolom routing ditambahkan ke `kitchen_ticket_items`:
  - `kitchen_station_id`
  - `station_name`
  - `course`
  - `station_sequence`
- Model `KitchenStation` dibuat.
- Relasi `Product::kitchenStation()` dan `KitchenTicketItem::kitchenStation()` dibuat.
- `KitchenService::createTicketForSale()` mengisi station item dari produk.
- `KitchenService::slipPayload()` mengembalikan payload print slip yang grouped by station.

## API

- `POST /api/kitchen-stations`
- `GET /api/kitchen-tickets/{ticket}/slip`
- `GET /api/kitchen-tickets` sekarang mengembalikan daftar `kitchen_stations`.
- `GET /api/pos` sekarang mengembalikan `kitchen_stations`.

Semua endpoint memakai permission `sales.create`.

## UI

- Pinia POS store menambahkan demo `kitchenStations`.
- Dashboard POS menampilkan station aktif dan station ticket pertama.

## Test Coverage

- Create kitchen station via API.
- Produk diarahkan ke kitchen station.
- Completed sale membuat KOT item dengan station yang benar.
- Kitchen ticket index mengembalikan station.
- Slip payload grouped by station.
