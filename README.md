# Endorse Tracker (Laravel 12 + SQL Server)

Website MVP untuk pencatatan endorse TikTok / Instagram dengan alur:

1. Deal masuk.
2. Pembelian atau tunggu produk datang.
3. Pembuatan draft (opsional storyline).
4. Checklist upload ke Google Drive.
5. Revisi berulang sampai approve.
6. Menunggu posting dan konfirmasi sudah posting.
7. Insight minimal H+7 setelah posting.
8. Menunggu payment sampai lunas.
9. Hitung laba bersih (pendapatan - modal).

## Fitur MVP

- Login single user (tanpa registrasi).
- Dashboard ringkasan status, reminder insight, dan overdue payment.
- CRUD data endorse.
- Histori revisi per endorse.
- Checklist `sudah upload di Google Drive` (tanpa simpan link).
- Boostcode opsional (durasi 7-365 hari).
- Bukti checkout opsional (bisa diisi jika produk dibeli sendiri).
- Pencatatan finansial IDR:
  - `Reimburse duluan`
  - `Reimburse bareng fee`
  - `Free endorse / barter`
  - `N/A (Produk Dikirim Brand)`
  - `N/A (Tidak Ada Produk)`

## Login

- Username: `dhedhepratiwi`
- Password: `dhedhepratiwi`

Nilai ini bisa diubah lewat `.env`:

```env
SINGLE_AUTH_USERNAME=...
SINGLE_AUTH_PASSWORD=...
```

## Setup

1. Install dependency:

```bash
composer install
```

2. Copy environment:

```bash
copy .env.example .env
php artisan key:generate
```

3. Pastikan `.env` memakai SQL Server:

```env
DB_CONNECTION=sqlsrv
DB_HOST=localhost
DB_PORT=
DB_DATABASE=endorse
DB_USERNAME=
DB_PASSWORD=
DB_ENCRYPT=no
DB_TRUST_SERVER_CERTIFICATE=true
SESSION_DRIVER=database
```

4. Buat symbolic link untuk file bukti checkout:

```bash
php artisan storage:link
```

5. Jalankan migrasi:

```bash
php artisan migrate
```

6. Jalankan aplikasi:

```bash
php artisan serve
```

## Catatan SQL Server

- Pastikan PHP extension `pdo_sqlsrv` dan `sqlsrv` aktif di `php.ini`.
- Pastikan SQL Server menerima koneksi TCP (`1433`).
