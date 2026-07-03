# Tahap 35 - Frontend Action Drawer & Form Foundation

## Tujuan

Menyiapkan pola form/action di workspace modul agar tombol action tidak lagi pasif dan bisa menjadi dasar submit API pada tahap berikutnya.

## Frontend

- State `activeAction`, `actionDraft`, dan `actionFeedback` ditambahkan.
- Toolbar action modul sekarang membuka drawer/form konteks.
- Field form berubah sesuai action yang dipilih.
- Perpindahan modul menutup drawer dan reset pencarian.
- Tombol `Save Draft` menampilkan feedback lokal bahwa draft siap disambungkan ke API.

## Cakupan Action

- POS: New Sale, Hold Cart, Open Shift
- Produk: New Product, Import CSV, Price Update
- Inventori: Stock Opname, Transfer Stock, Production
- Purchasing: New PO, Goods Receipt, Pay Supplier
- Accounting: New Journal, Settlement, Import Provider
- Reports: Refresh, Export, Print
- Customers: New Customer, Loyalty, Segment
- Settings: Invite User, Assign Role, Audit

## Catatan

Tahap ini belum melakukan submit API. Fokusnya adalah struktur drawer, field, dan state draft yang akan dipakai saat action disambungkan ke endpoint backend.
