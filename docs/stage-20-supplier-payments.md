# Tahap 20 - Supplier Payable Payments

Status: complete.

## Tujuan

Tahap ini menambahkan workflow pembayaran utang supplier setelah goods receipt membuat `supplier_payables`.

## Backend

- Tabel `supplier_payments` dibuat untuk mencatat pembayaran supplier.
- Model `SupplierPayment` dibuat dengan relasi ke supplier, payable, dan akun kas.
- Model `SupplierPayable` menambahkan relasi `payments`.
- Request validation `PostSupplierPaymentRequest` dibuat.
- Endpoint `POST /api/supplier-payables/{payable}/payments` dibuat dengan permission `purchases.manage`.
- Purchasing index mengembalikan `supplier_payments` dan supplier payables beserta payments.

## Posting Logic

- Pembayaran hanya boleh untuk payable di business dan branch aktif.
- Amount tidak boleh melebihi sisa payable.
- Payment dapat menutup payable penuh atau membuat status `partial`.
- Payable `paid_amount` diperbarui setiap payment berhasil.
- Audit log `supplier_payment.posted` dibuat.

## Accounting

Posting supplier payment otomatis membuat journal entry:

- Debit `2100 - Utang Usaha`.
- Kredit akun kas/bank yang dipilih.

Report purchasing menambahkan `supplier_payment_total` untuk periode aktif.

## Frontend

- Store purchasing menambahkan demo `supplierPayments`.
- Dashboard purchasing menampilkan total supplier payment dan sisa payable.

## Automated Test

- User dapat membayar supplier payable dan menutup balance.
- Payment amount di atas sisa payable ditolak.
- Auto journal supplier payment diverifikasi.
