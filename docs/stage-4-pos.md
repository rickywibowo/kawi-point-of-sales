# Tahap 4 - POS

## Scope

Tahap ini menyiapkan fondasi transaksi POS:

- Shift kasir
- Cash movement foundation
- Sale
- Sale items
- Sale item modifiers
- Sale payments
- Hold transaction
- Receipt/status foundation
- Void/refund status fields
- Idempotency key untuk offline sales queue

## Database Schema

- `cashier_shifts`
  - Scope: `business_id`, `branch_id`
  - Menyimpan buka/tutup shift, opening cash, expected cash, actual cash, dan selisih.
- `cash_movements`
  - Scope: `business_id`, `branch_id`, `cashier_shift_id`
  - Fondasi cash in/cash out.
- `sales`
  - Scope: `business_id`, `branch_id`
  - Menyimpan header transaksi, customer, cashier, shift, totals, status, UUID, dan idempotency key.
- `sale_items`
  - Scope: `business_id`, `branch_id`
  - Menyimpan produk, quantity, price, discount, tax, dan line total.
- `sale_item_modifiers`
  - Menyimpan modifier/topping yang dipilih pada item.
- `sale_payments`
  - Scope: `business_id`, `branch_id`
  - Mendukung `cash`, `card`, `transfer`, dan `qris`.
- `held_transactions`
  - Scope: `business_id`, `branch_id`
  - Menyimpan payload cart saat transaksi di-hold.

## API Endpoint

Semua endpoint memakai:

- `auth:sanctum`
- `tenant`
- Header `X-Business-Id`
- Header `X-Branch-Id`

Endpoint:

- `GET /api/pos`
  - Permission: `sales.create`
  - Mengembalikan produk POS, warehouse, hold transaction, dan transaksi hari ini.
- `POST /api/cashier-shifts`
  - Permission: `sales.create`
  - Membuka shift kasir.
- `POST /api/cashier-shifts/{shift}/close`
  - Permission: `sales.create`
  - Menutup shift kasir dan menghitung expected/actual/difference cash.
- `POST /api/sales`
  - Permission: `sales.create`
  - Membuat sale completed, item, payment, stock ledger sales consumption, dan update stock balance.
- `POST /api/held-transactions`
  - Permission: `sales.create`
  - Menyimpan transaksi hold.

## Business Rules

- Sale wajib memiliki shift kasir yang masih open.
- Sale wajib berada pada branch aktif.
- Sale wajib memiliki warehouse branch aktif untuk stock consumption.
- Payment total harus menutup grand total.
- `idempotency_key` mencegah transaksi offline tercatat dua kali.
- Produk stock-tracked otomatis mengurangi stock via `stock_ledgers`.
- `stock_balances` diperbarui setelah ledger sales consumption dibuat.

## Calculation

- `subtotal`: jumlah gross item termasuk modifier.
- `discount_total`: diskon item.
- `tax_total`: tax per item berdasarkan tax product.
- `service_charge_total`: nilai service dari request.
- `grand_total`: subtotal - discount + tax + service.
- `paid_total`: total payment.
- `change_total`: paid - grand total.

## Audit

Audit log dibuat untuk:

- `cashier_shift.opened`
- `cashier_shift.closed`
- `sale.held`
- `sale.completed`

## Seed Data

Seeder membuat:

- Shift kasir historis tertutup.
- Hold transaction demo.

## Test

Test Tahap 4 mencakup:

- Cashier membuka shift.
- Cashier hold transaction.
- Sale completed membuat item, payment, ledger, dan mengurangi balance.
- Idempotency key mencegah duplicate sale dan duplicate stock ledger.
- Cashier menutup shift.
