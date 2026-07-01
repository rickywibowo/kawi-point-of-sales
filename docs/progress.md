# KAWI POS Progress Log

## 2026-07-02

### Git

- Repository diinisialisasi.
- Commit baseline Tahap 1: `b2d244f chore: scaffold kawi pos foundation`.

### Tahap 1 - Fondasi

- Laravel + Vue 3 + Vite siap.
- Sanctum API auth siap.
- Pinia siap.
- Multi-business, multi-branch, RBAC, audit log dasar siap.
- Middleware tenant dan permission siap.
- Test fondasi lulus.

### Tahap 2 - Master Data

Status: complete.

- Schema unit of measure, tax, kategori/subkategori, supplier, customer, produk, harga produk per cabang, varian, modifier group, dan modifier dibuat.
- Model dan relationship master data dibuat.
- Service layer `MasterDataService` dibuat untuk create kategori dan produk.
- Request validation untuk kategori dan produk dibuat.
- API endpoint master data dibuat.
- Seeder master data demo dibuat.
- Vue Pinia store master data dibuat.
- Automated test master data dibuat.

### Tahap 3 - Recipe dan Inventory

Status: complete.

- Schema recipe, recipe item, unit conversion, warehouse, stock ledger, stock balance, adjustment, opname, dan transfer dibuat.
- Model dan relationship inventory dibuat.
- Service layer `InventoryService` dibuat untuk create recipe dan post stock adjustment.
- Request validation untuk recipe dan stock adjustment dibuat.
- API endpoint inventory dibuat.
- Seeder inventory demo dibuat.
- Vue Pinia store inventory dibuat.
- Automated test inventory dibuat.

### Tahap 4 - POS

Status: complete.

- Schema shift kasir, cash movement, sale, sale item, modifier item, payment, dan held transaction dibuat.
- Model dan relationship POS dibuat.
- Service layer `PosService` dibuat untuk open/close shift, hold transaction, dan complete sale.
- Sale posting menulis stock ledger `sales_consumption` dan update stock balance.
- Request validation dan API endpoint POS dibuat.
- Seeder POS demo dibuat.
- Vue Pinia store POS dibuat.
- Automated test POS dibuat.

### Tahap 5 - Purchasing

Status: complete.

- Schema purchase order, goods receipt, purchase return, dan supplier payable dibuat.
- Model dan relationship purchasing dibuat.
- Service layer `PurchasingService` dibuat untuk create PO, approve PO, dan post goods receipt.
- Goods receipt menulis stock ledger `purchase_receipt` dan update stock balance.
- API endpoint purchasing dibuat.
- Seeder purchasing demo dibuat.
- Vue Pinia store purchasing dibuat.
- Automated test purchasing dibuat.

### Tahap 6 - Accounting

Status: complete.

- Schema accounting period, chart of accounts, journal entry, dan journal line dibuat.
- Model dan relationship accounting dibuat.
- Service layer `AccountingService` dibuat untuk manual journal, trial balance, P&L, dan auto journal.
- Auto journal POS sale dan goods receipt dibuat.
- API endpoint accounting dibuat.
- Seeder chart of accounts dibuat.
- Vue Pinia store accounting dibuat.
- Automated test accounting dibuat.

### Tahap 7 - Offline Mode

Status: in progress, implementasi awal selesai.

- Schema offline sync batch dan conflict dibuat.
- Backend service offline sales sync dibuat.
- Endpoint sync sales dan conflict review dibuat.
- IndexedDB helper native dibuat.
- Vue Pinia store offline queue/status dibuat.
- Seeder conflict demo dibuat.
- Automated test offline sync dibuat.

## Cara Track Mundur

- Setiap tahap disimpan dalam commit terpisah.
- Dokumentasi tahap berada di `docs/`.
- Test harus lulus sebelum commit tahap dibuat.
- Untuk melihat perubahan tahap tertentu:

```bash
git log --oneline
git show <commit>
```
