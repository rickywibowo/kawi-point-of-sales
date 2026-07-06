# Stage 75 - Filament Branch Selector CRUD Audit

Status: complete.

## Ringkasan

Tahap ini merapikan CRUD Filament yang memakai `branch_id`. Sebelumnya branch sudah tampil di beberapa listing, tetapi pada form create/edit masih tersimpan sebagai hidden default dari branch aktif. Untuk back office, pola itu menyulitkan setup multi-cabang, terutama jika tiap cabang punya jenis usaha, kategori, dan produk yang berbeda.

## Perubahan

- Menambahkan helper `BranchOptions` untuk dropdown cabang berdasarkan business aktif.
- Mengubah form Categories dan Products agar field Branch tampil saat create/edit.
- Listing Categories dan Products tetap dibatasi business aktif, tetapi tidak lagi dikunci hanya ke branch aktif.
- Dropdown parent category, category product, kitchen station, warehouse, product, purchase order, goods receipt, payable, dan recipe sekarang mengikuti Branch yang dipilih di form.
- Mengubah form branch-level berikut agar Branch tampil di create/edit: Warehouses, Dining Tables, Kitchen Stations, Purchase Orders, Goods Receipts, Purchase Returns, Supplier Payables, Supplier Payments, Stock Balances, Stock Adjustments, Stock Opnames, dan Production Orders.
- Recipe tidak punya `branch_id`, tetapi pilihan Output Product sekarang menampilkan semua produk business dengan label cabang.
- Halaman Filament Help diperbarui agar instruksi setup cabang sesuai perilaku form terbaru.

## Cara Pakai

1. Buka `Administration > Branches` untuk membuat cabang.
2. Buka `Master Data > Categories`, klik Create, lalu pilih Branch yang sesuai.
3. Buka `Master Data > Products`, klik Create, pilih Branch, lalu pilih kategori yang sudah tersaring sesuai cabang.
4. Untuk setup POS cabang, buka Warehouses, Dining Tables, dan Kitchen Stations lalu pilih Branch masing-masing.
5. Untuk dokumen operasional, pilih Branch terlebih dulu agar pilihan warehouse/product/dokumen terkait ikut tersaring.

## Catatan

- API kasir dan dashboard tetap memakai branch aktif dari user/session.
- Filament back office sekarang lebih cocok untuk admin pusat yang mengatur banyak cabang dari satu panel.
- CRUD item detail dokumen masih bisa ditingkatkan dengan Relation Manager pada tahap lanjutan.

## Verifikasi

```bash
php -l app/Filament/**/*.php
php artisan test
php artisan route:list --path=admin/products
php artisan route:list --path=admin/categories
```
