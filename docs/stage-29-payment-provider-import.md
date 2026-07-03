# Tahap 29 - Payment Provider Reconciliation Import

## Tujuan

Menambahkan fondasi import settlement provider pembayaran seperti QRIS, bank transfer, atau kartu agar finance dapat mencocokkan laporan provider dengan settlement internal POS.

## Backend

- Tabel `payment_provider_imports` dibuat untuk header import provider.
- Tabel `payment_provider_import_rows` dibuat untuk detail baris import.
- Model `PaymentProviderImport` dan `PaymentProviderImportRow` dibuat.
- `PaymentSettlement` memiliki relasi `providerImports`.
- Service `PaymentProviderImportService` dibuat untuk parsing dan matching import.
- Import bisa dikirim sebagai:
  - `csv_content`
  - `rows`
- Header CSV minimal:
  - `reference`
  - `amount`
- Header opsional:
  - `fee_amount`
  - `settled_at`

## Matching Rules

- Import wajib menempel ke `payment_settlement_id`.
- Method import harus sama dengan method settlement.
- Matching dilakukan memakai `sale_payments.reference`.
- Jika reference ditemukan dan amount sama, status row menjadi `matched`.
- Jika reference ditemukan tetapi amount berbeda, status row menjadi `amount_mismatch`.
- Jika reference tidak ditemukan di settlement, status row menjadi `unmatched`.
- Header import menyimpan `matched_count`, `unmatched_count`, `gross_amount`, `fee_amount`, `received_amount`, dan `variance_to_settlement`.
- `variance_to_settlement` dihitung dari `received_amount - payment_settlements.reported_amount`.

## API

- `GET /api/payment-provider-imports`
- `POST /api/payment-provider-imports`

Semua endpoint memakai permission `accounting.manage`.

## UI

- Pinia accounting store menambahkan demo provider imports.
- Dashboard Accounting menampilkan import provider terakhir dan jumlah unmatched review.

## Test Coverage

- Import CSV provider untuk settlement QRIS.
- Row matched berdasarkan reference dan amount.
- Row unmatched saat reference tidak ada di settlement.
- Summary gross, fee, received, dan variance.
- Audit log `payment_provider_import.created`.
