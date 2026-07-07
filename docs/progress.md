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

### Tahap 23 - Table Reservations

Status: complete.

- Tabel `table_reservations` dibuat untuk booking meja.
- Model, request validation, service workflow, dan endpoint reservasi dibuat.
- Endpoint `POST /api/dining-tables/{table}/reservations` dibuat.
- Endpoint cancel dan seat reservation dibuat.
- POS index mengembalikan reservasi aktif hari berjalan.
- Reservasi mengubah status meja menjadi `reserved`, `occupied`, atau kembali `available`.
- Validasi kapasitas meja dan overlap reservation dibuat.
- Vue Pinia store POS dan dashboard POS diperbarui.
- Dokumentasi Tahap 23 dibuat.
- Automated test table reservations dibuat.

### Tahap 24 - Promotion & Voucher Engine

Status: complete.

- Tabel `promotions` dibuat dan kolom promo ditambahkan ke `sales`.
- Model, request validation, service, controller, dan endpoint promo dibuat.
- Endpoint `POST /api/promotions` dibuat dengan permission `sales.create`.
- POS index mengembalikan promo aktif.
- Sale menerima `promotion_code` dan menghitung promotion discount.
- Promo mendukung tipe `percent` dan `fixed`, minimum subtotal, maximum discount, dan usage limit.
- Receipt digital menampilkan kode promo dan total diskon promo.
- Vue Pinia store POS dan dashboard POS diperbarui.
- Dokumentasi Tahap 24 dibuat.
- Automated test promotion/voucher dibuat.

### Tahap 25 - Kitchen Display System Foundation

Status: complete.

- Tabel `kitchen_tickets` dan `kitchen_ticket_items` dibuat.
- Model, service, controller, dan endpoint Kitchen Display System dibuat.
- Completed sale otomatis membuat kitchen ticket `KOT-{sale_number}`.
- Kitchen ticket item dibuat dari sale items.
- Endpoint `GET /api/kitchen-tickets` dibuat untuk ticket aktif.
- Endpoint update status kitchen ticket dan kitchen ticket item dibuat.
- POS index mengembalikan kitchen tickets aktif.
- Vue Pinia store POS dan dashboard POS menampilkan kitchen queue.
- Dokumentasi Tahap 25 dan ringkasan milestone proyek dibuat.
- Automated test KDS dibuat.

### Tahap 26 - Delivery Workflow Foundation

Status: complete.

- Tabel `delivery_orders` dibuat dan `sales.delivery_fee_total` ditambahkan.
- Model, request validation, service, controller, dan endpoint delivery dibuat.
- Sale tipe `delivery` wajib menyertakan recipient dan address.
- Delivery fee masuk ke grand total sale.
- Sale delivery otomatis membuat delivery order `DO-{sale_number}`.
- Endpoint `GET /api/delivery-orders` dan update status delivery dibuat.
- POS index mengembalikan delivery orders aktif.
- Receipt digital menampilkan detail delivery dan delivery fee.
- Vue Pinia store POS dan dashboard POS menampilkan delivery queue.
- Dokumentasi Tahap 26 dibuat.
- Automated test delivery workflow dibuat.

### Tahap 27 - Payment Settlement & Reconciliation

Status: complete.

- Tabel `payment_settlements` dan `payment_settlement_items` dibuat.
- Model, request validation, service, controller, dan endpoint payment settlement dibuat.
- Endpoint `GET /api/payment-settlements` dan `POST /api/payment-settlements` dibuat dengan permission `accounting.manage`.
- Settlement menghitung expected amount dari sale payment completed yang belum pernah disettlement.
- Variance dihitung dari reported amount dikurangi expected amount.
- Sale payment yang sudah masuk settlement tidak dapat disettlement ulang.
- Audit log `payment_settlement.posted` dibuat.
- Report dashboard menampilkan ringkasan payment settlements dan breakdown per method.
- Vue Pinia store accounting dan dashboard accounting diperbarui.
- Dokumentasi Tahap 27 dibuat.
- Automated test payment settlement dibuat.

### Tahap 28 - Cash Drawer Session Audit

Status: complete.

- Tabel `cash_drawer_audits` dibuat untuk bukti audit tutup kasir.
- Model `CashDrawerAudit` dibuat dan direlasikan ke `CashierShift`.
- Close shift mendukung input denominasi kas melalui `drawer_counts`.
- Total denominasi wajib sama dengan `actual_cash`.
- Selisih kas wajib memiliki `variance_reason`.
- Status audit mendukung `balanced`, `variance_pending`, dan `variance_approved`.
- Endpoint `GET /api/pos` mengembalikan drawer audits terbaru.
- Audit log `cash_drawer.audit_created` dibuat.
- Vue Pinia store POS dan dashboard Post-Sale Controls diperbarui.
- Dokumentasi Tahap 28 dibuat.
- Automated test cash drawer audit dibuat.

### Tahap 29 - Payment Provider Reconciliation Import

Status: complete.

- Tabel `payment_provider_imports` dan `payment_provider_import_rows` dibuat.
- Model, request validation, service, controller, dan endpoint provider import dibuat.
- Endpoint `GET /api/payment-provider-imports` dan `POST /api/payment-provider-imports` dibuat dengan permission `accounting.manage`.
- Import provider menempel ke `payment_settlement_id` dan wajib memakai method yang sama.
- CSV provider diparse dari `csv_content` dengan header minimal `reference` dan `amount`.
- Row provider dicocokkan ke `sale_payments.reference` dari settlement internal.
- Status row mendukung `matched`, `amount_mismatch`, dan `unmatched`.
- Header import menghitung gross, fee, received amount, matched/unmatched count, dan variance terhadap settlement.
- Audit log `payment_provider_import.created` dibuat.
- Vue Pinia store accounting dan dashboard accounting diperbarui.
- Dokumentasi Tahap 29 dibuat.
- Automated test payment provider import dibuat.

### Tahap 30 - KDS Station Routing & Slip Payload

Status: complete.

- Tabel `kitchen_stations` dibuat untuk station dapur per branch.
- Kolom `products.kitchen_station_id` ditambahkan untuk routing produk ke station.
- Kolom station, course, dan sequence ditambahkan ke `kitchen_ticket_items`.
- Model `KitchenStation` dibuat.
- Relasi produk dan kitchen ticket item ke station dibuat.
- Endpoint `POST /api/kitchen-stations` dibuat dengan permission `sales.create`.
- Endpoint `GET /api/kitchen-tickets/{ticket}/slip` dibuat untuk payload printed kitchen slip.
- KOT otomatis mengisi station item dari mapping produk.
- Endpoint KDS dan POS mengembalikan daftar kitchen stations aktif.
- Vue Pinia store POS dan dashboard POS diperbarui.
- Dokumentasi Tahap 30 dibuat.
- Automated test KDS station routing dibuat.

### Tahap 31 - Frontend API Integration Foundation

Status: complete.

- API client frontend `resources/js/services/api.js` dibuat.
- API client mendukung bearer token, tenant header, branch header, dan error handling.
- Store foundation menambahkan login demo, session loader, API status, dan tenant context persistence.
- Store master data, inventory, POS, purchasing, accounting, reports, customers, user access, dan audit mendapat action `loadFromApi()`.
- Dashboard memanggil `loadDashboard()` saat mounted.
- Dashboard tetap fallback ke data demo saat user belum login atau API belum tersedia.
- Tombol `Connect Demo API` ditambahkan untuk hydrate dashboard dari API demo.
- Dokumentasi Tahap 31 dibuat.

### Tahap 32 - Login & Session UX

Status: complete.

- Panel login dashboard dibuat untuk login API dari browser.
- Credential demo default `owner@kawi.test` dan `password` disediakan di form.
- Header dashboard menampilkan status API, tombol login, dan tombol logout.
- Store foundation menambahkan loading session, login error, dan action logout.
- API client menambahkan helper `clearApiSession()`.
- Logout membersihkan token dan tenant context lalu kembali ke mode demo.
- Quick stats dashboard diubah menjadi computed agar mengikuti data store setelah API hydrate.
- Dokumentasi Tahap 32 dibuat.

### Tahap 33 - Frontend Module Navigation Foundation

Status: complete.

- State `activeModule` dibuat di dashboard Vue.
- Sidebar modul berubah menjadi selector module aktif.
- Workspace module ditambahkan di area utama dashboard.
- Workspace menampilkan data ringkas sesuai module aktif.
- Modul awal mencakup kasir, produk, inventori, purchasing, accounting, laporan, pelanggan, dan pengaturan.
- Dashboard overview lama tetap dipertahankan.
- Dokumentasi Tahap 33 dibuat.

### Tahap 34 - Frontend Module Table & Action Foundation

Status: complete.

- Workspace modul mendapat search input.
- Data workspace difilter berdasarkan data, info, dan status.
- Toolbar action awal dibuat per modul.
- Pencarian reset saat user berpindah modul.
- Empty state filter ditambahkan.
- Dokumentasi Tahap 34 dibuat.

### Tahap 35 - Frontend Action Drawer & Form Foundation

Status: complete.

- Action toolbar workspace sekarang membuka drawer/form konteks.
- State `activeAction`, `actionDraft`, dan `actionFeedback` dibuat.
- Field form action dibuat dinamis berdasarkan action yang dipilih.
- Tombol `Save Draft` menampilkan feedback lokal untuk dasar submit API berikutnya.
- Perpindahan modul menutup drawer action dan reset pencarian.
- Dokumentasi Tahap 35 dibuat.

### Tahap 36 - Frontend API Submit Actions

Status: complete.

- Action drawer mulai tersambung ke endpoint API nyata.
- `New Customer` submit ke `POST /api/customers`.
- `New Product` submit ke `POST /api/products`.
- Store customer dan master data reload setelah submit berhasil.
- Drawer menampilkan badge `API submit ready` atau `Draft only`.
- Action lain tetap memakai mode draft lokal.
- Dokumentasi Tahap 36 dibuat.

### Tahap 37 - Frontend POS Submit Actions

Status: complete.

- Action `Open Shift` submit ke `POST /api/cashier-shifts`.
- Action `Hold Cart` submit ke `POST /api/held-transactions`.
- Badge `API submit ready` diperluas untuk action POS awal.
- POS store reload setelah action POS berhasil.
- Dokumentasi Tahap 37 dibuat.

## 2026-07-04

### Tahap 38 - Frontend Inventory Submit Actions

Status: complete.

- Store inventory frontend menyimpan ID warehouse, produk, dan recipe dari API.
- Action `Stock Opname` submit ke `POST /api/stock-opnames`.
- Action `Transfer Stock` submit ke `POST /api/stock-transfers`.
- Action `Production` submit ke `POST /api/production-orders`.
- Payload drawer inventory mengikuti request validation backend.
- Inventory store reload setelah action inventory berhasil.
- Dokumentasi Tahap 38 dibuat.

### Tahap 39 - Frontend Purchasing Submit Actions

Status: complete.

- Store master data frontend menyimpan ID supplier dan produk untuk payload purchasing.
- Store purchasing frontend menyimpan ID purchase order, goods receipt, purchase return, dan supplier payable.
- Action `New PO` submit ke `POST /api/purchase-orders`.
- Action `Goods Receipt` submit ke `POST /api/goods-receipts`.
- Action `Pay Supplier` submit ke `POST /api/supplier-payables/{payable}/payments`.
- Payload drawer purchasing mengikuti request validation backend.
- Purchasing dan inventory store reload setelah action purchasing berhasil.
- Dokumentasi Tahap 39 dibuat.

### Tahap 40 - Frontend Accounting Submit Actions

Status: complete.

- Store accounting frontend menyimpan ID account, payment settlement, dan provider import dari API.
- Action `New Journal` submit ke `POST /api/journal-entries`.
- Action `Settlement` submit ke `POST /api/payment-settlements`.
- Action `Import Provider` submit ke `POST /api/payment-provider-imports`.
- Payload manual journal dibuat seimbang dengan dua line debit/kredit.
- Accounting store reload setelah action accounting berhasil.
- Dokumentasi Tahap 40 dibuat.

### Tahap 41 - Frontend Customer & User Submit Actions

Status: complete.

- Store customer frontend menyimpan ID customer dari paginator API.
- Store user access frontend menyimpan ID user, role, dan branch dari API.
- Action `Loyalty` submit ke `POST /api/customers/{customer}/loyalty-transactions`.
- Action `Invite User` submit ke `POST /api/user-access/users`.
- Action `Assign Role` submit ke `POST /api/user-access/users/{user}/roles`.
- Customer dan user access store reload setelah submit berhasil.
- Dokumentasi Tahap 41 dibuat.

### Tahap 42 - Frontend POS Sale Submit

Status: complete.

- POS index API mengembalikan `active_shift` user pada branch aktif.
- POS store frontend menyimpan shift ID, produk POS, dan warehouse POS dari API.
- Action `New Sale` submit ke `POST /api/sales`.
- Payload sale memakai shift aktif, warehouse, produk, quantity, payment, dan idempotency key.
- POS store reload setelah sale berhasil.
- Dokumentasi Tahap 42 dibuat.

### Tahap 43 - Frontend POS Shift Controls

Status: complete.

- Action `Cash Movement` ditambahkan ke modul Kasir.
- Action `Close Shift` ditambahkan ke modul Kasir.
- `Cash Movement` submit ke `POST /api/cashier-shifts/{shift}/cash-movements`.
- `Close Shift` submit ke `POST /api/cashier-shifts/{shift}/close`.
- POS store menyimpan expected cash dari active shift dan mengosongkan shift ID saat shift tidak aktif.
- POS store reload setelah cash movement atau close shift berhasil.
- Dokumentasi Tahap 43 dibuat.

### Tahap 44 - Frontend POS Setup Actions

Status: complete.

- Action `New Promo` ditambahkan ke modul Kasir dan submit ke `POST /api/promotions`.
- Action `New Table` ditambahkan ke modul Kasir dan submit ke `POST /api/dining-tables`.
- Action `Kitchen Station` ditambahkan ke modul Kasir dan submit ke `POST /api/kitchen-stations`.
- POS store menyimpan ID promo, dining table, dan kitchen station dari API.
- POS store reload setelah action setup POS berhasil.
- Dokumentasi Tahap 44 dibuat.

### Tahap 45 - Frontend POS Table Actions

Status: complete.

- Action `Table Status` ditambahkan ke modul Kasir.
- Action `Reserve Table` ditambahkan ke modul Kasir.
- `Table Status` submit ke `PATCH /api/dining-tables/{table}/status`.
- `Reserve Table` submit ke `POST /api/dining-tables/{table}/reservations`.
- POS store menyimpan ID table reservation dari API.
- POS store reload setelah action meja atau reservasi berhasil.
- Dokumentasi Tahap 45 dibuat.

### Tahap 46 - Frontend POS Reservation Lifecycle

Status: complete.

- Action `Seat Reservation` ditambahkan ke modul Kasir.
- Action `Cancel Reservation` ditambahkan ke modul Kasir.
- `Seat Reservation` submit ke `PATCH /api/table-reservations/{reservation}/seat`.
- `Cancel Reservation` submit ke `PATCH /api/table-reservations/{reservation}/cancel`.
- POS store reload setelah seat atau cancel reservation berhasil.
- Dokumentasi Tahap 46 dibuat.

### Tahap 47 - Frontend POS Status Actions

Status: complete.

- POS store menyimpan ID kitchen ticket dan delivery order dari API.
- Action `Kitchen Status` ditambahkan ke modul Kasir.
- Action `Delivery Status` ditambahkan ke modul Kasir.
- `Kitchen Status` submit ke `PATCH /api/kitchen-tickets/{ticket}/status`.
- `Delivery Status` submit ke `PATCH /api/delivery-orders/{delivery}/status`.
- POS store reload setelah update status kitchen atau delivery berhasil.
- Dokumentasi Tahap 47 dibuat.

### Tahap 48 - Frontend POS Kitchen Item Status

Status: complete.

- POS store menyimpan daftar kitchen ticket item dari API.
- Action `Kitchen Item Status` ditambahkan ke modul Kasir.
- `Kitchen Item Status` submit ke `PATCH /api/kitchen-ticket-items/{item}/status`.
- POS store reload setelah update status item kitchen berhasil.
- Dokumentasi Tahap 48 dibuat.

### Tahap 49 - Frontend POS Sale Status Actions

Status: complete.

- POS store menyimpan daftar sale harian dari API.
- Action `Void Sale` ditambahkan ke modul Kasir.
- Action `Refund Sale` ditambahkan ke modul Kasir.
- `Void Sale` submit ke `POST /api/sales/{sale}/void`.
- `Refund Sale` submit ke `POST /api/sales/{sale}/refund`.
- POS store reload setelah void atau refund sale berhasil.
- Dokumentasi Tahap 49 dibuat.

### Tahap 50 - Frontend POS Receipt Action

Status: complete.

- POS store menyimpan daftar receipt dari sale harian API.
- Action `View Receipt` ditambahkan ke modul Kasir.
- `View Receipt` mengambil data dari `GET /api/sales/{sale}/receipt`.
- Feedback action menampilkan nomor receipt dan grand total.
- Dokumentasi Tahap 50 dibuat.

### Tahap 51 - Frontend Purchasing Return Action

Status: complete.

- Purchasing store menyimpan supplier ID dan item pertama dari goods receipt.
- Action `Return Supplier` ditambahkan ke modul Purchasing.
- `Return Supplier` submit ke `POST /api/purchase-returns`.
- Purchasing dan inventory store reload setelah return supplier berhasil.
- Dokumentasi Tahap 51 dibuat.

### Tahap 52 - Frontend Purchasing Approve PO Action

Status: complete.

- Action `Approve PO` ditambahkan ke modul Purchasing.
- `Approve PO` submit ke `POST /api/purchase-orders/{purchaseOrder}/approve`.
- Action memakai purchase order draft pertama sebagai default.
- Purchasing dan inventory store reload setelah approval berhasil.
- Dokumentasi Tahap 52 dibuat.

### Tahap 53 - Frontend Accounting Operational Expense Action

Status: complete.

- Action `Operational Expense` ditambahkan ke modul Accounting.
- `Operational Expense` submit ke `POST /api/operational-expenses`.
- Payload expense memakai expense account, cash account, category, payee, description, amount, payment method, dan reference.
- Accounting store reload setelah expense berhasil diposting.
- Dokumentasi Tahap 53 dibuat.

### Tahap 54 - Frontend Offline Conflicts

Status: complete.

- Offline store menambahkan action `loadConflicts`.
- `loadConflicts` mengambil data dari `GET /api/offline/conflicts`.
- Dashboard memuat konflik offline bersama data modul lain.
- Panel Offline Sync menampilkan waktu cek konflik terakhir.
- Dokumentasi Tahap 54 dibuat.

### Tahap 55 - Frontend Inventory Stock Adjustment Action

Status: complete.

- Action `Stock Adjustment` ditambahkan ke modul Inventory.
- `Stock Adjustment` submit ke `POST /api/stock-adjustments`.
- Payload adjustment memakai warehouse, product, quantity delta, unit cost, dan notes.
- Inventory store reload setelah adjustment berhasil diposting.
- Dokumentasi Tahap 55 dibuat.

### Tahap 56 - Frontend Inventory Recipe Action

Status: complete.

- Action `New Recipe` ditambahkan ke modul Inventory.
- `New Recipe` submit ke `POST /api/recipes`.
- Payload recipe memakai product, ingredient product, yield quantity, ingredient quantity, unit cost, dan waste percentage.
- Inventory store reload setelah recipe berhasil dibuat.
- Dokumentasi Tahap 56 dibuat.

### Tahap 57 - Frontend Customer Update Action

Status: complete.

- Action `Update Customer` ditambahkan ke modul Customers.
- `Update Customer` submit ke `PATCH /api/customers/{customer}`.
- Payload update memakai name, phone, email, notes, dan status active.
- Customer store reload setelah update berhasil.
- Dokumentasi Tahap 57 dibuat.

### Tahap 58 - Frontend POS Drawer Audit API

Status: complete.

- POS store menyimpan daftar drawer audit dari API.
- POS store memetakan cash drawer audit terbaru ke `drawerAudit`.
- Panel POS memakai status, expected cash, counted cash, variance, dan denomination breakdown dari API.
- Data demo drawer audit tetap menjadi fallback jika API belum mengirim audit.
- Dokumentasi Tahap 58 dibuat.

### Tahap 59 - Frontend POS Post-Sale Counts

Status: complete.

- POS store menghitung `voidedToday` dari sale harian berstatus `voided`.
- POS store menghitung `refundedToday` dari sale harian berstatus `refunded`.
- Panel Post-Sale Controls memakai counter void/refund dari API.
- Data demo post-sale tetap menjadi fallback jika API belum mengirim sale harian.
- Dokumentasi Tahap 59 dibuat.

### Tahap 60 - Frontend POS Held Transaction List

Status: complete.

- POS store menyimpan `heldTransactionItems` dari held transactions API.
- Counter `heldTransactions` tetap dihitung dari jumlah hold aktif.
- Panel Post-Sale Controls menampilkan hold aktif terbaru dan jumlah itemnya.
- Data demo held transaction tetap menjadi fallback jika API belum mengirim hold aktif.
- Dokumentasi Tahap 60 dibuat.

### Tahap 61 - Frontend POS Promotion Details

Status: complete.

- POS store memetakan detail aturan promo dari promotions API.
- Panel POS menampilkan nilai promo, minimum transaksi, maksimum diskon, dan periode berlaku.
- Data demo promo tetap menjadi fallback jika API belum mengirim promo aktif.
- Dokumentasi Tahap 61 dibuat.

### Tahap 62 - Frontend Audit Log List

Status: complete.

- Audit store membaca summary action dan recent security event sesuai response API.
- Audit store menyimpan daftar audit log terbaru dari paginator API.
- Panel Audit Review menampilkan action, actor, branch, entity, dan waktu audit terbaru.
- Dokumentasi Tahap 62 dibuat.

### Tahap 63 - Frontend Customer Profile Summary

Status: complete.

- Customer store mengambil profile customer aktif dari API.
- Customer store menyimpan customer summary, recent sales, dan loyalty transaction terbaru.
- Panel Customer CRM menampilkan AOV, last purchase, recent sale, dan loyalty terakhir.
- Dokumentasi Tahap 63 dibuat.

### Tahap 64 - Frontend Reports API Detail

Status: complete.

- Reports store membaca payload dari wrapper `reports` sesuai response API.
- Reports store memetakan purchasing, payment methods, top products, dan settlement summary.
- Panel Reports menampilkan top product, payment method terbesar, dan open payable.
- Dokumentasi Tahap 64 dibuat.

### Tahap 65 - Frontend Dashboard Refresh State

Status: complete.

- Dashboard menampilkan waktu refresh terakhir saat semua modul berhasil dimuat.
- Dashboard menampilkan modul yang gagal jika refresh parsial bermasalah.
- Tombol Refresh global dan action Refresh modul Reports memakai flow refresh yang sama.
- Dokumentasi Tahap 65 dibuat.

### Tahap 66 - Release Readiness Docs

Status: complete.

- README project KAWI POS dibuat untuk setup lokal, login demo, seed demo, dan verifikasi.
- Dokumen `docs/release-readiness.md` dibuat untuk local run checklist, demo flow, API notes, dan release notes.
- Dokumentasi Tahap 66 dibuat.

### Tahap 67 - Help and Product Category UX

Status: complete.

- Modul Help ditambahkan ke navigasi dashboard.
- Panel Help menampilkan panduan cepat tambah kategori, tambah produk, dan dokumentasi.
- Action `New Category` ditambahkan ke modul Produk dan tersambung ke API categories.
- Action `New Product` mendukung `Category ID`.
- Sidebar Master Data menampilkan daftar kategori beserta ID.
- Dokumentasi Tahap 67 dibuat.

### Tahap 68 - Product Category List and Delete

Status: complete.

- Workspace modul Produk menampilkan listing kategori beserta `Category ID`.
- Action `Delete Category` ditambahkan ke modul Produk.
- Endpoint `DELETE /api/categories/{category}` ditambahkan.
- Delete category ditolak jika kategori masih memiliki produk atau child category.
- Automated test delete category ditambahkan.
- Dokumentasi Tahap 68 dibuat.

### Tahap 69 - Filament Back Office Foundation

Status: complete.

- Paket Filament ditambahkan ke project.
- Panel back office `/admin` dibuat.
- Filament resource kategori dan produk dibuat untuk CRUD back office.
- Form/table kategori dan produk dirapikan untuk business aktif KAWI.
- User model diberi akses panel Filament.
- Guard delete kategori dipakai oleh API dan Filament.
- Dokumentasi Tahap 69 dibuat.

### Tahap 70 - Filament Master and POS Setup Resources

Status: complete.

- Filament resource supplier, customer, UOM, tax, warehouse, dining table, promotion, dan kitchen station dibuat.
- Helper scope business dan branch dibuat untuk resource Filament.
- Form master data mengisi tenant aktif secara otomatis.
- Form/table dining table, promotion, dan kitchen station dibuat manual agar CRUD langsung usable.
- Dokumentasi Tahap 70 dibuat.

### Tahap 71 - Filament Inventory, Purchasing, and Production Resources

Status: complete.

- Filament resource recipe, stock balance, stock adjustment, stock opname, stock transfer, dan production order dibuat.
- Filament resource purchase order, goods receipt, purchase return, supplier payable, dan supplier payment dibuat.
- Resource operasional dikelompokkan ke menu Inventory, Production, dan Purchasing.
- Form header dokumen memakai tenant otomatis dan dropdown relasi agar tidak perlu input ID mentah.
- Admin route Filament bertambah menjadi 66 route.
- Dokumentasi Tahap 71 dibuat.

### Tahap 72 - Filament Help Page

Status: complete.

- Halaman Help / Cara Pakai ditambahkan ke Filament.
- Menu Support > Help tersedia di `/admin/help`.
- Panduan login, maintenance lokal, master data, menu back office, dan catatan migration ditulis di halaman Help.
- Dokumentasi Tahap 72 dibuat.

### Tahap 73 - Filament Business and Branch Resources

Status: complete.

- CRUD Businesses ditambahkan untuk pengaturan perusahaan/pusat.
- CRUD Branches ditambahkan untuk pengaturan outlet/cabang.
- Kedua resource masuk navigation group Administration.
- Audit CRUD Filament yang sudah ada dan belum dibuat ditulis di dokumentasi tahap.
- Dokumentasi Tahap 73 dibuat.

### Tahap 74 - Branch Specific Product and Category

Status: complete.

- Kategori dan produk diberi `branch_id`.
- Unique slug kategori dan SKU/barcode produk menjadi per business + branch.
- API master data dan POS memfilter kategori/produk sesuai branch aktif.
- Filament Categories dan Products pada tahap ini masih memakai branch aktif sebagai default awal.
- Validasi POS, inventory, purchasing, dan production diperketat agar product sesuai branch transaksi.
- Dokumentasi Tahap 74 dibuat.

### Tahap 75 - Filament Branch Selector CRUD Audit

Status: complete.

- Audit CRUD Filament branch-level dilakukan.
- Branch selector per form sempat dicoba untuk Product, Category, dan dokumen branch-level.
- Hasil evaluasi: pola final dipindahkan ke context selector di Tahap 76 agar user tidak memilih Branch berulang di setiap form.
- Dokumentasi Tahap 75 dibuat sebagai catatan keputusan desain.

### Tahap 76 - Spatie RBAC and Context Selection

Status: complete.

- Dependency `spatie/laravel-permission` ditambahkan.
- Model Role dan Permission dipindahkan ke basis Spatie dengan model custom KAWI.
- Migration kompatibilitas Spatie dibuat tanpa menghapus tabel RBAC lama.
- Data role-permission dan user-role lama dicopy ke `role_has_permissions` dan `model_has_roles`.
- User memakai trait `HasRoles` Spatie dengan pivot tambahan `business_id` dan `branch_id`.
- Endpoint `GET /api/auth/contexts` dan `POST /api/auth/context` dibuat.
- Login dan `/api/auth/me` mengembalikan context business/branch yang boleh dipilih.
- Tenant resolver memakai current branch user sebagai fallback jika header branch tidak dikirim.
- Admin branch hanya bisa memilih branch sesuai role assignment; owner/super admin bisa memilih context yang lebih luas.
- Halaman Filament `Administration > Context` ditambahkan untuk switch business/branch back office.
- Form Filament branch-level kembali otomatis mengikuti branch context aktif.
- Dokumentasi Tahap 76 dibuat.

### Tahap 77 - Initial Master Data Seeders

Status: complete.

- Kolom `code` dan `type` ditambahkan ke `businesses`.
- Pivot `outlet_user` ditambahkan untuk relasi user ke outlet/branch.
- Seeder awal dibuat terpisah: RolePermissionSeeder, BusinessSeeder, OutletSeeder, dan DefaultUserSeeder.
- DatabaseSeeder memanggil seed awal dalam urutan role/permission, business, outlet, lalu user.
- Seed awal membuat 3 business, 3 outlet, 5 role Spatie, 8 permission, dan default owner user.
- Owner user dihubungkan ke semua business melalui `business_user` dan semua outlet melalui `outlet_user`.
- Seeder aman dijalankan berulang tanpa menggandakan data.
- Data demo produk/inventory/sales lama hanya dipanggil saat environment `testing`, bukan saat seed normal.
- Dokumentasi Tahap 77 dibuat.

### Tahap 78 - Active Business and Outlet Context

Status: complete.

- Endpoint `GET /api/me/context-options` dibuat untuk menampilkan business/outlet yang accessible oleh user.
- Endpoint `POST /api/me/active-context` dibuat untuk menyimpan active business/outlet ke session.
- Endpoint `GET /api/me/active-context` dan `DELETE /api/me/active-context` dibuat untuk membaca dan menghapus context aktif.
- Middleware `active.business` dan `active.outlet` ditambahkan.
- Service `ActiveContext` dibuat untuk membaca active business/outlet dari session.
- Validasi context memakai `business_user`, `outlet_user`, status aktif, dan ownership outlet terhadap business.
- Guidance route masa depan ditulis: master data business-scoped memakai `active.business`, operasional outlet-scoped memakai `active.outlet`.
- Dokumentasi Tahap 78 dibuat.

### Tahap 79 - Filament Active Business and Outlet Context

Status: complete.

- Pengembangan frontend Vue/Nuxt dipause; arah proyek difokuskan ke Laravel + Filament.
- Halaman Filament `ManageActiveContext` dibuat untuk memilih active business dan active outlet.
- Menu `Administration > Active Business / Outlet` ditambahkan.
- Pilihan business hanya berasal dari `business_user`; pilihan outlet hanya berasal dari `outlet_user`.
- Outlet dropdown difilter berdasarkan business yang dipilih dan hanya outlet aktif yang muncul.
- Context Filament disimpan di session `active_business_id` dan `active_outlet_id`.
- Middleware Filament memastikan user sudah memiliki context valid setelah login.
- Context invalid dibersihkan dari session dan user diarahkan ulang ke halaman context.
- Helper `ActiveContext` ditambah method `clear()`.
- Filament `TenantContext` membaca active context session agar resource otomatis memakai business/outlet aktif.
- Resource `Businesses` dibatasi ke business yang accessible oleh user.
- Dokumentasi Tahap 79 dibuat.

### Tahap 80 - Modern Accounting Foundation

Status: complete.

- Fondasi accounting modern dibuat dengan arah Laravel + Filament first.
- COA/accounts discope per business, sedangkan outlet/branch dipakai sebagai dimensi accounting.
- Migration additive menambahkan `is_cash_account`, `is_system`, soft delete account, `outlet_id`, `entry_date`, `reference_no`, dan `journal_entry_lines`.
- Tabel `outlet_account_mappings` dibuat untuk mapping kas/bank/QRIS/selisih kas per outlet.
- Model accounting diberi guard agar parent account, mapping account, dan journal line tidak lintas business.
- Resource Filament Accounting ditambahkan: Chart of Accounts, Outlet Account Mappings, dan Journal Entries.
- Journal entry draft dapat dibuat dan hanya bisa dipost jika debit-credit valid dan balance.
- `AccountSeeder` dibuat untuk COA modern dan default mapping outlet di semua business existing.
- Service accounting lama dibuat kompatibel dengan kode dan tipe akun modern.
- Dokumentasi Tahap 80 dibuat.

## Cara Track Mundur

- Setiap tahap disimpan dalam commit terpisah.
- Dokumentasi tahap berada di `docs/`.
- Test harus lulus sebelum commit tahap dibuat.
- Untuk melihat perubahan tahap tertentu:

```bash
git log --oneline
git show <commit>
```
