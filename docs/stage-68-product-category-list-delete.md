# Tahap 68 - Product Category List and Delete

Status: complete.

## Ringkasan

Tahap ini memperbaiki UX product category dengan listing kategori di workspace Produk dan fitur hapus kategori yang aman.

## Perubahan

- Workspace modul Produk sekarang menampilkan kategori beserta `Category ID`.
- Action `Delete Category` ditambahkan ke modul Produk.
- API client menambahkan helper `apiDelete`.
- Backend menambahkan endpoint `DELETE /api/categories/{category}`.
- Service master data menolak hapus kategori yang masih memiliki produk atau child category.
- Automated test ditambahkan untuk hapus kategori kosong dan penolakan kategori yang masih dipakai produk.

## Cara Akses Listing Kategori

1. Login dashboard.
2. Buka modul `Produk`.
3. Listing kategori muncul di tabel workspace sebagai baris `Kategori`.
4. `Category ID` juga tetap terlihat di sidebar Master Data.

## Cara Delete Category

1. Buka modul `Produk`.
2. Klik `Delete Category`.
3. Isi `Category ID`.
4. Simpan.

Kategori hanya bisa dihapus bila belum memiliki produk dan belum memiliki child category.

## Verifikasi

- `npm run build`
- `php artisan test`
