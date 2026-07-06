# Stage 74 - Branch Specific Product and Category

Status: complete.

## Ringkasan

Tahap ini mengubah katalog produk dan kategori agar bisa berbeda per cabang. Ini mendukung skenario satu perusahaan memiliki cabang dengan jenis usaha berbeda, misalnya cabang restoran dan cabang retail.

## Perubahan

- Menambahkan `branch_id` ke tabel `categories`.
- Menambahkan `branch_id` ke tabel `products`.
- Data kategori dan produk lama otomatis ditempel ke cabang pertama dalam business saat migration dijalankan.
- Unique constraint kategori berubah dari `business + slug` menjadi `business + branch + slug`.
- Unique constraint produk berubah dari `business + sku/barcode` menjadi `business + branch + sku/barcode`.
- API master data hanya mengembalikan kategori, produk, dan kitchen station untuk branch aktif.
- API POS hanya menampilkan produk branch aktif.
- Validasi sale menolak produk dari cabang lain.
- Validasi create category/product menempelkan data ke branch aktif.
- Validasi inventory, purchasing, dan production diperketat agar product sesuai branch transaksi.
- Filament Categories dan Products sekarang otomatis scoped ke branch aktif.
- Dropdown kategori, produk, kitchen station, stock balance, recipe, dan production order difilter sesuai branch aktif.
- Seeder demo mengisi kategori dan produk dengan `branch_id`.

## Cara Pakai

1. Buka `Administration > Branches` untuk membuat cabang/outlet.
2. Pastikan user memakai `current_branch_id` cabang yang ingin disetup.
3. Buka `Master Data > Categories` dan buat kategori untuk cabang itu.
4. Buka `Master Data > Products` dan buat produk untuk cabang itu.
5. Produk/kategori cabang lain tidak akan muncul di POS branch aktif.

## Catatan

- `Business` tetap menjadi pemilik data utama.
- `Branch` sekarang menjadi scope katalog untuk kategori dan produk.
- Unit, tax, supplier, dan customer masih business-level.
- Warehouse tetap branch-level dan dapat bertipe `branch`, `central`, atau `production`.

## Verifikasi

```bash
php artisan migrate --force
php artisan test
npm run build
```

Hasil:

- Migration sukses.
- Laravel test sukses: 82 tests, 469 assertions.
- Frontend build sukses.
