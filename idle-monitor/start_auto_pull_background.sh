#!/bin/bash
# ==============================================================================
# SCRIPT UNTUK MENGAKTIFKAN AUTO PULL BACKGROUND (IDLE & GPS)
# ==============================================================================

echo "=========================================================="
echo "🚀 MENGAKTIFKAN AUTO PULL DI BACKGROUND"
echo "=========================================================="
echo ""
echo "Sistem akan menggunakan Scheduler dan Queue Worker bawaan Laravel"
echo "untuk menarik data secara otomatis di background tanpa kena limit API."
echo ""
echo "Mengaktifkan container scheduler dan worker..."

# Pastikan berada di direktori project (tempat docker-compose.yml berada)
docker compose up -d scheduler worker

echo ""
echo "✅ AUTO PULL BERHASIL DIAKTIFKAN!"
echo "=========================================================="
echo "Jadwal Penarikan Otomatis:"
echo "1. Idle Alarm : Setiap 3 menit (menarik data 2 jam terakhir)"
echo "2. GPS Track  : Setiap 5 menit (menarik data 1 jam terakhir)"
echo ""
echo "Data akan selalu terupdate tanpa error Gateway Timeout (504)."
echo ""
echo "🔍 CARA CEK STATUS PROSES BACKGROUND:"
echo "Jalankan perintah berikut untuk melihat log proses:"
echo "  docker logs -f idle-monitor-scheduler"
echo "  docker logs -f idle-monitor-worker"
echo "=========================================================="
