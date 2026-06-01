# ============================================================
# Script: Download Database Backup dari VPS
# VPS  : naleubuntu@nalehanan
# Path : /var/www/nalehanan/endorse/storage/app/backups/database/
# ============================================================

$VPS_USER    = "naleubuntu"
$VPS_HOST    = "103.197.189.221"
$VPS_PATH    = "/var/www/nalehanan/endorse/storage/app/backups/database"
$LOCAL_DIR   = "$PSScriptRoot\storage\backups-from-vps"

# Buat folder lokal jika belum ada
if (-not (Test-Path $LOCAL_DIR)) {
    New-Item -ItemType Directory -Path $LOCAL_DIR | Out-Null
    Write-Host "Folder dibuat: $LOCAL_DIR"
}

# Pilihan mode
Write-Host ""
Write-Host "========================================"
Write-Host "  Download Backup Endorse dari VPS"
Write-Host "========================================"
Write-Host "1. Download backup TERBARU saja"
Write-Host "2. Download SEMUA backup"
Write-Host "3. Lihat daftar file backup di VPS"
Write-Host "4. Keluar"
Write-Host ""
$choice = Read-Host "Pilih (1/2/3/4)"

switch ($choice) {

    "1" {
        Write-Host ""
        Write-Host "Mengambil nama file terbaru dari VPS..."

        # Ambil nama file terbaru via SSH
        $latestFile = ssh "${VPS_USER}@${VPS_HOST}" "ls -t ${VPS_PATH}/*.sql 2>/dev/null | head -1 | xargs basename"

        if (-not $latestFile -or $latestFile -eq "") {
            Write-Host "Tidak ada file backup ditemukan di VPS." -ForegroundColor Red
            exit 1
        }

        Write-Host "File terbaru: $latestFile"
        Write-Host "Mengunduh ke: $LOCAL_DIR\$latestFile"
        Write-Host ""

        scp "${VPS_USER}@${VPS_HOST}:${VPS_PATH}/${latestFile}" "$LOCAL_DIR\"

        if ($LASTEXITCODE -eq 0) {
            Write-Host ""
            Write-Host "Berhasil diunduh: $LOCAL_DIR\$latestFile" -ForegroundColor Green
        } else {
            Write-Host "Gagal mengunduh file." -ForegroundColor Red
        }
    }

    "2" {
        Write-Host ""
        Write-Host "Mengunduh semua file backup ke: $LOCAL_DIR"
        Write-Host ""

        scp "${VPS_USER}@${VPS_HOST}:${VPS_PATH}/*.sql" "$LOCAL_DIR\"

        if ($LASTEXITCODE -eq 0) {
            Write-Host ""
            Write-Host "Semua backup berhasil diunduh ke: $LOCAL_DIR" -ForegroundColor Green
        } else {
            Write-Host "Gagal mengunduh. Pastikan ada file .sql di VPS." -ForegroundColor Red
        }
    }

    "3" {
        Write-Host ""
        Write-Host "Daftar file backup di VPS:"
        Write-Host "----------------------------------------"

        ssh "${VPS_USER}@${VPS_HOST}" "ls -lh ${VPS_PATH}/*.sql 2>/dev/null | awk '{print \$5, \$9}' | xargs -I{} bash -c 'echo {}' || echo 'Tidak ada file backup.'"

        Write-Host "----------------------------------------"
    }

    "4" {
        Write-Host "Keluar."
        exit 0
    }

    default {
        Write-Host "Pilihan tidak valid." -ForegroundColor Yellow
        exit 1
    }
}

Write-Host ""
Write-Host "Lokasi folder backup lokal: $LOCAL_DIR"
Write-Host ""
