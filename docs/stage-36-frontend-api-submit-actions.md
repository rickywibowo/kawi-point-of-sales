# Tahap 36 - Frontend API Submit Actions

## Tujuan

Mulai menyambungkan action drawer frontend ke endpoint API nyata dengan scope kecil dan aman.

## Frontend

- `App.vue` mengimpor `apiPost`.
- Action `New Customer` tersambung ke `POST /api/customers`.
- Action `New Product` tersambung ke `POST /api/products`.
- Setelah submit berhasil:
  - customer store reload via `customers.loadFromApi()`
  - master data store reload via `masterData.loadFromApi()`
- Drawer menampilkan badge:
  - `API submit ready`
  - `Draft only`
- Action lain tetap menggunakan mode draft lokal.

## Payload Awal

`New Customer`:

- `name`
- `phone`
- `is_active`

`New Product`:

- `name`
- `type`
- `base_price`
- `cost_price`
- `track_stock`
- `is_active`

## Catatan

Tahap ini membuktikan pola submit API dari action drawer. Tahap berikutnya dapat memperluas submit untuk POS, inventory, purchasing, accounting, dan user access dengan payload yang lebih lengkap.
