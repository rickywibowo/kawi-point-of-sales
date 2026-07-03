# Tahap 28 - Cash Drawer Session Audit

## Tujuan

Menambahkan bukti audit tutup kasir berbasis hitungan denominasi agar selisih kas saat closing shift dapat dilacak, diberi alasan, dan disetujui.

## Backend

- Tabel `cash_drawer_audits` dibuat untuk menyimpan hasil audit per shift.
- Setiap shift hanya boleh memiliki satu drawer audit.
- Model `CashDrawerAudit` dibuat dan direlasikan ke `CashierShift`.
- `CloseShiftRequest` mendukung:
  - `drawer_counts`
  - `drawer_counts.*.denomination`
  - `drawer_counts.*.quantity`
  - `drawer_counts.*.label`
  - `variance_reason`
  - `variance_approved`
- `PosService::closeShift()` menghitung total denominasi dan memastikan nilainya sama dengan `actual_cash`.
- Jika ada selisih kas, `variance_reason` wajib diisi.
- Status audit:
  - `balanced`
  - `variance_pending`
  - `variance_approved`
- Audit log `cash_drawer.audit_created` dicatat saat drawer audit dibuat.

## API

Endpoint existing tetap dipakai:

- `POST /api/cashier-shifts/{shift}/close`

`GET /api/pos` sekarang mengembalikan `cash_drawer_audits` terbaru untuk branch aktif.

## UI

- Pinia POS store menambahkan demo drawer audit.
- Dashboard Post-Sale Controls menampilkan status audit, variance, dan jumlah denominasi.

## Test Coverage

- Closing shift dengan denominasi kas dan approved variance.
- Validasi total denominasi harus sama dengan `actual_cash`.
- Audit log drawer audit.
