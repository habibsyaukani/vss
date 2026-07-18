#!/bin/bash
# ==============================================================================
# SCRIPT TARIK DATA GPS MANUAL VIA SSH (BACKGROUND / NOHUP)
# Menghindari Gateway Timeout 504 pada Web Browser
# ==============================================================================

# Cek input tanggal
if [ -z "$1" ]; then
    echo "=========================================================="
    echo "❌ ERROR: Masukkan tanggal yang mau ditarik!"
    echo "Gunakan format: YYYY-MM-DD"
    echo "Contoh:"
    echo "  ./pull_gps_nohup.sh 2026-07-01"
    echo "=========================================================="
    exit 1
fi

DATE=$1
LOG_FILE="pull_gps_${DATE}.log"

echo "=========================================================="
echo "🚀 MEMULAI PENARIKAN DATA GPS"
echo "Tanggal: $DATE"
echo "Log file: $LOG_FILE"
echo "=========================================================="
echo "Menjalankan command di background (nohup)..."

# Tarik data secara background tanpa terputus jika SSH ditutup
nohup docker exec idle-monitor-app php artisan vss:pull-gps-tracks --date=$DATE --devices=all --limit=0 > $LOG_FILE 2>&1 &

PID=$!

echo ""
echo "✅ BERHASIL DIJALANKAN!"
echo "Proses berjalan di background dengan PID: $PID"
echo ""
echo "🔍 CARA CEK PROGRESS:"
echo "Jalankan perintah ini untuk melihat proses secara live:"
echo "  tail -f $LOG_FILE"
echo ""
echo "💡 (Tekan Ctrl+C untuk keluar dari tampilan log, proses akan tetap berjalan)"
echo "=========================================================="
