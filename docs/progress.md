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

Status: complete.

- Schema offline sync batch dan conflict dibuat.
- Backend service offline sales sync dibuat.
- Endpoint sync sales dan conflict review dibuat.
- IndexedDB helper native dibuat.
- Vue Pinia store offline queue/status dibuat.
- Seeder conflict demo dibuat.
- Automated test offline sync dibuat.

### Tahap 8 - Reports

Status: complete.

- Service layer `ReportService` dibuat untuk agregasi sales, stock, purchasing, dan accounting.
- Endpoint `GET /api/reports` dibuat.
- Laporan mendukung filter tanggal dan branch context.
- Vue Pinia store reports dibuat.
- Dokumentasi Tahap 8 dibuat.
- Automated test reports dibuat.

### Tahap 9 - Production Readiness

Status: complete.

- Endpoint publik `GET /api/health` dibuat untuk monitoring app, database, runtime, dan release metadata.
- `.env.example` dilengkapi variabel deployment backend/frontend.
- Dokumentasi deploy Laravel Cloud + Cloudflare Pages dibuat.
- Automated test health check dibuat.

### Tahap 10 - Customer CRM Foundation

Status: complete.

- Service layer `CustomerService` dibuat untuk list, create, update, profile, dan summary pelanggan.
- Endpoint customer lookup/profile dibuat.
- POS sale dan held transaction memvalidasi `customer_id` dalam business aktif.
- Vue Pinia store customer dan panel dashboard Customer CRM dibuat.
- Dokumentasi Tahap 10 dibuat.
- Automated test customer CRM dan tenant isolation dibuat.

### Tahap 11 - Post-Sale Controls

Status: complete.

- Endpoint cash movement shift kasir dibuat.
- Endpoint void dan refund sale dibuat.
- Void/refund membuat stock ledger pembalik dan mengembalikan stock balance.
- Close shift menghitung expected cash dari opening cash, cash sales, cash in, dan cash out.
- Vue Pinia store POS dan panel dashboard Post-Sale Controls dibuat.
- Dokumentasi Tahap 11 dibuat.
- Automated test post-sale controls dibuat.

### Tahap 12 - Inventory Controls

Status: complete.

- Tabel item untuk stock transfer dan stock opname dibuat.
- Endpoint stock transfer dan stock opname dibuat.
- Transfer stok membuat ledger `transfer_out` dan `transfer_in`.
- Opname stok membuat ledger `stock_opname` berdasarkan variance.
- Inventory index mengembalikan dokumen transfer dan opname terbaru.
- Vue Pinia store inventory dan dashboard kontrol stok diperbarui.
- Dokumentasi Tahap 12 dibuat.
- Automated test inventory controls dibuat.

### Tahap 13 - Accounting Statements

Status: complete.

- General ledger dibuat dari journal lines per account.
- Balance sheet dibuat dari trial balance dan net profit periode berjalan.
- Cash flow dibuat dari akun kas/bank.
- Endpoint accounting mengembalikan general ledger, balance sheet, dan cash flow.
- Endpoint reports memasukkan balance sheet dan cash flow dalam blok accounting.
- Vue Pinia store accounting/reports dan dashboard accounting diperbarui.
- Dokumentasi Tahap 13 dibuat.
- Automated test accounting statements dibuat.

### Tahap 14 - User & RBAC Administration

Status: complete.

- Service layer `UserAccessService` dibuat untuk directory user access, invite user, dan assign role.
- Endpoint user access dibuat dengan permission `users.manage`.
- Validasi tenant isolation untuk user, role, dan branch dibuat.
- Audit log `user.invited` dan `role.assigned` dibuat.
- Vue Pinia store user access dan panel dashboard User Access dibuat.
- Dokumentasi Tahap 14 dibuat.
- Automated test user/RBAC administration dibuat.

### Tahap 15 - Audit Review & Compliance

Status: complete.

- Service layer `AuditReviewService` dibuat untuk audit log, filter, summary, dan security events.
- Endpoint `GET /api/audit-logs` dibuat dengan permission `users.manage`.
- Filter audit berdasarkan action, entity type, user, dan tanggal dibuat.
- Tenant isolation audit log business aktif dibuat.
- Vue Pinia store audit dan panel dashboard Audit Review dibuat.
- Dokumentasi Tahap 15 dibuat.
- Automated test audit review dan tenant isolation dibuat.

### Tahap 16 - Purchase Returns

Status: complete.

- Tabel `purchase_return_items` dibuat.
- Endpoint `POST /api/purchase-returns` dibuat.
- Posting retur membuat ledger `purchase_return` dan mengurangi stock balance.
- Posting retur mengurangi supplier payable terkait goods receipt.
- Validasi retur terhadap goods receipt item dan quantity received dibuat.
- Vue Pinia store purchasing dan dashboard retur diperbarui.
- Dokumentasi Tahap 16 dibuat.
- Automated test purchase return dibuat.

### Tahap 17 - Production Foundation

Status: complete.

- Tabel `production_orders` dan `production_order_items` dibuat.
- Endpoint `POST /api/production-orders` dibuat.
- Produksi memakai recipe untuk konsumsi bahan dan output produk.
- Ledger `production_consumption` dan `production_output` dibuat.
- Actual quantity, planned quantity, waste quantity, dan total cost dicatat.
- Inventory index dan dashboard inventory menampilkan production orders.
- Dokumentasi Tahap 17 dibuat.
- Automated test production foundation dibuat.

### Tahap 18 - Receipt & Transaction History

Status: complete.

- Service layer `ReceiptService` dibuat untuk payload struk digital.
- Endpoint `GET /api/sales/{sale}/receipt` dibuat.
- Receipt berisi business, branch, sale, items, payments, totals, dan QR payload.
- Tenant dan branch isolation receipt dibuat.
- Vue Pinia store POS dan dashboard struk diperbarui.
- Dokumentasi Tahap 18 dibuat.
- Automated test receipt dibuat.

## 2026-07-03

### Tahap 19 - Operational Expenses

Status: complete.

- Tabel `operational_expenses` dibuat untuk pencatatan pengeluaran outlet.
- Model, request validation, service, dan endpoint operational expense dibuat.
- Endpoint `POST /api/operational-expenses` dibuat dengan permission `accounting.manage`.
- Posting expense otomatis membuat jurnal debit beban dan kredit kas.
- Accounting index mengembalikan daftar operational expenses terbaru.
- Report dashboard menampilkan ringkasan expense dan total per kategori.
- Vue Pinia store accounting dan dashboard accounting diperbarui.
- Dokumentasi Tahap 19 dibuat.
- Automated test operational expense dibuat.

### Tahap 20 - Supplier Payable Payments

Status: complete.

- Tabel `supplier_payments` dibuat untuk pembayaran utang supplier.
- Model, request validation, controller, dan endpoint supplier payment dibuat.
- Endpoint `POST /api/supplier-payables/{payable}/payments` dibuat dengan permission `purchases.manage`.
- Payment mengurangi sisa payable dan mengubah status menjadi `partial` atau `closed`.
- Posting payment otomatis membuat jurnal debit utang usaha dan kredit kas.
- Purchasing index mengembalikan supplier payments dan payment history per payable.
- Report purchasing menampilkan total supplier payment periode aktif.
- Vue Pinia store purchasing dan dashboard purchasing diperbarui.
- Dokumentasi Tahap 20 dibuat.
- Automated test supplier payment dibuat.

### Tahap 21 - Customer Loyalty Ledger

Status: complete.

- Tabel `customer_loyalty_transactions` dibuat untuk riwayat poin pelanggan.
- Model, request validation, dan endpoint loyalty transaction dibuat.
- Endpoint `POST /api/customers/{customer}/loyalty-transactions` dibuat dengan permission `sales.create`.
- Customer profile mengembalikan loyalty transactions terbaru.
- Manual adjustment poin dapat menambah/mengurangi poin dengan proteksi balance tidak boleh negatif.
- POS sale dengan customer otomatis membuat loyalty earn berdasarkan grand total.
- Audit log `customer.loyalty_adjusted` dibuat.
- Vue Pinia store customer dan dashboard Customer CRM diperbarui.
- Dokumentasi Tahap 21 dibuat.
- Automated test customer loyalty dibuat.

### Tahap 22 - Dine-in Table Management

Status: complete.

- Tabel `dining_tables` dibuat dan `sales.dining_table_id` ditambahkan.
- Model, request validation, service, dan endpoint dining table dibuat.
- Endpoint `POST /api/dining-tables` dan `PATCH /api/dining-tables/{table}/status` dibuat.
- POS index mengembalikan daftar dining tables.
- Sale tipe `dine_in` wajib memakai meja dari branch aktif.
- Meja dine-in berubah menjadi `cleaning` setelah sale selesai.
- Receipt digital menampilkan informasi meja.
- Seeder POS, Vue Pinia store POS, dan dashboard POS diperbarui.
- Dokumentasi Tahap 22 dibuat.
- Automated test dine-in table management dibuat.

## Cara Track Mundur

- Setiap tahap disimpan dalam commit terpisah.
- Dokumentasi tahap berada di `docs/`.
- Test harus lulus sebelum commit tahap dibuat.
- Untuk melihat perubahan tahap tertentu:

```bash
git log --oneline
git show <commit>
```
