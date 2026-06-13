# Dokumentasi Deploy: Laravel 12 di STB Indihome (HG680)

Server pribadi pengganti VPS, jalan di STB Indihome yang sudah di-flash Armbian.
Aplikasi **Endorse Tracker** (Laravel 12 + React/Inertia + SQLite) diakses publik
lewat domain `nale-hanan.my.id` menggunakan **FrankenPHP** + **Cloudflare Tunnel**.

---

## Spesifikasi & Kondisi

| Item | Detail |
|---|---|
| Hardware | STB Indihome **HG680** (chipset Amlogic, arm64/aarch64) |
| OS | Armbian — **Ubuntu 20.04 focal**, kernel 5.9 arm64 |
| RAM | 1.8 GB (+ swap 929 MB) |
| PHP | **8.5.7** (dibawa oleh FrankenPHP, bukan dari apt) |
| Web server | **FrankenPHP** v1.12.4 (Caddy bawaan) |
| Database | **SQLite** |
| App | Laravel 12.53.0, frontend React + Vite + Tailwind (Inertia) |
| Akses publik | **Cloudflare Tunnel** → `https://nale-hanan.my.id` |
| Repo | `https://github.com/Nktpamungkas/endorse.git` |

---

## Masalah yang Dihadapi & Solusinya

Catatan penting supaya tidak terulang / mudah debug ke depan.

### 1. SSH dari laptop tidak bisa (AP Isolation)
- **Gejala:** STB (kabel LAN, `192.168.1.100`) dan laptop (WiFi, `192.168.1.9`)
  sama-sama bisa ping gateway, tapi tidak bisa saling ping. SSH `Connection timed out`.
- **Penyebab:** Router ZTE F670L mengisolasi segmen LAN dari WiFi (AP/Client Isolation).
  Akun `user` tidak punya akses untuk mematikannya.
- **Solusi:** STB dipindah ke **WiFi yang sama** dengan laptop (`Wifi.id`), sehingga
  satu segmen → SSH jalan. IP WiFi STB: `192.168.1.12`.

```bash
nmcli device wifi list
nmcli device wifi connect "Wifi.id" password "PASSWORD"
ip addr show wlan0   # cek IP baru
```

> Catatan: pastikan Windows Firewall / Hyper-V (WSL) tidak ikut memblokir, tapi
> inti masalahnya adalah isolation router.

### 2. PHP 8.x tidak tersedia di apt (arm64)
- **Gejala:** Ubuntu focal bawaan cuma PHP 7.4. `apt install php8.3` gagal.
- **Penyebab:**
  - PPA `ondrej/php` **tidak menyediakan paket arm64**.
  - Repo `sury` (Debian) **memblokir Ubuntu** (HTTP `418 I'm a teapot`).
- **Solusi:** Pakai **FrankenPHP** — satu binary yang sudah memuat PHP 8.5 + semua
  ekstensi (termasuk `pdo_sqlite`, `mbstring`, dll). Tanpa apt, tanpa Docker.

### 3. FrankenPHP `php-cli` tidak menerima flag
- **Gejala:** `php -v`, `php -m` error `Failed opening required '-v'`.
- **Penyebab:** `frankenphp php-cli` memperlakukan argumen pertama sebagai **nama file
  script**, bukan flag PHP.
- **Solusi:** Buat wrapper `/usr/local/bin/php` yang membuang flag PHP lalu menjalankan
  script-nya. (Lihat bagian setup di bawah.)

### 4. `composer install` gagal di `package:discover`
- **Gejala:** `Invalid working directory specified, allow_url_fopen=1 does not exist.`
- **Penyebab:** Composer menjalankan `@php` internal dengan flag `-d` yang bocor ke
  artisan (jalur ini tidak lewat wrapper).
- **Solusi:** Jangan biarkan Composer menjalankan script. Pakai `--no-scripts`, lalu
  jalankan `php artisan package:discover` **manual** lewat wrapper.

### 5. Database repo aslinya SQL Server
- `.env.example` pakai `DB_CONNECTION=sqlsrv` (tidak jalan di arm64).
- **Solusi:** override ke `sqlite`.

---

## Langkah Setup Lengkap (dari nol)

### A. Tools dasar
```bash
# Hapus repo PHP yang bermasalah (kalau ada)
rm -f /etc/apt/sources.list.d/php.list
rm -f /etc/apt/sources.list.d/ondrej-ubuntu-php-focal.list

apt update && apt install -y git unzip curl
```

### B. Node.js 22 (untuk build frontend React/Vite)
```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt install -y nodejs
node -v   # v22.x
```

### C. FrankenPHP (PHP + web server, 1 binary)
```bash
curl -L https://github.com/dunglas/frankenphp/releases/latest/download/frankenphp-linux-aarch64 \
  -o /usr/local/bin/frankenphp
chmod +x /usr/local/bin/frankenphp
frankenphp version
```

Cek ekstensi (harus ada `pdo_sqlite`, `mbstring`, dll):
```bash
echo '<?php print_r(get_loaded_extensions());' > /tmp/cek.php
frankenphp php-cli /tmp/cek.php
```

### D. Wrapper `php` (penting!)
Membuat `php` mengarah ke FrankenPHP, sekaligus membuang flag yang tidak didukung
(biar `composer` & `artisan` jalan).
```bash
cat > /usr/local/bin/php <<'EOF'
#!/bin/sh
# Buang opsi PHP (-d key=val, dll), jalankan script-nya saja via FrankenPHP
while [ $# -gt 0 ]; do
  case "$1" in
    -d) shift 2 ;;
    -d*|-n|-q|-c*) shift ;;
    -c) shift 2 ;;
    --) shift; break ;;
    -*) shift ;;
    *) break ;;
  esac
done
exec frankenphp php-cli "$@"
EOF
chmod +x /usr/local/bin/php
hash -r
```

> ⚠️ `php -v` memang akan error — itu normal. Yang penting `php <file>` jalan
> (composer & artisan berupa file).

### E. Composer
```bash
cd /opt/endorse   # nanti, setelah clone
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
frankenphp php-cli /tmp/composer-setup.php
mv composer.phar /usr/local/bin/composer
composer --version
```

### F. Clone & install aplikasi
```bash
cd /opt
git clone https://github.com/Nktpamungkas/endorse.git
cd endorse

# install dependency TANPA menjalankan script (hindari bug -d flag)
composer install --no-scripts
php artisan package:discover --ansi
```

### G. Konfigurasi .env + SQLite
```bash
cp .env.example .env
php artisan key:generate

# override DB ke SQLite
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
sed -i 's#^DB_DATABASE=.*#DB_DATABASE=/opt/endorse/database/database.sqlite#' .env
# bersihkan baris khusus SQL Server
sed -i '/^DB_HOST=/d; /^DB_PORT=/d; /^DB_USERNAME=/d; /^DB_PASSWORD=/d; /^DB_ENCRYPT=/d; /^DB_TRUST_SERVER_CERTIFICATE=/d' .env

# buat file db + migrasi
mkdir -p database && touch database/database.sqlite
php artisan migrate --force
```

### H. Build frontend
```bash
npm install
npm run build   # output ke public/build/
```

### I. Tes jalan
```bash
frankenphp php-server --root /opt/endorse/public --listen :8000 &
curl -I http://localhost:8000   # harus 200 OK
# dari browser dalam jaringan: http://192.168.1.12:8000
```

---

## Cloudflare Tunnel (akses publik)

### 1. Install cloudflared
```bash
cd /tmp
curl -L https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm64.deb \
  -o cloudflared.deb
dpkg -i cloudflared.deb
cloudflared --version
```

### 2. Login (butuh domain sudah aktif di Cloudflare)
```bash
cloudflared tunnel login
# buka URL yang muncul di browser → pilih zona nale-hanan.my.id → Authorize
```

### 3. Buat tunnel + config
```bash
cloudflared tunnel create endorse

mkdir -p /etc/cloudflared
CRED=$(ls /root/.cloudflared/*.json | head -1)
TUNNEL_ID=$(basename "$CRED" .json)
cat > /etc/cloudflared/config.yml <<EOF
tunnel: $TUNNEL_ID
credentials-file: $CRED
ingress:
  - hostname: nale-hanan.my.id
    service: http://localhost:8000
  - service: http_status:404
EOF
```

### 4. Routing DNS
```bash
# kalau record lama ada, hapus dulu di dashboard, atau pakai --overwrite-dns
cloudflared tunnel route dns endorse nale-hanan.my.id
```

---

## Menjadikan Service (auto-start, tahan reboot)

### cloudflared
```bash
cloudflared service install
systemctl enable --now cloudflared
```

### FrankenPHP (Laravel)
```bash
cat > /etc/systemd/system/endorse.service <<'EOF'
[Unit]
Description=Endorse Laravel (FrankenPHP)
After=network.target

[Service]
Type=simple
WorkingDirectory=/opt/endorse
ExecStart=/usr/local/bin/frankenphp php-server --root /opt/endorse/public --listen :8000
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable --now endorse
```

Cek dua-duanya:
```bash
systemctl status endorse cloudflared --no-pager
```

---

## Operasional Sehari-hari

### Cek status & log
```bash
systemctl status endorse cloudflared
journalctl -u endorse -f          # log Laravel realtime
journalctl -u cloudflared -f      # log tunnel realtime
```

### Restart
```bash
systemctl restart endorse         # restart aplikasi
systemctl restart cloudflared     # restart tunnel
```

### Update aplikasi (setelah push ke GitHub)
```bash
cd /opt/endorse
git pull

# PENTING: pakai --no-scripts lalu discover manual (hindari bug -d flag)
composer install --no-scripts
php artisan package:discover --ansi

php artisan migrate --force
npm install && npm run build
systemctl restart endorse
```

### Bersihkan cache Laravel (kalau perlu)
```bash
cd /opt/endorse
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## Catatan / Hal yang Perlu Diingat

- **IP WiFi STB (`192.168.1.12`) masih dynamic** — bisa berubah saat reboot. Untuk
  akses lokal/SSH yang stabil, set static IP di NetworkManager. (Akses publik via
  Cloudflare tidak terpengaruh karena lewat tunnel keluar.)
- **`php -v` error itu normal** — wrapper sengaja tidak meneruskan flag telanjang.
- **Selalu `composer install --no-scripts`** lalu `php artisan package:discover`
  manual, jangan `composer install` polos (akan gagal di `package:discover`).
- **Database SQLite** ada di `/opt/endorse/database/database.sqlite`. Backup file ini
  = backup seluruh data. Contoh backup cepat:
  ```bash
  cp /opt/endorse/database/database.sqlite /root/backup-$(date +%F).sqlite
  ```
- **Konsumsi RAM** saat idle: cloudflared ~24MB, FrankenPHP ~58MB. Ringan.
- Kalau mau pindah/tambah domain, edit `ingress` di `/etc/cloudflared/config.yml`
  lalu `cloudflared tunnel route dns endorse <domain>` dan `systemctl restart cloudflared`.

---

## Arsitektur Singkat

```
Internet
   │
   ▼
nale-hanan.my.id  (DNS Cloudflare → CNAME ke tunnel)
   │
   ▼
Cloudflare Edge
   │  (koneksi keluar dari STB, tidak butuh IP publik)
   ▼
cloudflared (service)  ──►  FrankenPHP :8000 (service)
                                  │
                                  ▼
                           Laravel 12 + React
                                  │
                                  ▼
                           SQLite (database.sqlite)
```

*Dibuat sebagai catatan deploy pribadi. Sesuaikan path/domain bila berubah.*
