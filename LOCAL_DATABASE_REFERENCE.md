# Acuan Database Lokal AIMMS

File ini dibuat sebagai acuan saat membandingkan database lokal dengan database online di Hostinger.

## Koneksi Lokal

Database lokal yang dipakai:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

File database:

```text
database/database.sqlite
```

## Login Seed Lokal

Semua user seed lokal memakai password:

```text
password123
```

| ID | Nama | Email | Role |
| --- | --- | --- | --- |
| 1 | Master Admin | master@aimms.local | Master Admin |
| 2 | Admin GA | ga@aimms.local | Admin GA |
| 3 | Admin Produksi | adm-produksi@aimms.local | Admin Produksi |
| 4 | Agus Kepala Produksi | agus.kapro@aimms.local | Kepala Produksi |
| 5 | Nanta Kepala Produksi | nanta.kapro@aimms.local | Kepala Produksi |
| 6 | Dul Kepala Produksi | dul.kapro@aimms.local | Kepala Produksi |
| 7 | SPV Operasional | acep.spv@aimms.local | SPV Operasional |
| 8 | Manager Operasional | helmi.mo@aimms.local | Manager Operasional |
| 9 | Manager Finance | jeje.mf@aimms.local | Manager Finance |
| 10 | Direktur Operasional | jaja.dirut@aimms.local | Direktur Operasional |

## Jumlah Data Lokal

| Tabel | Jumlah |
| --- | ---: |
| assets | 3 |
| audit_logs | 0 |
| employee_boots | 0 |
| employee_uniforms | 0 |
| inventories | 0 |
| inventory_transactions | 0 |
| item_requests | 0 |
| item_request_items | 0 |
| item_request_approvals | 0 |
| permissions | 10 |
| purchase_orders | 0 |
| purchase_order_items | 0 |
| purchase_order_approvals | 0 |
| roles | 9 |
| role_has_permissions | 42 |
| model_has_roles | 10 |
| stocks | 0 |
| stock_inbounds | 0 |
| stock_outbounds | 0 |
| users | 10 |
| migrations | 44 |

## Role Lokal

| ID | Role |
| --- | --- |
| 1 | Master Admin |
| 2 | Admin GA |
| 3 | Admin Produksi |
| 4 | Kepala Produksi |
| 5 | Supervisor Operasional |
| 6 | SPV Operasional |
| 7 | Manager Operasional |
| 8 | Manager Finance |
| 9 | Direktur Operasional |

## Permission Lokal

| ID | Permission |
| --- | --- |
| 1 | view dashboard |
| 2 | manage assets |
| 3 | manage inventory |
| 4 | manage stocks |
| 5 | manage item requests |
| 6 | approve item requests |
| 7 | realize item requests |
| 8 | approve purchase |
| 9 | view reports |
| 10 | manage users |

## Asset Seed Lokal

| Kode | Nama | Category | Type | Status | Value |
| --- | --- | --- | --- | --- | ---: |
| AST-001 | Laptop Operasional | Office | Office Assets | active | 15000000 |
| AST-002 | Printer Kantor | Office | Office Assets | maintenance | 5000000 |
| AST-003 | Motor Operasional | Motor | Motorcycles | active | 18000000 |

## Tabel Utama dan Kolom Penting

### users

- `id`
- `name`
- `email`
- `email_verified_at`
- `password`
- `remember_token`
- `profile_photo_path`
- `created_at`
- `updated_at`

### assets

- `id`
- `asset_code`
- `name`
- `category`
- `type`
- `purchase_date`
- `value`
- `status`
- `specification`
- `photo`
- `last_service`
- `next_service`
- `location`
- `nopol`
- `pic`
- `deleted_at`
- `created_at`
- `updated_at`

### stocks

- `id`
- `qty`
- `asset_id`
- `item_code`
- `item_name`
- `specification`
- `location`
- `unit`
- `status`
- `photo`
- `created_at`
- `updated_at`

### stock_inbounds

- `id`
- `item_name`
- `qty`
- `created_at`
- `updated_at`

### stock_outbounds

- `id`
- `item_name`
- `qty`
- `unit`
- `description`
- `created_at`
- `updated_at`

### item_requests

- `id`
- `request_number`
- `requested_at`
- `division`
- `requested_role`
- `overall_status`
- `current_step`
- `initial_note`
- `requested_by`
- `final_approved_at`
- `rejected_at`
- `expired_at`
- `ga_seen_at`
- `realization_status`
- `realization_note`
- `realized_by`
- `realized_at`
- `completed_at`
- `last_action_at`
- `stock_deducted_at`
- `created_at`
- `updated_at`

### item_request_items

- `id`
- `item_request_id`
- `line_number`
- `item_name`
- `qty`
- `unit`
- `description`
- `stock_id`
- `distributed_qty`
- `procurement_type`
- `created_at`
- `updated_at`

### purchase_orders

- `id`
- `po_number`
- `total_price`
- `status`
- `transaction_date`
- `transaction_type`
- `category`
- `description`
- `vendor`
- `qty`
- `unit`
- `unit_price`
- `status_label`
- `photo`
- `division`
- `requested_by`
- `requested_role`
- `overall_status`
- `current_step`
- `initial_note`
- `final_approved_at`
- `rejected_at`
- `expired_at`
- `finance_seen_at`
- `ga_seen_at`
- `realization_status`
- `realization_note`
- `realized_by`
- `realized_at`
- `completed_at`
- `completed_by`
- `receipt_note`
- `receipt_file`
- `actual_total_price`
- `last_action_at`
- `created_at`
- `updated_at`

### purchase_order_items

- `id`
- `purchase_order_id`
- `line_number`
- `item_name`
- `qty`
- `unit`
- `estimated_unit_price`
- `estimated_total_price`
- `description`
- `created_at`
- `updated_at`

### employee_boots

- `id`
- `return_date`
- `expiry_date`
- `employee_name`
- `employee_code`
- `department`
- `boot_size`
- `quantity_given`
- `condition`
- `notes`
- `photo`
- `created_at`
- `updated_at`

### employee_uniforms

- `id`
- `pickup_date`
- `expiry_date`
- `employee_name`
- `employee_code`
- `department`
- `shirt_size`
- `quantity_given`
- `condition`
- `notes`
- `photo`
- `created_at`
- `updated_at`

## Cara Cek Online Jika Error

Di Hostinger/phpMyAdmin, bandingkan hal berikut dengan lokal:

1. Tabel `migrations` harus memuat semua migration yang ada di lokal.
2. Kolom-kolom penting di tabel `purchase_orders`, `item_requests`, `stocks`, dan `assets` harus ada.
3. Role dan permission harus ada, terutama `Master Admin`.
4. Jika error dashboard muncul, cek apakah kolom ini ada:

```text
purchase_orders.overall_status
purchase_orders.actual_total_price
purchase_orders.transaction_date
assets.type
assets.status
stocks.asset_id
stocks.item_name
```

5. Jika error login/akses menu muncul, cek tabel:

```text
users
roles
permissions
model_has_roles
role_has_permissions
```

6. Jika link **Lihat bukti nota** error di Hostinger, cek file nota ada di:

```text
storage/app/public/purchase-orders/receipts
```

Link bukti nota memakai route:

```text
/media/purchase-orders/receipts/nama-file
```

Setelah upload perubahan route/model, jalankan:

```bash
php artisan optimize:clear
php artisan route:cache
```

## Perintah Lokal Untuk Refresh Acuan

```bash
php artisan migrate:fresh --seed
php artisan migrate:status
```

Untuk melihat jumlah data SQLite lokal:

```bash
sqlite3 database/database.sqlite "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;"
```
