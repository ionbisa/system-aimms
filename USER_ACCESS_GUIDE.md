# User Access Guide

Dokumen ini dipakai sebagai panduan cepat akun default, role, dan akses menu pada aplikasi AIMMS.

## Password Default

Semua akun default menggunakan password awal:

`password123`

Setelah login pertama, disarankan password segera diganti oleh `Master Admin` melalui menu `Setting User`.

## Akun Default

| Nama | Role | Email |
|---|---|---|
| Master Admin | Master Admin | `master@aimms.local` |
| Admin GA | Admin GA | `ga@aimms.local` |
| Admin Produksi | Admin Produksi | `adm-produksi@aimms.local` |
| Agus Kepala Produksi | Kepala Produksi | `agus.kapro@aimms.local` |
| Nanta Kepala Produksi | Kepala Produksi | `nanta.kapro@aimms.local` |
| Dul Kepala Produksi | Kepala Produksi | `dul.kapro@aimms.local` |
| SPV Operasional | SPV Operasional | `acep.spv@aimms.local` |
| Manager Operasional | Manager Operasional | `helmi.mo@aimms.local` |
| Manager Finance | Manager Finance | `jeje.mf@aimms.local` |
| Direktur Operasional | Direktur Operasional | `jaja.dirut@aimms.local` |

## Ringkasan Akses Menu

| Role | Dashboard | Asset Management | Purchase Order | Stock | APD Karyawan | Reporting | Setting User |
|---|---|---|---|---|---|---|---|
| Master Admin | Ya | Ya | Ya | Ya | Ya | Ya | Ya |
| Admin GA | Ya | Ya | Ya | Ya | Ya | Ya | Tidak |
| Admin Produksi | Ya | Tidak | Tidak | Ya | Ya | Ya | Tidak |
| Kepala Produksi | Ya | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak |
| SPV Operasional | Ya | Ya | Ya | Ya | Ya | Ya | Tidak |
| Manager Operasional | Ya | Ya | Ya | Ya | Ya | Ya | Tidak |
| Manager Finance | Ya | Ya | Ya | Ya | Ya | Ya | Tidak |
| Direktur Operasional | Ya | Ya | Ya | Ya | Ya | Ya | Tidak |

## Seeder Yang Perlu Dijalankan

Jika aplikasi akan dipakai untuk go-live, jalankan:

```bash
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=UserSeeder
```

Atau jika ingin lengkap:

```bash
php artisan db:seed
```

## Catatan Operasional

- Menu `Setting User` hanya untuk `Master Admin`.
- Reset password bisa dilakukan dari menu `Setting User`.
- Password baru hanya ditampilkan sekali setelah reset berhasil.
- Jika user belum upload foto, sistem akan menampilkan ikon user default.
