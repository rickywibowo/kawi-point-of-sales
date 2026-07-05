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
- Frontend action drawer inventory tersambung ke API stock adjustment.
- Frontend action drawer inventory tersambung ke API recipe creation.

Tahap terkait: 2, 3, 12, 16, 17, 38, 55, 56.

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
- Frontend dashboard POS memuat konflik offline dari API.
- Frontend POS store memuat cash drawer audit dari API.
- Frontend POS store menghitung void/refund harian dari sale API.
- Frontend POS store memuat held transaction aktif dari API.
- Frontend POS store memuat detail promo aktif dari API.

Tahap terkait: 4, 7, 11, 18, 22, 23, 24, 25, 26, 28, 30, 42, 43, 44, 45, 46, 47, 48, 49, 50, 54, 58, 59, 60, 61.

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
- Frontend action drawer accounting tersambung ke API operational expense.
- Frontend dashboard Reports membaca detail purchasing, payment, product, dan settlement dari API.

Tahap terkait: 6, 8, 13, 19, 27, 29, 40, 53, 64.

## Milestone 6 - Customer & Loyalty

Status: complete.

- Customer CRM, customer profile, sales summary, recent sales, loyalty ledger, manual loyalty adjustment, dan auto earn dari sale.
- Frontend action drawer customer tersambung ke API customer update.
- Frontend action drawer customer tersambung ke API loyalty adjustment.
- Frontend dashboard Customer CRM menampilkan profile summary dan recent sales dari API.

Tahap terkait: 10, 21, 41, 57, 63.

## Milestone 7 - Administration UX

Status: complete.

- Frontend action drawer user access tersambung ke API invite user dan assign role.
- Frontend dashboard settings menampilkan audit log terbaru dari API.

Tahap terkait: 41, 62.

## Milestone Berikutnya

Prioritas yang masih natural untuk dilanjutkan:

- Final QA checklist MVP.
- Tambah Filament resource untuk supplier, customer, inventory, purchasing, accounting, user, audit, dan reports.
- Enhancement lanjutan untuk Segment, Export, dan Print bila dibutuhkan.

## Back Office Filament

Status: in progress.

- Panel Filament tersedia di `/admin`.
- CRUD kategori dan produk tersedia sebagai resource awal.
- CRUD supplier, customer, UOM, tax, warehouse, dining table, promotion, dan kitchen station tersedia.
- Vue tetap difokuskan untuk layar operasional POS.

Tahap terkait: 69, 70.

## Release Lokal

Status: ready for local demo.

- README project menjelaskan setup lokal, login demo, seed demo, dan verifikasi.
- `docs/release-readiness.md` menyediakan checklist local run dan demo flow.
- Dashboard menyediakan modul Help dan panduan tambah kategori/produk.
- Modul Produk menyediakan listing kategori dan delete category yang aman.

Tahap terkait: 66, 67, 68.
