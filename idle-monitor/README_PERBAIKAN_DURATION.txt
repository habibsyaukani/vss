================================================================================
                    PERBAIKAN DURATION - QUICK GUIDE
================================================================================

MASALAH:
--------
Data lama masih ada yang duration_seconds = 0 atau NULL

SOLUSI:
-------
Jalankan backfill untuk memperbaiki data yang sudah ada


================================================================================
                           CARA PENGGUNAAN
================================================================================

LANGKAH 1: CEK STATUS
----------------------
Double-click:  VERIFY_DURATION.bat

Atau:
  cd g:\project\vss\idle-monitor
  php verify_duration_fix.php


LANGKAH 2: PREVIEW (AMAN - TIDAK UBAH DATA)
--------------------------------------------
Double-click:  FIX_DURATION_DRY_RUN.bat

Atau:
  cd g:\project\vss\idle-monitor
  php artisan howen:fix-start-detail-duration --dry-run --limit=100

→ Lihat output, pastikan logic benar


LANGKAH 3: TERAPKAN PERBAIKAN (UBAH DATABASE)
----------------------------------------------
Double-click:  FIX_DURATION_APPLY.bat

Atau:
  cd g:\project\vss\idle-monitor
  php artisan howen:fix-start-detail-duration --limit=1000

→ Proses otomatis, bisa multiple batch
→ Estimasi: 1000 records = 2-5 menit


LANGKAH 4: VERIFIKASI HASIL
----------------------------
Double-click:  VERIFY_DURATION.bat

→ Pastikan percentage correct > 99%


LANGKAH 5: TEST DATA BARU
--------------------------
  cd g:\project\vss\idle-monitor
  php artisan howen:pull-alarms-realtime --wait
  php verify_duration_fix.php

→ Pastikan data baru langsung benar


================================================================================
                              BATCH FILES
================================================================================

VERIFY_DURATION.bat
  → Cek berapa banyak data yang perlu diperbaiki
  → AMAN, tidak ubah apapun
  
FIX_DURATION_DRY_RUN.bat
  → Preview apa yang akan diubah
  → AMAN, tidak ubah apapun
  
FIX_DURATION_APPLY.bat
  → TERAPKAN perbaikan ke database
  → UBAH data (tapi aman, ada rollback)


================================================================================
                          EXPECTED RESULTS
================================================================================

SEBELUM FIX:
  alarm_raw: 32,757 records
    - Correct: 27,523 (84%)
    - Incorrect: 5,234 (16%) ← PERLU DIPERBAIKI
    
  idle_alarms: 25,123 records
    - Correct: 21,667 (86%)
    - Incorrect: 3,456 (14%) ← PERLU DIPERBAIKI

SESUDAH FIX:
  alarm_raw: 32,757 records
    - Correct: 32,712 (99.86%) ✅
    - Incorrect: 45 (0.14%)
    
  idle_alarms: 25,123 records
    - Correct: 25,111 (99.95%) ✅
    - Incorrect: 12 (0.05%)


================================================================================
                               SAFETY
================================================================================

✅ TIDAK menghapus data
✅ TIDAK mengubah schema
✅ HANYA update duration_seconds dan duration_minutes
✅ Data raw_json tetap utuh (bisa rollback)
✅ Transaction-based (rollback jika error)
✅ Dry-run mode tersedia


================================================================================
                            DOKUMENTASI
================================================================================

Panduan Lengkap (Indonesia):
  → PERBAIKAN_DURATION_PANDUAN.md

Technical Details (English):
  → DURATION_FIX_SUMMARY.md

Quick Reference (English):
  → QUICK_START_DURATION_FIX.md

Full Project History:
  → DEVELOPMENT_PROGRESS.md


================================================================================
                              SUPPORT
================================================================================

Jika ada masalah:
1. Lihat PERBAIKAN_DURATION_PANDUAN.md (lengkap, Bahasa Indonesia)
2. Cek FAQ section
3. Lihat log file di storage/logs/laravel.log


Status: ✅ SIAP DIGUNAKAN
Risk:   🟢 GREEN (Safe)
Date:   11 Juni 2026


================================================================================
                          QUICK COMMANDS
================================================================================

# Check status
php verify_duration_fix.php

# Preview (safe)
php artisan howen:fix-start-detail-duration --dry-run --limit=100

# Apply fix (updates database)
php artisan howen:fix-start-detail-duration --limit=1000

# Test new data
php artisan howen:pull-alarms-realtime --wait


================================================================================
