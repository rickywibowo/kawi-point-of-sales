# Tahap 34 - Frontend Module Table & Action Foundation

## Tujuan

Meningkatkan workspace modul agar lebih siap menjadi halaman operasional: setiap modul punya pencarian data dan toolbar action awal.

## Frontend

- `moduleSearch` ditambahkan untuk filter baris workspace.
- `filteredModuleRows` dibuat agar pencarian berlaku pada data, info, dan status.
- `moduleActions` dibuat untuk action awal per modul.
- Selector modul sekarang reset pencarian saat berpindah modul.
- Workspace module menampilkan:
  - search input
  - action buttons
  - filtered table rows
  - empty state jika filter tidak menemukan data

## Action Awal

- Kasir: New Sale, Hold Cart, Open Shift
- Produk: New Product, Import CSV, Price Update
- Inventori: Stock Opname, Transfer Stock, Production
- Purchasing: New PO, Goods Receipt, Pay Supplier
- Accounting: New Journal, Settlement, Import Provider
- Laporan: Refresh, Export, Print
- Pelanggan: New Customer, Loyalty, Segment
- Pengaturan: Invite User, Assign Role, Audit

## Catatan

Action button pada tahap ini belum submit ke API. Tujuannya membuat struktur UX dan area kontrol yang akan dipakai tahap form/action nyata berikutnya.
