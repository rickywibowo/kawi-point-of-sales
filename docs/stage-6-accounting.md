# Tahap 6 - Accounting

## Scope

Tahap ini menyiapkan fondasi double-entry accounting:

- Accounting periods
- Chart of accounts
- Journal entries
- Journal lines
- General ledger foundation
- Trial balance
- Profit and loss foundation
- Automatic journals dari POS sale dan goods receipt

## Chart of Accounts Awal

- `1100` Kas
- `1200` Bank
- `1300` Persediaan
- `1400` Pajak Masukan
- `1500` Piutang Usaha
- `2100` Utang Usaha
- `2200` Pajak Keluaran
- `3100` Modal Pemilik
- `4100` Penjualan
- `5100` Harga Pokok Penjualan
- `6100` Beban Operasional

## API Endpoint

Semua endpoint memakai `auth:sanctum`, `tenant`, dan permission `accounting.manage`.

- `GET /api/accounting`
  - Mengembalikan accounts, journal entries, trial balance, dan profit/loss.
- `POST /api/journal-entries`
  - Membuat posted journal manual yang wajib balanced.

## Auto Journal

### POS Sale

- Debit Kas
- Kredit Penjualan
- Kredit Pajak Keluaran jika ada
- Debit HPP jika ada stock-tracked item
- Kredit Persediaan

### Goods Receipt

- Debit Persediaan
- Debit Pajak Masukan jika ada
- Kredit Utang Usaha

## Guardrails

- Journal wajib balanced.
- Account wajib berasal dari business aktif.
- Source journal menyimpan `source_type` dan `source_id`.
- Posting otomatis idempotent per source document.

## Test

Test Tahap 6 mencakup:

- Manual journal balanced berhasil.
- Manual journal unbalanced ditolak.
- POS sale membuat auto journal.
- Goods receipt membuat auto journal.
- Accounting endpoint mengembalikan trial balance dan profit/loss.
