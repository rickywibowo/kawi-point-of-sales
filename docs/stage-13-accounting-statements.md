# Tahap 13 - Accounting Statements

Status: complete.

## Tujuan

Tahap ini melengkapi laporan accounting dasar yang dibutuhkan POS:

- General ledger.
- Balance sheet.
- Cash flow.
- Integrasi statement ke endpoint accounting dan report dashboard.

## Backend

`AccountingService` sekarang menyediakan:

- `generalLedger()`
- `balanceSheet()`
- `cashFlow()`

Endpoint existing:

```http
GET /api/accounting
```

Response accounting sekarang berisi:

- `trial_balance`
- `profit_and_loss`
- `general_ledger`
- `balance_sheet`
- `cash_flow`

## General Ledger

General ledger mengelompokkan journal line per account dan menghitung:

- Debit total.
- Credit total.
- Running balance.
- Ending balance.

Filter tanggal didukung lewat query:

```http
GET /api/accounting?date_from=2026-07-01&date_to=2026-07-31
```

## Balance Sheet

Balance sheet disusun dari trial balance:

- Assets.
- Liabilities.
- Equity.
- Net profit periode berjalan masuk sebagai equity.
- Flag `is_balanced` menandai neraca seimbang.

## Cash Flow

Cash flow menggunakan akun dengan `is_cash = true`.

Output:

- Cash inflows.
- Cash outflows.
- Net cash flow.
- Ending cash balance.

## Reports

`GET /api/reports` sekarang memasukkan:

- `accounting.balance_sheet`
- `accounting.cash_flow`

## Frontend

Store accounting dan reports diperbarui untuk menampilkan ringkasan:

- Status statement.
- Total asset neraca.
- Net cash flow.
- Ending cash balance.

## Test

Automated test diperluas di:

- `tests/Feature/Accounting/AccountingTest.php`

Coverage:

- Accounting endpoint mengembalikan statement baru.
- General ledger menghitung ending balance.
- Balance sheet seimbang.
- Cash flow menghitung net cash flow dan ending cash balance.
