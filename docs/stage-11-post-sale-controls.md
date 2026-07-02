# Tahap 11 - Post-Sale Controls

Status: complete.

## Tujuan

Tahap ini menambahkan kontrol operasional setelah transaksi POS:

- Cash in dan cash out pada shift kasir.
- Void transaksi.
- Refund transaksi.
- Stock ledger pembalik untuk void/refund.
- Audit log untuk semua aksi penting.

## Endpoint

```http
POST /api/cashier-shifts/{shift}/cash-movements
POST /api/sales/{sale}/void
POST /api/sales/{sale}/refund
```

Permission:

- `sales.create` untuk cash movement.
- `sales.void` untuk void sale.
- `sales.refund` untuk refund sale.

## Cash Movement

Cash movement hanya bisa dilakukan pada shift yang masih `open`.

Tipe:

- `cash_in`
- `cash_out`

Saat shift ditutup, expected cash dihitung dari:

```text
opening cash + cash sales + cash in - cash out
```

## Void dan Refund

Void/refund hanya bisa dilakukan pada sale dengan status `completed`.

Saat void/refund:

- Status sale berubah menjadi `voided` atau `refunded`.
- Timestamp dan user operator disimpan.
- Reason ditambahkan ke notes sale.
- Stock consumption dibalik dengan stock ledger baru.

Movement type:

- `sales_void`
- `sales_refund`

## Audit

Audit action:

- `cash_movement.created`
- `sale.voided`
- `sale.refunded`

## Frontend

Dashboard awal menampilkan panel Post-Sale Controls:

- Net cash movement.
- Void hari ini.
- Refund hari ini.

## Test

Automated test ditambahkan di:

- `tests/Feature/Pos/PosTest.php`

Coverage:

- Cash movement memengaruhi expected cash saat close shift.
- Void sale mengembalikan stock balance.
- Refund sale membuat stock ledger pembalik.
- Sale yang sudah refund/void tidak bisa diubah status kedua kali.
