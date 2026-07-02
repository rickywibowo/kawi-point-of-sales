# Tahap 19 - Operational Expenses

Status: complete.

## Tujuan

Tahap ini menambahkan fondasi pencatatan pengeluaran operasional outlet agar biaya harian seperti listrik, packaging, kebersihan, dan kebutuhan kas kecil dapat masuk ke accounting secara otomatis.

## Backend

- Tabel `operational_expenses` dibuat untuk menyimpan expense per business dan branch.
- Model `OperationalExpense` dibuat dengan relasi ke akun expense dan akun kas.
- Request validation `StoreOperationalExpenseRequest` dibuat.
- Service `OperationalExpenseService` dibuat untuk posting expense.
- Endpoint `POST /api/operational-expenses` dibuat dengan permission `accounting.manage`.
- Posting expense otomatis membuat journal entry:
  - Debit akun beban.
  - Kredit akun kas/bank.
- Audit log `expense.posted` dibuat setelah expense berhasil diposting.

## Accounting & Reports

- Accounting index mengembalikan `operational_expenses` terbaru.
- Report dashboard mengembalikan ringkasan expense:
  - `expense_count`
  - `total`
  - `by_category`
- Profit & loss dan cash flow ikut terdampak lewat journal entry yang dibuat otomatis.

## Frontend

- Store accounting menambahkan data demo operational expense.
- Dashboard accounting menampilkan total operational expense dan expense terakhir.

## Validasi

- Expense account harus berasal dari business aktif.
- Expense account harus bertipe `expense` atau `cost_of_goods_sold`.
- Cash account harus berasal dari business aktif dan bertanda `is_cash`.
- Amount wajib lebih besar dari 0.

## Automated Test

- User dapat posting operational expense dan auto journal terbentuk.
- Expense account dari business lain ditolak.
- Accounting index memuat blok `operational_expenses`.
