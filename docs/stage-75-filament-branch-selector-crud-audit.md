# Stage 75 - Filament Branch Selector CRUD Audit

Status: complete.

## Ringkasan

Tahap ini adalah audit awal CRUD Filament yang memakai `branch_id`. Pada tahap ini branch selector sempat dibuka di form create/edit agar admin pusat bisa memilih cabang saat membuat data. Setelah evaluasi berikutnya, pola final dipindahkan ke Stage 76: user memilih business/branch sebagai context session, lalu form CRUD otomatis mengikuti context tersebut.

## Perubahan

- Audit menemukan semua form branch-level yang masih perlu konsisten dengan scope cabang.
- Branch selector per form dicoba sebagai solusi awal.
- Catatan lanjutan: solusi final adalah context selector di Stage 76 agar Product, Category, dan CRUD branch-level tidak perlu memilih Branch berulang.

## Cara Pakai

1. Buka `Administration > Branches` untuk membuat cabang.
2. Lihat Stage 76 untuk flow final pemilihan business/branch context.
3. Setelah context dipilih, buka `Master Data > Categories` atau `Master Data > Products`; data otomatis mengikuti cabang aktif.

## Catatan

- API kasir dan dashboard tetap memakai branch aktif dari user/session.
- Stage 76 menggantikan branch selector per form dengan context selector yang lebih disiplin.
- CRUD item detail dokumen masih bisa ditingkatkan dengan Relation Manager pada tahap lanjutan.

## Verifikasi

```bash
php artisan test
php artisan route:list --path=admin/products
php artisan route:list --path=admin/categories
```
