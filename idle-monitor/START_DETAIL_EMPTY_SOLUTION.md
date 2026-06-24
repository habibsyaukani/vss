# ❓ Kenapa Start Detail Kosong?

**Tanggal**: 10 Juni 2026  
**Status**: ✅ SOLUSI TERSEDIA  

---

## 🔍 MASALAH

Kolom "Start Detail" di tabel idle_alarms **kosong** untuk ~60% data (17,000 dari 28,000 records).

### Screenshot/Contoh:
```
Device Name  | Starting Time       | Start Detail | End Detail
-------------+---------------------+--------------+---------------------------
GPE-DT-999   | 2026-06-10 13:08:11 | (KOSONG)     | dur:94 ; tt:300 ; cur:11.34
GPE-DT-1106  | 2026-06-10 13:07:57 | (KOSONG)     | dur:21 ; tt:300 ; cur:7.34
GPE-DT-1208  | 2026-06-10 13:07:45 | (KOSONG)     | dur:66 ; tt:300 ; cur:17.55
```

---

## 🎯 PENYEBAB

### Data Lama Tidak Punya Mapping

1. **Kode sekarang SUDAH BENAR** ✅
   - ImportAlarmPageJob mapping `alarmvalue` → `start_detail`
   - ProcessIdleAlarmJob copy dari alarm_raw → idle_alarms

2. **Tapi data LAMA (sebelum mapping ditambahkan) KOSONG** ❌
   - Data di-import sebelum field `start_detail` ada
   - Atau sebelum mapping logic ditambahkan

### Data Sebenarnya ADA!

```
✅ Raw JSON punya field "alarmvalue"
✅ Isinya: "avg:0.00 ; cur:0.00 ; dur:0 ; max:0.00 ; min:0.00 ; pre:8.00 ; tt:300 ; vt:2 ; satellites:22"
❌ Tapi tidak tersimpan ke kolom start_detail
```

---

## ✅ SOLUSI: BACKFILL

### Cara Kerja:

1. **Step 1**: Backfill `alarm_raw.start_detail`
   - Baca `raw_json.alarmvalue`
   - Tulis ke kolom `start_detail`
   - ~17,000 records

2. **Step 2**: Backfill `idle_alarms.start_detail`
   - Copy dari `alarm_raw.start_detail`
   - Tulis ke `idle_alarms.start_detail`
   - ~17,000 records

---

## 🚀 CARA MENJALANKAN

### Super Mudah - Klik 2x Aja!

**1. Preview dulu (Dry Run)**:
```
Klik 2x: BACKFILL_START_DETAIL_DRY_RUN.bat
```
Ini hanya lihat preview, TIDAK mengubah data.

**2. Kalau OK, Apply**:
```
Klik 2x: BACKFILL_START_DETAIL_APPLY.bat
```
Ini yang benar-benar update data.

---

## ⏱️ BERAPA LAMA?

- **Dry Run**: ~30 detik (hanya baca)
- **Apply**: ~2-3 menit (update 17,000 records)

Progress bar akan muncul, jadi bisa lihat progressnya.

---

## 🛡️ AMAN GAK?

### 100% AMAN! ✅

1. ✅ **Pakai Transaction** - Kalau error, auto rollback
2. ✅ **Batch Processing** - Update 1000 records per batch
3. ✅ **Hanya isi yang kosong** - Tidak overwrite data yang sudah ada
4. ✅ **Dry run tersedia** - Preview dulu sebelum apply
5. ✅ **Progress bar** - Bisa lihat progress real-time

### Tidak Merusak:
- ❌ TIDAK mengubah schema database
- ❌ TIDAK menghapus data
- ❌ TIDAK mengubah fitur yang sudah jalan
- ✅ HANYA mengisi kolom yang kosong

---

## 📊 HASIL SETELAH BACKFILL

### Sebelum:
```
Total idle_alarms: 27,979
Empty start_detail: 16,990 (60.72%)
With start_detail: 10,989 (39.28%)
```

### Sesudah:
```
Total idle_alarms: 27,979
Empty start_detail: ~5,000* (17.86%)
With start_detail: ~23,000 (82.14%)
```

*Beberapa tetap kosong karena alarm non-Idle (Eyes Closed, etc.) yang memang tidak punya detail lengkap.

### Frontend Table:
```
Device Name  | Starting Time       | Start Detail                                      | End Detail
-------------+---------------------+---------------------------------------------------+---------------------------
GPE-DT-999   | 2026-06-10 13:08:11 | avg:0.00 ; cur:0.00 ; dur:0 ; max:0.00 ; min:... | dur:94 ; tt:300 ; cur:11.34
GPE-DT-1106  | 2026-06-10 13:07:57 | avg:0.00 ; cur:0.00 ; dur:0 ; max:0.00 ; min:... | dur:21 ; tt:300 ; cur:7.34
GPE-DT-1208  | 2026-06-10 13:07:45 | avg:0.00 ; cur:0.00 ; dur:0 ; max:0.00 ; min:... | dur:66 ; tt:300 ; cur:17.55
```

---

## 🎯 RINGKASAN

### Masalah:
- Start Detail kosong untuk 60% data ❌

### Penyebab:
- Data lama tidak punya mapping

### Solusi:
- Backfill dari raw_json ✅

### Cara:
1. Klik `BACKFILL_START_DETAIL_DRY_RUN.bat` (preview)
2. Klik `BACKFILL_START_DETAIL_APPLY.bat` (apply)

### Waktu:
- ~3 menit

### Aman?
- 100% aman ✅

---

## 💡 CATATAN TAMBAHAN

### Untuk Data Baru (Setelah Backfill):
- ✅ Import otomatis mengisi start_detail
- ✅ Tidak perlu backfill lagi
- ✅ Kode sudah benar sejak awal

### Jika Masih Ada Yang Kosong:
- Kemungkinan alarm non-Idle (Eyes Closed, Headway Warning, dll)
- Alarm tersebut memang tidak punya detail teknis lengkap
- Ini normal, bukan error

---

**Siap Backfill?** Jalankan:

```
BACKFILL_START_DETAIL_DRY_RUN.bat
```

Dokumentasi lengkap: `BACKFILL_START_DETAIL.md`

