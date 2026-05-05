# Menjalankan AIMMS Lokal dan Online

Project ini adalah aplikasi Laravel 12. Gunakan satu source code yang sama, lalu bedakan konfigurasi lewat file `.env` di masing-masing tempat.

## Lokal

1. Siapkan PHP 8.2+, Composer, Node.js, dan npm.
2. Copy template lokal:

   ```bash
   cp .env.local.example .env
   ```

3. Buat file database SQLite lokal:

   ```bash
   touch database/database.sqlite
   ```

4. Pastikan `.env` lokal memakai SQLite:

   ```env
   DB_CONNECTION=sqlite
   DB_DATABASE=database/database.sqlite
   ```

5. Install dependency jika belum ada:

   ```bash
   composer install
   npm install
   ```

6. Generate key dan isi tabel:

   ```bash
   php artisan key:generate
   php artisan migrate --seed
   ```

7. Jalankan aplikasi:

   ```bash
   php artisan serve
   npm run dev
   ```

8. Buka `http://127.0.0.1:8000/login`.

User seed lokal memakai password `password123`, misalnya `master@aimms.local`.

## Hostinger

1. Buat database MySQL dari hPanel Hostinger, lalu catat `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, dan `DB_HOST`.
2. Copy `.env.hostinger.example` menjadi `.env` di server.
3. Isi:

   ```env
   APP_URL=https://domain-anda.com
   DB_DATABASE=nama_database_hostinger
   DB_USERNAME=user_database_hostinger
   DB_PASSWORD=password_database_hostinger
   ```

4. Pastikan `APP_ENV=production` dan `APP_DEBUG=false`.
5. Upload source code ke hosting. Jika document root Hostinger mengarah ke folder project atau `public_html`, root `index.php` dan root `.htaccess` di project ini sudah disiapkan untuk menjalankan Laravel.
6. Jalankan perintah berikut dari terminal/SSH Hostinger:

   ```bash
   composer install --no-dev --optimize-autoloader
   npm install
   npm run build
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

7. Buka domain yang sudah diisi pada `APP_URL`.

## Catatan

- Jangan upload `.env` lokal ke Git.
- Untuk lokal, SQLite sudah cukup untuk mengecek tampilan dan mayoritas workflow. Jika ingin meniru Hostinger lebih dekat, pakai MySQL/MariaDB lokal.
- Jika setelah upload muncul error 500, cek `storage/logs/laravel.log` dan pastikan folder `storage` serta `bootstrap/cache` writable.

## Workflow Edit Lokal ke Hostinger

Workflow yang disarankan:

1. Edit source code di komputer lokal.
2. Jalankan dan cek perubahan di lokal:

   ```bash
   php artisan serve
   npm run dev
   ```

3. Jika ada perubahan database, buat migration Laravel, lalu tes lokal:

   ```bash
   php artisan migrate
   ```

4. Build asset production sebelum upload:

   ```bash
   npm run build
   ```

5. Upload perubahan source code ke Hostinger.
6. Di Hostinger, jalankan:

   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

7. Cek domain online.

Jangan edit langsung file utama di Hostinger jika perubahan itu masih perlu dites. Lebih aman edit di lokal, tes, lalu upload ke Hostinger. Kalau terpaksa edit langsung di Hostinger, download dulu file yang berubah ke lokal agar source code lokal tetap sama dengan yang online.

## Sinkron dari Hostinger ke Lokal

Jika aplikasi online sudah punya data terbaru dan ingin dicek lokal:

1. Export database dari phpMyAdmin Hostinger.
2. Import database itu ke MySQL lokal.
3. Download folder upload publik bila diperlukan, terutama:

   ```text
   storage/app/public
   ```

4. Pastikan `.env` lokal tetap memakai database lokal, bukan database Hostinger.

Yang perlu disamakan antara lokal dan online adalah source code, migration, dan struktur data. Yang tidak boleh disamakan mentah-mentah adalah `.env`, karena lokal dan Hostinger punya konfigurasi database/domain yang berbeda.

## Bukti Nota di Hostinger

Link **Lihat bukti nota** memakai route `/media/...`, sehingga tetap bisa menampilkan file dari `storage/app/public` walaupun symlink `/storage` di shared hosting bermasalah.

Jika bukti nota error setelah upload perubahan, jalankan di Hostinger:

```bash
php artisan optimize:clear
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Pastikan file nota tersimpan di:

```text
storage/app/public/purchase-orders/receipts
```
