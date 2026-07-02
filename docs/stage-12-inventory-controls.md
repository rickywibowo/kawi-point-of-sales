# Tahap 12 - Inventory Controls

Status: complete.

## Tujuan

Tahap ini melengkapi workflow kontrol stok yang sebelumnya baru memiliki header:

- Stock transfer antar warehouse.
- Stock opname dengan variance.
- Item table untuk transfer dan opname.
- Ledger movement untuk setiap perubahan stok.
- Audit log untuk dokumen kontrol stok.

## Database

Migration baru:

- `2026_07_02_000006_create_inventory_control_item_tables.php`

Tabel baru:

- `stock_transfer_items`
- `stock_opname_items`

## Endpoint

```http
POST /api/stock-transfers
POST /api/stock-opnames
```

Keduanya memakai middleware:

- `auth:sanctum`
- `tenant`
- `permission:inventory.adjust`

## Stock Transfer

Transfer membuat dua ledger:

- `transfer_out` di warehouse asal.
- `transfer_in` di warehouse tujuan.

Stock balance asal berkurang dan stock balance tujuan bertambah.

## Stock Opname

Opname membandingkan:

```text
counted_quantity - system_quantity = variance_quantity
```

Jika variance bukan nol, sistem membuat ledger `stock_opname` dan menyesuaikan stock balance.

## Tenant Isolation

Warehouse dan product divalidasi harus berada dalam business aktif.

## Frontend

Store inventory dan dashboard awal menampilkan ringkasan dokumen kontrol stok:

- Transfer terakhir.
- Opname terakhir.
- Jumlah dokumen kontrol.

## Test

Automated test berada di:

- `tests/Feature/Inventory/InventoryTest.php`

Coverage:

- Transfer membuat ledger out/in dan update balance.
- Opname membuat ledger variance dan update balance.
- Transfer menolak warehouse dari business lain.
