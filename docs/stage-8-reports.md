# Tahap 8 - Reports

## Scope

Tahap ini menyiapkan fondasi laporan:

- Penjualan periodik
- Penjualan per cabang
- Penjualan per produk
- Metode pembayaran
- Pajak dan diskon
- Refund dan void foundation
- Stock on hand
- Stock valuation
- Stock movement summary
- Fast moving product
- Purchasing summary
- Open payable
- Trial balance
- Profit and loss

## API Endpoint

Semua endpoint memakai `auth:sanctum`, `tenant`, dan permission `reports.view`.

- `GET /api/reports`
  - Optional query: `date_from`, `date_to`
  - Mengembalikan agregasi sales, stock, purchasing, dan accounting.

## Report Sections

- `sales`
  - Transaction count
  - Subtotal
  - Discount total
  - Tax total
  - Service charge total
  - Grand total
  - Refund total
  - Void count
- `sales_by_branch`
- `sales_by_product`
- `payment_methods`
- `stock`
  - SKU count
  - Quantity on hand
  - Stock value
  - Minimum stock alerts
  - Fast moving
  - Slow moving placeholder
- `stock_movements`
- `purchasing`
  - PO count
  - Goods receipt total
  - Open payable total
- `accounting`
  - Trial balance
  - Profit and loss

## Tenant Isolation

- Semua query memakai `business_id`.
- Jika request memiliki branch context, laporan operasional difilter dengan `branch_id`.
- `sales_by_branch` tetap menampilkan konsolidasi cabang dalam business aktif.

## Test

Test Tahap 8 mencakup:

- Endpoint report mengembalikan struktur utama.
- Laporan sales membaca transaksi completed.
- Laporan stock membaca stock balance dan movement.
- Laporan purchasing membaca payable dan goods receipt.
- Branch context memfilter ringkasan operasional.
