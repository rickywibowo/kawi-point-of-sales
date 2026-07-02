# Tahap 21 - Customer Loyalty Ledger

Status: complete.

## Tujuan

Tahap ini menambahkan ledger poin loyalty agar perubahan poin pelanggan dapat dilacak, diaudit, dan otomatis bertambah dari transaksi POS.

## Backend

- Tabel `customer_loyalty_transactions` dibuat.
- Model `CustomerLoyaltyTransaction` dibuat.
- Model `Customer` menambahkan relasi `loyaltyTransactions`.
- Request validation `StoreLoyaltyTransactionRequest` dibuat.
- Endpoint `POST /api/customers/{customer}/loyalty-transactions` dibuat dengan permission `sales.create`.
- Customer profile mengembalikan `loyalty_transactions` terbaru.

## Loyalty Logic

- Manual adjustment bisa menambah atau mengurangi poin.
- Balance poin tidak boleh menjadi negatif.
- POS sale dengan customer otomatis membuat loyalty earn.
- Rule earn awal: `floor(grand_total / 10000)`.
- Auto earn memakai source `Sale::class` dan `source_id` sale agar tidak double post.
- Audit log `customer.loyalty_adjusted` dibuat untuk setiap perubahan poin.

## Frontend

- Store customer menambahkan demo loyalty transactions.
- Dashboard customer menampilkan total poin dan loyalty transaction terakhir.

## Automated Test

- Cashier dapat adjust loyalty points customer.
- Adjustment yang membuat balance negatif ditolak.
- Completed sale dengan customer otomatis membuat loyalty earn.
