@echo off
REM ========================================
REM PULL GPS DATA - 11 JUNI 2026
REM ========================================
REM
REM Script ini akan:
REM 1. Tarik data GPS dari VSS API untuk tanggal 11 Juni 2026
REM 2. Simpan ke gps_tracks_raw
REM 3. Dispatch job untuk proses ke gps_tracks
REM
REM Waktu eksekusi: ~10-30 menit (tergantung jumlah device)
REM ========================================

echo.
echo ========================================
echo   PULL GPS DATA - 11 JUNI 2026
echo ========================================
echo.
echo Target: 2026-06-11 00:00:00 - 23:59:59
echo.
echo PERINGATAN:
echo - Script ini akan tarik data untuk SEMUA device aktif
echo - Proses bisa memakan waktu 10-30 menit
echo - Pastikan koneksi internet stabil
echo.

pause

echo.
echo Starting GPS data pull...
echo.

C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe pull_gps_yesterday.php

echo.
echo ========================================
echo Script selesai!
echo ========================================
echo.

pause
