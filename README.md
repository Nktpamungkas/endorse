# Endorse Tracker

Aplikasi web untuk pencatatan dan tracking kampanye endorsement TikTok / Instagram.
Dibangun dengan Laravel 12 + React/Inertia + SQLite.

## Alur Kerja

1. Deal masuk.
2. Pembelian atau tunggu produk datang.
3. Pembuatan draft (opsional storyline).
4. Checklist upload ke Google Drive.
5. Revisi berulang sampai approve.
6. Menunggu posting dan konfirmasi sudah posting.
7. Insight minimal H+7 setelah posting.
8. Menunggu payment sampai lunas.
9. Hitung laba bersih (pendapatan - modal).

## Fitur

- Login single user (tanpa registrasi).
- Dashboard ringkasan status, reminder insight, dan overdue payment.
- CRUD data endorse dengan workflow 9 tahap.
- Histori revisi per endorse.
- Pencatatan finansial (fee, reimburse, modal produk, laba bersih).
- Cashflow tambahan (pemasukan & pengeluaran di luar endorse).
- Halaman saldo & total modal.
- Export CSV.
- Database backup otomatis terjadwal.
- Multi-user dengan role master / trial / paid.

## Login Default

```
Username : dhedhepratiwi
Password : dhedhepratiwi
```

Bisa diubah lewat `.env`:

```env
SINGLE_AUTH_USERNAME=...
SINGLE_AUTH_PASSWORD=...
```

---

## Setup Lokal (Windows)

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Konfigurasi `.env` untuk SQLite:

```env
DB_CONNECTION=sqlite
DB_DATABASE=C:/path/ke/endorse/database/database.sqlite
```

```bash
touch database/database.sqlite
php artisan migrate
php artisan storage:link
npm install
npm run dev
php artisan serve
```

Buka `http://localhost:8000`.

---

## Deploy: STB Indihome HG680 (Armbian + FrankenPHP)

Server pribadi pengganti VPS. STB Indihome yang sudah di-flash Armbian,
diakses publik lewat domain `nale-hanan.my.id` menggunakan FrankenPHP + Cloudflare Tunnel.

### Spesifikasi

| Item | Detail |
|---|---|
| Hardware | STB Indihome **HG680** (Amlogic, arm64/aarch64) |
| OS | Armbian — Ubuntu 20.04 focal, kernel 5.9 arm64 |
| RAM | 1.8 GB (+ swap 929 MB) |
| PHP | 8.5.7 (dibawa oleh FrankenPHP) |
| Web server | FrankenPHP v1.12.4 |
| Database | SQLite |
| Akses publik | Cloudflare Tunnel → `https://nale-hanan.my.id` |

### Masalah yang Pernah Dihadapi

#### 1. SSH dari laptop tidak bisa (AP Isolation)
- **Gejala:** STB (LAN) dan laptop (WiFi) tidak bisa saling ping meski satu router.
- **Penyebab:** Router ZTE F670L mengisolasi segmen LAN dari WiFi.
- **Solusi:** Pindahkan STB ke WiFi yang sama dengan laptop.

```bash
nmcli device wifi connect "NamaWifi" password "PASSWORD"
ip addr show wlan0
```

#### 2. PHP 8.x tidak tersedia di apt (arm64)
- **Gejala:** Ubuntu focal hanya menyediakan PHP 7.4. PPA ondrej/php tidak ada paket arm64.
- **Solusi:** Pakai FrankenPHP — satu binary yang sudah memuat PHP 8.5 + semua ekstensi.

#### 3. FrankenPHP `php-cli` tidak menerima flag
- **Gejala:** `php -v` error `Failed opening required '-v'`.
- **Solusi:** Buat wrapper `/usr/local/bin/php` yang membuang flag PHP.

#### 4. `composer install` gagal di `package:discover`
- **Gejala:** `Invalid working directory specified, allow_url_fopen=1 does not exist.`
- **Solusi:** Pakai `--no-scripts`, lalu jalankan `php artisan package:discover` manual.

### Langkah Setup dari Nol

#### A. Tools dasar
```bash
rm -f /etc/apt/sources.list.d/php.list
apt update && apt install -y git unzip curl
```

#### B. Node.js 22
```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt install -y nodejs
```

#### C. FrankenPHP
```bash
curl -L https://github.com/dunglas/frankenphp/releases/latest/download/frankenphp-linux-aarch64 \
  -o /usr/local/bin/frankenphp
chmod +x /usr/local/bin/frankenphp
```

#### D. Wrapper `php`
```bash
cat > /usr/local/bin/php <<'EOF'
#!/bin/sh
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

> `php -v` akan error — itu normal. Yang penting `php <file>` jalan.

#### E. Composer
```bash
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
frankenphp php-cli /tmp/composer-setup.php
mv composer.phar /usr/local/bin/composer
```

#### F. Clone & install
```bash
cd /opt
git clone https://github.com/Nktpamungkas/endorse.git
cd endorse

composer install --no-scripts
php artisan package:discover --ansi
```

#### G. Konfigurasi `.env` + SQLite
```bash
cp .env.example .env
php artisan key:generate

sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
sed -i 's#^DB_DATABASE=.*#DB_DATABASE=/opt/endorse/database/database.sqlite#' .env
sed -i '/^DB_HOST=/d; /^DB_PORT=/d; /^DB_USERNAME=/d; /^DB_PASSWORD=/d; /^DB_ENCRYPT=/d; /^DB_TRUST_SERVER_CERTIFICATE=/d' .env

mkdir -p database && touch database/database.sqlite
php artisan migrate --force
```

#### H. Build frontend
```bash
npm install
npm run build
```

#### I. Test
```bash
frankenphp php-server --root /opt/endorse/public --listen :8000 &
curl -I http://localhost:8000
```

### Cloudflare Tunnel (Akses Publik)

```bash
# Install cloudflared
cd /tmp
curl -L https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm64.deb \
  -o cloudflared.deb
dpkg -i cloudflared.deb

# Login & buat tunnel
cloudflared tunnel login
cloudflared tunnel create endorse

# Config
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

# Routing DNS
cloudflared tunnel route dns endorse nale-hanan.my.id
```

### Service (Auto-start)

**FrankenPHP:**
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

**Cloudflared:**
```bash
cloudflared service install
systemctl enable --now cloudflared
```

Cek status:
```bash
systemctl status endorse cloudflared --no-pager
```

---

## Operasional Sehari-hari

### Update Aplikasi (setelah push ke GitHub)
```bash
cd /opt/endorse
git pull
composer install --no-scripts
php artisan package:discover --ansi
php artisan migrate --force
npm install && npm run build
systemctl restart endorse
```

### Cek Log
```bash
journalctl -u endorse -f       # log Laravel realtime
journalctl -u cloudflared -f   # log tunnel realtime
```

### Restart
```bash
systemctl restart endorse
systemctl restart cloudflared
```

### Bersihkan Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Backup Database Manual
```bash
cp /opt/endorse/database/database.sqlite /root/backup-$(date +%F).sqlite
```

---

## Catatan Penting

- **IP WiFi STB (`192.168.1.12`) masih dynamic** — bisa berubah saat reboot. Set static IP di NetworkManager untuk akses lokal/SSH yang stabil.
- **`php -v` error itu normal** — wrapper sengaja tidak meneruskan flag telanjang.
- **Selalu `composer install --no-scripts`** lalu `php artisan package:discover` manual.
- **Database SQLite** ada di `/opt/endorse/database/database.sqlite`. Backup file ini = backup seluruh data.
- Konsumsi RAM idle: cloudflared ~24MB, FrankenPHP ~58MB.

---

## Arsitektur

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
