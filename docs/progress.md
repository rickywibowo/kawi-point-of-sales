# KAWI POS Progress Log

## 2026-07-02

### Git

- Repository diinisialisasi.
- Commit baseline Tahap 1: `b2d244f chore: scaffold kawi pos foundation`.

### Tahap 1 - Fondasi

- Laravel + Vue 3 + Vite siap.
- Sanctum API auth siap.
- Pinia siap.
- Multi-business, multi-branch, RBAC, audit log dasar siap.
- Middleware tenant dan permission siap.
- Test fondasi lulus.

### Tahap 2 - Master Data

Status: complete.

- Schema unit of measure, tax, kategori/subkategori, supplier, customer, produk, harga produk per cabang, varian, modifier group, dan modifier dibuat.
- Model dan relationship master data dibuat.
- Service layer `MasterDataService` dibuat untuk create kategori dan produk.
- Request validation untuk kategori dan produk dibuat.
- API endpoint master data dibuat.
- Seeder master data demo dibuat.
- Vue Pinia store master data dibuat.
- Automated test master data dibuat.

### Tahap 3 - Recipe dan Inventory

Status: in progress, implementasi awal selesai.

- Schema recipe, recipe item, unit conversion, warehouse, stock ledger, stock balance, adjustment, opname, dan transfer dibuat.
- Model dan relationship inventory dibuat.
- Service layer `InventoryService` dibuat untuk create recipe dan post stock adjustment.
- Request validation untuk recipe dan stock adjustment dibuat.
- API endpoint inventory dibuat.
- Seeder inventory demo dibuat.
- Vue Pinia store inventory dibuat.
- Automated test inventory dibuat.

## Cara Track Mundur

- Setiap tahap disimpan dalam commit terpisah.
- Dokumentasi tahap berada di `docs/`.
- Test harus lulus sebelum commit tahap dibuat.
- Untuk melihat perubahan tahap tertentu:

```bash
git log --oneline
git show <commit>
```
