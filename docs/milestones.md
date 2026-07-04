# KAWI POS Milestones

## Milestone 1 - Core Platform

Status: complete.

- Laravel, Vue, Vite, Sanctum, tenant context, RBAC, dan audit log dasar.
- Multi-business dan multi-branch siap.
- Health check dan deployment readiness tersedia.
- Frontend API client foundation, login/session UX, module navigation shell, module table/action foundation, action drawer foundation, dan submit API awal untuk master data/customer/POS tersedia.

Tahap terkait: 1, 9, 14, 15, 31, 32, 33, 34, 35, 36, 37.

## Milestone 2 - Master Data & Inventory

Status: complete.

- Master produk, kategori, supplier, customer, tax, UOM, modifier, dan harga per cabang.
- Recipe, warehouse, stock ledger, stock balance, adjustment, transfer, opname, dan production order.
- Purchase return dan production posting memengaruhi stok.
- Frontend action drawer inventory tersambung ke API stock opname, stock transfer, dan production order.

Tahap terkait: 2, 3, 12, 16, 17, 38.

## Milestone 3 - POS Operations

Status: complete.

- Shift kasir, cash movement, sale, payment, held transaction, void, refund, receipt digital, dan offline sync.
- Dine-in table management, reservation, promotion/voucher, KDS foundation, KDS station routing, delivery workflow, dan cash drawer audit.
- Frontend action drawer POS tersambung ke API sale completion.
- Frontend action drawer POS tersambung ke API cash movement dan close shift.
- Frontend action drawer POS tersambung ke API promo, dining table, dan kitchen station setup.
- Frontend action drawer POS tersambung ke API table status dan reservation.
- Frontend action drawer POS tersambung ke API seat dan cancel reservation.
- Frontend action drawer POS tersambung ke API kitchen ticket dan delivery order status.
- Frontend action drawer POS tersambung ke API kitchen ticket item status.
- Frontend action drawer POS tersambung ke API void dan refund sale.
- Frontend action drawer POS tersambung ke API digital receipt.

Tahap terkait: 4, 7, 11, 18, 22, 23, 24, 25, 26, 28, 30, 42, 43, 44, 45, 46, 47, 48, 49, 50.

## Milestone 4 - Purchasing & Payables

Status: complete.

- Purchase order, approval, goods receipt, supplier payable, purchase return, dan supplier payment.
- Goods receipt dan supplier payment otomatis masuk accounting journal.
- Frontend action drawer purchasing tersambung ke API purchase order, goods receipt, dan supplier payment.
- Frontend action drawer purchasing tersambung ke API purchase order approval.
- Frontend action drawer purchasing tersambung ke API purchase return.

Tahap terkait: 5, 16, 20, 39, 51, 52.

## Milestone 5 - Accounting & Reports

Status: complete.

- Chart of accounts, manual journal, auto journal, trial balance, profit and loss, general ledger, balance sheet, cash flow, operational expense, payment settlement, provider reconciliation import, dan dashboard reports.
- Frontend action drawer accounting tersambung ke API journal entry, payment settlement, dan provider import.

Tahap terkait: 6, 8, 13, 19, 27, 29, 40.

## Milestone 6 - Customer & Loyalty

Status: complete.

- Customer CRM, customer profile, sales summary, recent sales, loyalty ledger, manual loyalty adjustment, dan auto earn dari sale.
- Frontend action drawer customer tersambung ke API loyalty adjustment.

Tahap terkait: 10, 21, 41.

## Milestone 7 - Administration UX

Status: in progress.

- Frontend action drawer user access tersambung ke API invite user dan assign role.

Tahap terkait: 41.

## Milestone Berikutnya

Prioritas yang masih natural untuk dilanjutkan:

- Frontend UX lanjutan: forms nyata dan API submit per modul.
