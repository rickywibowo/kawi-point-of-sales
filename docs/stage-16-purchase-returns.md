# Tahap 16 - Purchase Returns

Status: complete.

## Tujuan

Tahap ini melengkapi purchasing dengan workflow retur pembelian:

- Retur barang dari goods receipt.
- Item retur per produk.
- Stock ledger `purchase_return`.
- Pengurangan stock balance.
- Pengurangan supplier payable.
- Audit log retur pembelian.

## Database

Migration baru:

- `2026_07_02_000007_create_purchase_return_items_table.php`

Tabel baru:

- `purchase_return_items`

## Endpoint

```http
POST /api/purchase-returns
```

Middleware:

- `auth:sanctum`
- `tenant`
- `permission:purchases.manage`

## Business Rules

- Goods receipt harus milik business aktif dan supplier yang sama.
- Product harus milik business aktif.
- Jika `goods_receipt_item_id` dikirim, item harus berasal dari goods receipt tersebut.
- Quantity retur tidak boleh lebih besar dari quantity received.

## Stock dan Payable

Posting retur:

- Membuat `purchase_returns`.
- Membuat `purchase_return_items`.
- Membuat stock ledger `purchase_return`.
- Mengurangi `stock_balances`.
- Mengurangi `supplier_payables.amount` untuk goods receipt terkait.

## Frontend

Store purchasing dan dashboard awal menampilkan ringkasan total retur pembelian.

## Test

Automated test:

- `tests/Feature/Purchasing/PurchasingTest.php`

Coverage:

- Retur mengurangi stok.
- Retur mengurangi payable.
- Retur membuat stock ledger dan audit log.
- Retur menolak quantity melebihi received quantity.
