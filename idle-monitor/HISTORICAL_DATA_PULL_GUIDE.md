# 📚 HISTORICAL DATA PULL GUIDE

**Date**: July 16, 2026  
**Purpose**: Dokumentasi cara tarik data historical (idle alarm & GPS track) via CLI

---

## 🎯 STRATEGY OVERVIEW

### **Realtime Data (Automatic)**
- ✅ Handled by scheduler (runs every 30 min)
- ✅ No manual action needed
- ✅ Always up-to-date for last 24-48 hours

### **Historical Data (Manual via CLI)**
- ✅ Use CLI commands untuk pull data lama
- ✅ Parallel execution untuk speed
- ✅ No timeout issues
- ✅ No web server overhead

---

## 📋 COMMANDS AVAILABLE

### **1. IDLE ALARM DATA**

```bash
# Single day
docker exec idle-monitor-app php artisan howen:pull-alarms-date-range \
  --from=YYYY-MM-DD \
  --to=YYYY-MM-DD \
  --pages=100

# Example:
docker exec idle-monitor-app php artisan howen:pull-alarms-date-range \
  --from=2026-07-15 \
  --to=2026-07-15 \
  --pages=100
```

**Parameters**:
- `--from`: Start date (YYYY-MM-DD)
- `--to`: End date (YYYY-MM-DD)
- `--pages`: Max pages to pull (default: 200, recommend: 100 for speed)

**Estimasi waktu**: 3-5 menit per hari

---

### **2. GPS TRACK DATA**

```bash
# Single day, all devices
docker exec idle-monitor-app php artisan vss:pull-gps-tracks \
  --date=YYYY-MM-DD \
  --devices=all \
  --limit=0

# Example:
docker exec idle-monitor-app php artisan vss:pull-gps-tracks \
  --date=2026-07-15 \
  --devices=all \
  --limit=0
```

**Parameters**:
- `--date`: Date to pull (YYYY-MM-DD)
- `--devices`: Device filter (all, or specific device_id)
- `--limit`: Limit records (0 = unlimited)

**Estimasi waktu**: 5-10 menit per hari (tergantung jumlah device)

---

## ⚠️ RATE LIMITING WARNING

**CRITICAL**: API Howen has rate limiting protection. If you pull too many dates sequentially or in parallel without delay, you will get:

```
VSS login gagal: Login too frequently
```

**Best Practices to Avoid Rate Limiting**:
- ✅ Add 60-second delay between parallel process starts
- ✅ Limit to 3-5 parallel processes maximum
- ✅ Use helper scripts with built-in delays (recommended)
- ✅ If you see rate limit error, STOP and wait 5-10 minutes
- ✅ Reduce pages to 50 instead of 100 for safer execution

**Example Rate Limit Error**:
```
📥 Page 10/100...
ERROR: VSS login gagal: Login too frequently
Process stopped at page 10
```

**Solution**: Use the provided helper scripts with `DELAY=60` parameter.

---

## 🚀 PARALLEL EXECUTION

### **Strategy: 3-5 Parallel Processes with Rate Limiting Protection**

**Method 1: Multiple SSH Windows (Manual - Use with Caution)**

```bash
# Terminal 1
ssh khabib@103.130.6.115
docker exec idle-monitor-app php artisan howen:pull-alarms-date-range --from=2026-07-01 --to=2026-07-01 --pages=100

# Terminal 2
ssh khabib@103.130.6.115
docker exec idle-monitor-app php artisan howen:pull-alarms-date-range --from=2026-07-02 --to=2026-07-02 --pages=100

# Terminal 3
ssh khabib@103.130.6.115
docker exec idle-monitor-app php artisan howen:pull-alarms-date-range --from=2026-07-03 --to=2026-07-03 --pages=100
```

**Result**: 3 hari selesai dalam 5 menit!

---

**Method 2: Use Helper Scripts (RECOMMENDED - Has Rate Limiting Protection)**

The project includes helper scripts with built-in rate limiting protection:

**For Idle Alarms**:
```bash
# Navigate to project directory
cd /home/khabib/vss/idle-monitor-new/idle-monitor/

# Make executable
chmod +x pull_idle_alarms_parallel.sh

# Edit dates and run
./pull_idle_alarms_parallel.sh
```

**For GPS Tracks**:
```bash
# Navigate to project directory
cd /home/khabib/vss/idle-monitor-new/idle-monitor/

# Make executable
chmod +x pull_gps_tracks_parallel.sh

# Edit dates and run
./pull_gps_tracks_parallel.sh
```

**Keuntungan**:
- ✅ Built-in 60-second delay between starts
- ✅ Rate limiting protection
- ✅ Reduced pages (50 instead of 100)
- ✅ Process tetap jalan setelah logout SSH
- ✅ Log tersimpan untuk audit
- ✅ Warning messages tentang rate limiting

**Monitor Progress**:
```bash
# Monitor all logs
tail -f pull_*.log

# Check running processes
ps aux | grep "howen:pull"
ps aux | grep "vss:pull-gps"

# Kill process if needed
kill -9 <PID>
```

---

## 📊 HELPER SCRIPTS

### **Script 1: Pull Multiple Dates (Loop)**

```bash
#!/bin/bash
# pull_idle_alarms_range.sh

START_DATE="2026-07-01"
END_DATE="2026-07-10"

current=$START_DATE
while [ "$current" != $(date -I -d "$END_DATE + 1 day") ]; do
  echo "📥 Pulling data for $current..."
  docker exec idle-monitor-app php artisan howen:pull-alarms-date-range \
    --from=$current \
    --to=$current \
    --pages=100
  
  echo "✅ $current completed"
  current=$(date -I -d "$current + 1 day")
done

echo "🎉 All dates completed!"
```

**Usage**:
```bash
chmod +x pull_idle_alarms_range.sh
./pull_idle_alarms_range.sh
```

---

### **Script 2: Pull Multiple Dates (Parallel Background)**

```bash
#!/bin/bash
# pull_idle_alarms_parallel.sh

DATES=(
  "2026-07-01"
  "2026-07-02"
  "2026-07-03"
  "2026-07-04"
  "2026-07-05"
)

for date in "${DATES[@]}"; do
  echo "🚀 Starting pull for $date in background..."
  nohup docker exec idle-monitor-app php artisan howen:pull-alarms-date-range \
    --from=$date \
    --to=$date \
    --pages=100 \
    > "pull_idle_${date}.log" 2>&1 &
done

echo "✅ All processes started!"
echo "📋 Monitor with: tail -f pull_idle_*.log"
echo "🔍 Check status: ps aux | grep howen:pull"
```

**Usage**:
```bash
chmod +x pull_idle_alarms_parallel.sh
./pull_idle_alarms_parallel.sh

# Monitor all logs
tail -f pull_idle_*.log
```

---

## ✅ VERIFICATION

### **Check Data Masuk**

```bash
# Check total records per tanggal
docker exec idle-monitor-app php artisan tinker --execute="
echo '01/07: ' . DB::table('idle_alarms')->whereDate('starting_time', '2026-07-01')->count() . PHP_EOL;
echo '02/07: ' . DB::table('idle_alarms')->whereDate('starting_time', '2026-07-02')->count() . PHP_EOL;
echo '03/07: ' . DB::table('idle_alarms')->whereDate('starting_time', '2026-07-03')->count() . PHP_EOL;
"
```

### **Check GPS Track Data**

```bash
docker exec idle-monitor-app php artisan tinker --execute="
echo '01/07: ' . DB::table('gps_tracks_raw')->whereDate('gps_time', '2026-07-01')->count() . PHP_EOL;
echo '02/07: ' . DB::table('gps_tracks_raw')->whereDate('gps_time', '2026-07-02')->count() . PHP_EOL;
"
```

---

## 📋 BEST PRACTICES

### **1. Use Helper Scripts (STRONGLY RECOMMENDED)**
```bash
# Helper scripts have built-in rate limiting protection
cd /home/khabib/vss/idle-monitor-new/idle-monitor/
chmod +x pull_idle_alarms_parallel.sh pull_gps_tracks_parallel.sh

# Edit dates in script, then run
./pull_idle_alarms_parallel.sh
```

**Why helper scripts?**
- ✅ Built-in 60-second delay between starts
- ✅ Reduced pages (50 instead of 100)
- ✅ Warning messages about rate limiting
- ✅ Prevents "Login too frequently" error

### **2. Start Small & Test**
```bash
# Test dengan 1 hari dulu
docker exec idle-monitor-app php artisan howen:pull-alarms-date-range --from=2026-07-01 --to=2026-07-01 --pages=10

# Jika OK, gunakan helper script untuk scale up
```

### **3. Parallel Limit (CRITICAL)**
```bash
# Max 3-5 parallel processes
# Lebih dari itu akan trigger rate limiting
# Helper scripts automatically handle this
```

### **4. Monitor Logs & Watch for Rate Limiting**
```bash
# Selalu monitor logs untuk detect error
tail -f pull_*.log

# Watch for this error:
# "VSS login gagal: Login too frequently"

# If you see it:
# - STOP all processes immediately (kill -9 <PID>)
# - Wait 5-10 minutes
# - Increase DELAY in helper script (60s → 90s)
# - Reduce parallel processes (5 → 3)
```

### **5. Off-Peak Hours**
```bash
# Jalankan di jam sepi (malam/weekend)
# Untuk minimize impact ke production
# Dan mengurangi risiko rate limiting
```

### **6. If Rate Limited**
```bash
# STOP semua proses
ps aux | grep "howen:pull"
kill -9 <PID>

# WAIT 5-10 menit

# RE-RUN dengan parameter lebih aman:
# - Increase DELAY dari 60s → 90s
# - Reduce parallel processes
# - Gunakan helper script
```

---

## 🎯 COMMON USE CASES

### **Use Case 1: Pull Data Bulan Lalu (USING HELPER SCRIPT - RECOMMENDED)**

```bash
# Edit pull_idle_alarms_parallel.sh
# Update DATES array dengan seluruh bulan July:

DATES=(
  "2026-07-01" "2026-07-02" "2026-07-03" ... "2026-07-31"
)

# Run script
./pull_idle_alarms_parallel.sh
```

**Estimasi**: 31 hari selesai dalam ~35-40 menit (with 60s delays for rate limiting protection)

**Alternative (Manual - NOT RECOMMENDED due to rate limiting risk)**:
```bash
# July 2026 (1-31)
for day in {01..31}; do
  nohup docker exec idle-monitor-app php artisan howen:pull-alarms-date-range \
    --from=2026-07-$day \
    --to=2026-07-$day \
    --pages=50 \
    > pull_07-$day.log 2>&1 &
  sleep 60  # MUST use 60s delay (not 2s) to prevent rate limiting
done
```

**⚠️ WARNING**: Manual method requires proper delay. Too short delay = rate limiting error!

---

### **Use Case 2: Pull Missing Dates**

```bash
# Check tanggal mana yang belum ada data
docker exec idle-monitor-app php artisan tinker --execute="
\$dates = [];
for (\$i = 1; \$i <= 31; \$i++) {
  \$date = '2026-07-' . str_pad(\$i, 2, '0', STR_PAD_LEFT);
  \$count = DB::table('idle_alarms')->whereDate('starting_time', \$date)->count();
  if (\$count == 0) {
    \$dates[] = \$date;
  }
}
echo 'Missing dates: ' . implode(', ', \$dates);
"

# Pull only missing dates
docker exec idle-monitor-app php artisan howen:pull-alarms-date-range --from=2026-07-05 --to=2026-07-05 --pages=100
docker exec idle-monitor-app php artisan howen:pull-alarms-date-range --from=2026-07-12 --to=2026-07-12 --pages=100
```

---

### **Use Case 3: Bulk Pull (1 Year)**

```bash
# Pull seluruh tahun 2025 (365 hari)
# MUST use rate limiting protection!

for month in {01..12}; do
  for day in {01..31}; do
    date="2025-$month-$day"
    
    # Check if date valid
    if date -d "$date" >/dev/null 2>&1; then
      nohup docker exec idle-monitor-app php artisan howen:pull-alarms-date-range \
        --from=$date \
        --to=$date \
        --pages=50 \
        > "pull_$date.log" 2>&1 &
      
      # Limit concurrent processes (max 3 for safety)
      while [ $(ps aux | grep -c "howen:pull") -gt 3 ]; do
        sleep 10
      done
      
      # CRITICAL: Add delay between starts
      sleep 60
    fi
  done
done
```

**Estimasi**: 365 hari selesai dalam ~6-8 jam (parallel 3 processes with 60s delays)

**⚠️ IMPORTANT**: 
- Reduced from 5 → 3 parallel processes for rate limiting safety
- Reduced pages from 100 → 50 for safety
- Added 60s delay between starts
- Better to take longer than to hit rate limit!

**RECOMMENDED**: Split by month and use helper scripts for better control

---

## 🆘 TROUBLESHOOTING

### **Issue 1: Command Not Found**

```bash
# Check if command exists
docker exec idle-monitor-app php artisan list | grep howen
docker exec idle-monitor-app php artisan list | grep vss

# If not found, check if file exists
docker exec idle-monitor-app ls -la app/Console/Commands/
```

### **Issue 2: No Data Pulled (0 records)**

```bash
# Check API token
docker exec idle-monitor-app php artisan tinker --execute="echo cache('howen_api_token'); echo PHP_EOL;"

# Test API connection
docker exec idle-monitor-app php artisan howen:test-auth

# Check command output
docker exec idle-monitor-app php artisan howen:pull-alarms-date-range --from=2026-07-15 --to=2026-07-15 --pages=1
```

### **Issue 3: Rate Limiting Error ("Login too frequently")**

```bash
# Symptoms:
# - Error message: "VSS login gagal: Login too frequently"
# - Process stops at page 10-16
# - Multiple failed login attempts

# Solution:
# 1. STOP all running processes
ps aux | grep "howen:pull"
kill -9 <PID>

# 2. WAIT 5-10 minutes
sleep 600

# 3. Use helper scripts with built-in delays
cd /home/khabib/vss/idle-monitor-new/idle-monitor/
./pull_idle_alarms_parallel.sh

# 4. If still happens, increase delay:
# Edit script: DELAY=90 (instead of 60)
# Reduce parallel processes: 3 instead of 5
```

### **Issue 4: Process Stuck**

```bash
# Check running processes
ps aux | grep "howen:pull"

# Kill stuck process
kill -9 <PID>

# Check container logs
docker logs idle-monitor-app
```

---

## 📞 SUPPORT

**Server**: 103.130.6.115  
**SSH User**: khabib@dash-serv  
**Container**: idle-monitor-app

**Commands Quick Reference**:
```bash
# SSH to server
ssh khabib@103.130.6.115

# Enter container (if needed)
docker exec -it idle-monitor-app bash

# Check logs
docker logs -f idle-monitor-app
docker exec idle-monitor-app tail -f storage/logs/laravel.log

# Check database
docker exec idle-monitor-app php artisan tinker
```

---

**Last Updated**: 2026-07-16  
**Version**: 1.0.0

