# Tahap 37 - Frontend POS Submit Actions

## Tujuan

Memperluas pola submit API dari action drawer ke action POS yang aman untuk dijalankan dari frontend.

## Frontend

- Action `Open Shift` tersambung ke `POST /api/cashier-shifts`.
- Action `Hold Cart` tersambung ke `POST /api/held-transactions`.
- Badge `API submit ready` sekarang mencakup:
  - New Customer
  - New Product
  - Open Shift
  - Hold Cart
- Setelah submit POS berhasil, POS store reload via `pos.loadFromApi()`.

## Payload Awal

`Open Shift`:

- `shift_number`
- `opening_cash`

`Hold Cart`:

- `hold_number`
- `payload.note`
- `payload.items`

## Catatan

Tahap ini belum menyambungkan `New Sale`, karena complete sale membutuhkan payload item, warehouse, shift, payment, tax, promotion, dan validasi yang lebih kompleks.
