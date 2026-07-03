# Tahap 27 - Payment Settlement & Reconciliation

## Tujuan

Membuat fondasi settlement pembayaran agar finance dapat mencocokkan total payment POS per metode dengan nominal yang benar-benar diterima dari cash drawer, QRIS, transfer, atau kartu.

## Backend

- Tabel `payment_settlements` menyimpan batch settlement per business, branch, method, periode, expected amount, reported amount, variance, status, dan user posting.
- Tabel `payment_settlement_items` mengunci setiap `sale_payment_id` ke satu settlement agar payment tidak direkonsiliasi dua kali.
- Model `PaymentSettlement` dan `PaymentSettlementItem` dibuat.
- Relasi `SalePayment::settlementItem()` ditambahkan untuk filter unsettled payment.
- Service `PaymentSettlementService` menghitung expected amount dari completed sale payment dalam periode aktif.
- Endpoint:
  - `GET /api/payment-settlements`
  - `POST /api/payment-settlements`
- Semua endpoint memakai permission `accounting.manage`.
- Audit log `payment_settlement.posted` dicatat saat settlement diposting.

## Business Rules

- Nomor settlement unik per business.
- Method yang didukung: `cash`, `card`, `transfer`, `qris`.
- Periode `date_to` tidak boleh lebih awal dari `date_from`.
- Hanya payment dari sale `completed` yang masuk settlement.
- Payment yang sudah punya settlement item tidak dapat disettlement ulang.
- Variance dihitung dari `reported_amount - expected_amount`.

## Reporting & UI

- `ReportService` menambahkan ringkasan `payment_settlements` berisi jumlah settlement, expected amount, reported amount, variance, dan breakdown per method.
- Pinia accounting store menambahkan data demo settlement dan total variance.
- Dashboard Accounting menampilkan settlement terakhir dan variance.

## Test Coverage

- Posting settlement dari sale payment yang belum disettlement.
- Validasi expected/reported/variance amount.
- Proteksi double settlement untuk payment yang sama.
- Audit log settlement.
