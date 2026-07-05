# Stage 73 - Filament Business and Branch Resources

Status: complete.

## Ringkasan

Tahap ini menambahkan CRUD perusahaan dan cabang asli ke back office Filament.

## Perubahan

- Menambahkan resource `Businesses` untuk data perusahaan/pusat.
- Menambahkan resource `Branches` untuk outlet/cabang.
- Kedua resource ditempatkan pada navigation group `Administration`.
- Form `Business` menyembunyikan UUID karena dibuat otomatis oleh model.
- Form `Branch` mengisi `business_id` dari tenant aktif dan menyembunyikan UUID.
- Tabel `Business` dan `Branch` menampilkan filter aktif/nonaktif.

## Akses

```text
/admin/businesses
/admin/branches
```

Menu:

```text
Administration > Businesses
Administration > Branches
```

## Audit CRUD Filament

Resource yang sudah tersedia:

- Business
- Branch
- Category
- Customer
- DiningTable
- GoodsReceipt
- KitchenStation
- Product
- ProductionOrder
- Promotion
- PurchaseOrder
- PurchaseReturn
- Recipe
- StockAdjustment
- StockBalance
- StockOpname
- StockTransfer
- Supplier
- SupplierPayable
- SupplierPayment
- Tax
- UnitOfMeasure
- Warehouse

CRUD yang belum dibuat dan masih layak jadi resource utama:

- Account
- AccountingPeriod
- AuditLog
- CashDrawerAudit
- CashierShift
- CashMovement
- DeliveryOrder
- HeldTransaction
- JournalEntry
- ModifierGroup
- Modifier
- OfflineSyncBatch
- OfflineSyncConflict
- OperationalExpense
- PaymentProviderImport
- PaymentSettlement
- Permission
- Role
- Sale
- TableReservation
- UnitConversion
- User

Model detail/item yang lebih cocok dibuat sebagai Relation Manager atau read-only detail:

- BranchProductPrice
- CustomerLoyaltyTransaction
- GoodsReceiptItem
- JournalLine
- KitchenTicketItem
- PaymentProviderImportRow
- PaymentSettlementItem
- ProductionOrderItem
- ProductVariant
- PurchaseOrderItem
- PurchaseReturnItem
- RecipeItem
- SaleItem
- SaleItemModifier
- SalePayment
- StockAdjustmentItem
- StockLedger
- StockOpnameItem
- StockTransferItem
- KitchenTicket

## Verifikasi

```bash
php artisan route:list --path=admin
php artisan test
```
