# 🛡️ SYSTEM PROTECTION RULES - MANDATORY FOR ALL TASKS

**⚠️ CRITICAL: Read this EVERY time before making ANY change to the codebase**

---

## 🚨 RULE PALING PENTING

```
JANGAN MERUSAK FITUR YANG SUDAH BERJALAN
JANGAN MENGHAPUS DATA YANG SUDAH ADA
JANGAN MENGUBAH FITUR YANG TIDAK DIMINTA
FOKUS HANYA PADA TASK YANG DIMINTA
SEMUA PERUBAHAN HARUS BACKWARD COMPATIBLE
```

---

## 1️⃣ PRIORITAS UTAMA

### ✅ BOLEH DILAKUKAN:
- ✅ Menambah fitur BARU
- ✅ Memperbaiki BUG yang ada
- ✅ Mengoptimalkan PERFORMA
- ✅ Menambah VALIDASI
- ✅ Menambah KEAMANAN
- ✅ Menambah DOKUMENTASI
- ✅ Membuat MIGRATION BARU

### ❌ DILARANG KERAS:
- ❌ Mengubah fitur yang sudah berjalan
- ❌ Menghapus fitur yang sudah berjalan
- ❌ Merusak struktur aplikasi
- ❌ Melakukan refactor besar-besaran
- ❌ Mengubah API endpoint yang sudah ada
- ❌ Menghapus kolom database
- ❌ Mengubah tabel yang sudah ada
- ❌ Menghapus migration lama

---

## 2️⃣ DATABASE PROTECTION - ABSOLUTE

### ❌ DILARANG MUTLAK:

```php
// JANGAN PERNAH LAKUKAN:
Schema::dropIfExists('table_name');  // ❌ DILARANG
Schema::drop('table_name');          // ❌ DILARANG
Schema::truncate('table_name');      // ❌ DILARANG

// Command:
php artisan migrate:fresh             // ❌ DILARANG
php artisan db:wipe                   // ❌ DILARANG
php artisan migrate:reset             // ❌ DILARANG

// Database operations:
DELETE FROM table_name;               // ❌ DILARANG tanpa WHERE
TRUNCATE TABLE table_name;            // ❌ DILARANG
ALTER TABLE table MODIFY COLUMN;      // ❌ DILARANG (ubah tipe)
ALTER TABLE table DROP COLUMN;        // ❌ DILARANG (hapus kolom)
```

### ✅ YANG BOLEH:

```php
// Migration baru untuk perubahan:
Schema::table('table_name', function (Blueprint $table) {
    $table->string('new_column')->nullable();  // ✅ Tambah kolom
    $table->dropColumn('old_column');          // ✅ Hapus kolom (hanya jika tidak digunakan)
});

// Jika perlu ubah tipe data:
Schema::table('table_name', function (Blueprint $table) {
    $table->string('column_name', 255)->change();  // ✅ Dalam migration baru
});

// Data manipulation yang aman:
INSERT INTO table_name VALUES (...);           // ✅ Tambah data
UPDATE table_name SET ... WHERE ...;           // ✅ Ubah data dengan kondisi
DELETE FROM table_name WHERE id = 123;         // ✅ Hapus dengan ID spesifik
```

---

## 3️⃣ EXISTING FEATURE PROTECTION

### SEBELUM MEMBUAT FITUR BARU - ANALISIS:

```markdown
1. Apa yang sudah berjalan?
   - Dashboard ✅
   - Login ✅
   - User Management ✅
   - Device Management ✅
   - Idle Alarm ✅
   - Scheduler ✅
   - Queue System ✅

2. Apa yang sudah digunakan?
   - Frontend ✅
   - Scheduler ✅
   - Queue ✅
   - API ✅
   - Database ✅

3. Jangan mengubah fitur di atas KECUALI diminta eksplisit
```

### FITUR YANG TIDAK BOLEH DISENTUH:

| Fitur | Status | Alasan |
|-------|--------|--------|
| Authentication | 🔒 LOCKED | Production critical |
| Dashboard | 🔒 LOCKED | Live data |
| User Management | 🔒 LOCKED | Security |
| Device Sync | 🔒 LOCKED | External API |
| Alarm Import | 🔒 LOCKED | Production data |
| Queue System | 🔒 LOCKED | Background jobs |
| Scheduler | 🔒 LOCKED | Timing critical |
| Database Schema | 🔒 LOCKED | Data integrity |
| API Endpoints | 🔒 LOCKED | Frontend dependency |

---

## 4️⃣ SCOPE CONTROL - STRICT

### Jika diminta: "Tambahkan menu Device Group"

#### ✅ BOLEH UBAH:
```
app/Http/Controllers/DeviceGroupController.php      ✅
app/Models/DeviceGroup.php                          ✅
database/migrations/*_create_device_groups.php      ✅
resources/views/admin/device-group/                 ✅
routes/admin.php (tambah route Device Group)        ✅
```

#### ❌ JANGAN UBAH:
```
app/Http/Controllers/AuthController.php             ❌
app/Http/Controllers/DashboardController.php        ❌
app/Http/Controllers/UserController.php             ❌
resources/views/admin/dashboard/                    ❌
resources/views/admin/auth/                         ❌
app/Jobs/ImportAlarmJob.php                         ❌
app/Jobs/ProcessIdleAlarmJob.php                    ❌
etc.
```

---

## 5️⃣ FILE PROTECTION CHECKLIST

### Sebelum mengubah file:

```markdown
[ ] Apakah file ini LANGSUNG berhubungan dengan task?
[ ] Apakah file ini sudah digunakan oleh fitur lain?
[ ] Apakah perubahan akan merusak fitur yang ada?
[ ] Apakah ada dependensi yang akan terputus?
[ ] Apakah perubahan backward compatible?

JIKA JAWAB TIDAK untuk pertanyaan 2-4, JANGAN UBAH FILE
```

---

## 6️⃣ API PROTECTION - JANGAN UBAH ENDPOINT

### ❌ DILARANG:

```php
// Jangan ubah endpoint yang sudah ada:
Route::get('/api/idle-alarms', ...);  // ❌ Jangan ubah
Route::get('/api/dashboard', ...);    // ❌ Jangan ubah

// Jangan ubah response structure:
return response()->json([
    'success' => true,
    'data' => [
        'id' => 1,
        'name' => 'Device'
    ]
]);
// ❌ Jangan ubah struktur ini
```

### ✅ BOLEH:

```php
// Tambah endpoint BARU:
Route::get('/api/idle-alarms/new', ...);  // ✅ OK

// Tambah field BARU ke response:
return response()->json([
    'success' => true,
    'data' => [
        'id' => 1,
        'name' => 'Device',
        'new_field' => 'value'  // ✅ Field baru OK
    ]
]);

// Jika perlu ubah struktur:
// Gunakan API versioning:
Route::get('/api/v2/idle-alarms', ...);  // ✅ API baru
```

---

## 7️⃣ QUEUE & SCHEDULER PROTECTION

### JANGAN UBAH JOBS INI:

```php
// ❌ JANGAN DISENTUH:
app/Jobs/ImportAlarmJob.php           // 🔒 Production critical
app/Jobs/ProcessIdleAlarmJob.php      // 🔒 Production critical
app/Jobs/SyncDeviceJob.php            // 🔒 Production critical
app/Jobs/RefreshTokenJob.php          // 🔒 Production critical
app/Console/Kernel.php                // 🔒 Scheduler config

// ❌ JANGAN JALANKAN:
php artisan schedule:work              // ❌ Bisa merusak timing
php artisan queue:work                 // ❌ Bisa interrupt jobs

// Kenapa?
- Sudah berjalan di production
- Berhubungan dengan data real
- Timing-critical
- Bisa menghilangkan data jika diubah
```

---

## 8️⃣ DATA SAFETY - JANGAN HAPUS

### ❌ DILARANG MUTLAK:

```bash
# Jangan jalankan command ini:
php artisan migrate:fresh              # ❌ Hapus semua data
php artisan db:wipe                    # ❌ Wipe database
php artisan migrate:reset              # ❌ Reset migrations

# Jangan jalankan script ini:
DELETE FROM users WHERE 1=1;           # ❌ Hapus semua user
TRUNCATE TABLE idle_alarms;            # ❌ Hapus semua alarm
DROP TABLE devices;                    # ❌ Hapus table
```

### ✅ JIKA PERLU DATA BERSIH:

```bash
# Hubungi admin terlebih dahulu
# Backup data existing
# Jalankan di lingkungan testing saja
# Dokumentasikan alasan perubahan
```

---

## 9️⃣ PRE-IMPLEMENTATION ANALYSIS

### SEBELUM MENULIS KODE - WAJIB ANALISIS:

```markdown
📋 ANALYSIS REQUIRED:

1. Files yang akan diubah:
   - [ ] List semua file

2. Files yang TIDAK akan diubah:
   - [ ] Konfirmasi file existing

3. Database impact:
   - [ ] Apakah ada perubahan schema?
   - [ ] Migration yang diperlukan?

4. API impact:
   - [ ] Apakah ada endpoint baru?
   - [ ] Apakah ada perubahan response?

5. Dependencies:
   - [ ] Apa yang bergantung pada perubahan ini?
   - [ ] Apakah akan merusak yang lain?

6. Risk assessment:
   - [ ] Risk level: GREEN / YELLOW / RED?
   - [ ] Mitigation: Apa yang bisa salah?

7. Testing:
   - [ ] Bagaimana cara test ini?
   - [ ] Unit test diperlukan?
   - [ ] Integration test diperlukan?
```

---

## 🔟 BACKWARD COMPATIBILITY - MANDATORY

### Semua perubahan harus:

```markdown
✅ Backward Compatible
   - Old code masih bisa berjalan
   - Old database masih bisa diakses
   - Old API masih bisa diakses

✅ Non-Breaking
   - Tidak mengganggu fitur existing
   - Tidak mengubah interface
   - Tidak mengubah behavior

✅ Safe to Deploy
   - Bisa langsung deploy ke production
   - Tidak perlu downtime
   - Bisa rollback jika ada masalah

✅ Reversible
   - Bisa di-undo dengan aman
   - Tidak meninggalkan data tercemar
   - Bisa dikembalikan ke state lama
```

---

## CHECKLIST SEBELUM MERANCANG FITUR

```markdown
Setiap kali mulai task baru, cek:

[ ] Task apa yang diminta?
[ ] Fitur apa yang sudah ada?
[ ] Apakah task berhubungan dengan fitur existing?
[ ] File mana saja yang akan diubah?
[ ] File mana saja yang TIDAK boleh diubah?
[ ] Database akan berubah?
[ ] API endpoint akan berubah?
[ ] Akan merusak fitur yang ada?

BARU MULAI KODE SETELAH SEMUA DICEK ✅
```

---

## CHECKLIST SEBELUM MENULIS KODE

```markdown
Before implementation, show:

[ ] 📋 ANALYSIS:
    - Files to modify: [list]
    - Files to NOT touch: [list]
    - Database impact: [analysis]
    - API impact: [analysis]
    
[ ] ⚠️ WARNINGS:
    - Any potential issues?
    - Any risks?
    
[ ] ✅ APPROVAL NEEDED:
    - Is this safe?
    - Any breaking changes?
    
[ ] 📝 IMPLEMENTATION PLAN:
    - Step 1: [what]
    - Step 2: [what]
    - Step 3: [what]
```

---

## CHECKLIST SEBELUM SUBMIT CODE

```markdown
Before submitting code, verify:

[ ] Tidak merusak fitur existing
[ ] Tidak menghapus data
[ ] Tidak mengubah API yang sudah ada
[ ] Tidak mengubah database (hanya migration baru)
[ ] Backward compatible
[ ] Tested (jika ada test)
[ ] Documented (jika ada doc)
[ ] Scope matched (sesuai dengan task)
[ ] No breaking changes

JIKA SEMUA TERCEK ✅, BARU SUBMIT
```

---

## CONTOH GOOD vs BAD

### ❌ BAD - Merusak fitur:

```php
// ❌ Jangan lakukan ini:
// Mengubah response API tanpa diminta
Route::get('/api/idle-alarms', function () {
    return response()->json([
        // ❌ Struktur berubah, frontend bisa rusak!
        'alarms' => [...],  // Sebelumnya: 'data' => [...]
    ]);
});

// ❌ Menghapus kolom:
Schema::table('users', function (Blueprint $table) {
    $table->dropColumn('role');  // ❌ Banyak yang depend
});

// ❌ Mengubah table:
Schema::rename('old_table', 'new_table');  // ❌ Queries rusak
```

### ✅ GOOD - Backward compatible:

```php
// ✅ Tambah endpoint baru:
Route::get('/api/v2/idle-alarms', function () {
    return response()->json([
        'alarms' => [...],
    ]);
});

// ✅ Tambah kolom baru:
Schema::table('users', function (Blueprint $table) {
    $table->string('department')->nullable();  // ✅ Baru, tidak merusak
});

// ✅ Tetap support yang lama:
if (request('version') === 'v2') {
    return new_response();
} else {
    return old_response();  // ✅ Backward compatible
}
```

---

## REFERENCE CHECKLIST

### Task diberikan: "Tambah fitur X"

```markdown
STEP 1 - ANALYZE (5 menit)
[ ] Baca task dengan detail
[ ] Lihat fitur yang sudah ada
[ ] Identifikasi file yang akan diubah
[ ] Check dependencies
[ ] Assess risk level

STEP 2 - DESIGN (5 menit)
[ ] Design solution
[ ] Plan perubahan database (jika ada)
[ ] Plan perubahan API (jika ada)
[ ] Plan perubahan file
[ ] Buat TIDAK ada breaking changes

STEP 3 - SHOW ANALYSIS (1 menit)
[ ] Tampilkan planned changes
[ ] Tampilkan protected files
[ ] Tampilkan risk assessment
[ ] Minta approval jika ada risiko

STEP 4 - IMPLEMENT (varies)
[ ] Ikuti design yang sudah disetujui
[ ] Hanya ubah file yang direncanakan
[ ] Gunakan migration untuk DB
[ ] Test perubahan
[ ] Dokumentasikan

STEP 5 - VERIFY (2 menit)
[ ] Cek tidak ada fitur yang rusak
[ ] Cek tidak ada data yang hilang
[ ] Cek backward compatible
[ ] Cek sesuai dengan task
[ ] Submit dengan confidence
```

---

## QUICK REFERENCE

### When in doubt:
1. Read this file
2. Do the analysis
3. Show the plan
4. Get approval
5. Implement safely
6. Verify nothing broke

### Most important:
```
🛡️ PROTECT existing features
🛡️ PROTECT the database
🛡️ PROTECT the API
🛡️ PROTECT production data
🛡️ PROTECT application integrity
```

### If you break something:
```
STOP immediately
Report the issue
ROLLBACK changes
NEVER cover up
ANALYZE what went wrong
PREVENT it next time
```

---

## 📌 ALWAYS REMEMBER

```
JANGAN MERUSAK FITUR YANG SUDAH BERJALAN
JANGAN MENGHAPUS DATA YANG SUDAH ADA
JANGAN MENGUBAH FITUR YANG TIDAK DIMINTA
FOKUS HANYA PADA TASK YANG DIMINTA
SEMUA PERUBAHAN HARUS BACKWARD COMPATIBLE

RULE INI ADALAH LAW DI PROJECT INI.
TIDAK ADA EXCEPTION.
TIDAK ADA SHORTCUT.
SEMUA HARUS AMAN DAN TERUKUR.
```

---

**Last Updated**: 2026-06-03
**Status**: 🔒 MANDATORY FOR ALL TASKS
**Version**: 1.0

*Baca file ini SEBELUM memulai pekerjaan apapun di project ini.*
