# Tahap 5 - Purchasing

## Scope

Tahap ini menyiapkan fondasi purchasing:

- Purchase order
- Purchase order items
- Approval PO
- Goods receipt
- Goods receipt items
- Purchase return foundation
- Supplier payable
- Receipt posting ke stock ledger

## API Endpoint

Semua endpoint memakai `auth:sanctum`, `tenant`, dan permission `purchases.manage`.

- `GET /api/purchasing`
- `POST /api/purchase-orders`
- `POST /api/purchase-orders/{purchaseOrder}/approve`
- `POST /api/goods-receipts`

## Business Rules

- Supplier, product, dan warehouse wajib berada di business aktif.
- PO menyimpan subtotal, tax total, dan grand total.
- PO bisa di-approve dengan audit log.
- Goods receipt membuat item receipt, supplier payable, stock ledger `purchase_receipt`, dan update stock balance.
- Partial receipt difasilitasi lewat `quantity_received` pada PO item.

## Ledger

Goods receipt tidak mengubah saldo langsung dari controller. Service membuat:

- `goods_receipts`
- `goods_receipt_items`
- `stock_ledgers` movement `purchase_receipt`
- `stock_balances`
- `supplier_payables`

## Audit

- `purchase_order.created`
- `purchase_order.approved`
- `goods_receipt.posted`

## Test

Test mencakup:

- Membuat purchase order.
- Approve purchase order.
- Posting goods receipt menambah stok dan membuat payable.
- Menolak product dari business lain.
