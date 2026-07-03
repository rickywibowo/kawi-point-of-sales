# Tahap 33 - Frontend Module Navigation Foundation

## Tujuan

Mengubah dashboard dari satu halaman ringkasan panjang menjadi shell aplikasi yang mulai punya navigasi modul dan workspace aktif.

## Frontend

- State `activeModule` ditambahkan di `App.vue`.
- Sidebar `Modul POS` berubah dari tombol statis menjadi selector modul.
- Modul awal:
  - Kasir
  - Produk
  - Inventori
  - Purchasing
  - Accounting
  - Laporan
  - Pelanggan
  - Pengaturan
- Workspace module ditambahkan di area utama.
- Workspace menampilkan summary dan baris data sesuai module aktif.
- Quick summary lama tetap dipertahankan sebagai dashboard overview.

## Dampak

- User bisa mulai berpindah konteks modul tanpa meninggalkan dashboard.
- Struktur ini menjadi dasar untuk tahap berikutnya: halaman modul dengan form, table view, dan action nyata.

## Verifikasi

- `npm run build` memastikan template dan computed state Vue valid.
