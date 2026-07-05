# Tahap 69 - Filament Back Office Foundation

Status: complete.

## Ringkasan

Tahap ini memulai pemindahan CRUD back office ke Filament agar master data lebih rapi, terstruktur, dan tidak terlalu bergantung pada form custom Vue.

## Perubahan

- Paket `filament/filament` ditambahkan ke Composer.
- Panel Filament admin dibuat di `/admin`.
- `AdminPanelProvider` didaftarkan di `bootstrap/providers.php`.
- User model mengimplementasikan akses panel Filament.
- Resource `CategoryResource` dibuat untuk CRUD kategori.
- Resource `ProductResource` dibuat untuk CRUD produk.
- Form kategori otomatis memakai business aktif, parent category, slug, sort order, dan status aktif.
- Form produk otomatis memakai business aktif, category, unit, tax, kitchen station, type, harga, stock toggle, dan status aktif.
- Table kategori dan produk mendukung search, sort, filter, edit, dan delete.
- Guard delete kategori dipindahkan ke model agar delete dari API maupun Filament tetap aman.

## Akses

```text
Back office: /admin
Email: owner@kawi.test
Password: password
```

## Catatan Arsitektur

- Filament dipakai untuk back office CRUD: kategori, produk, dan master data berikutnya.
- Vue tetap dipakai untuk layar operasional: kasir, cart, kitchen, table map, delivery, offline sync.
- Ini membuat CRUD lebih terkendali dan mengurangi kompleksitas state custom di Vue.

## Verifikasi

- `php artisan route:list --path=admin`
- `npm run build`
- `php artisan test`
