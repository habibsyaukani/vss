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

## 🚀 PARALLEL EXECUTION

### **Strategy: 3-5 Parallel Processes**

**Method 1: Multiple SSH Windows**

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

**Method 2: Background Jobs (Recommended for Many Dates)**

```bash
# Run in background
nohup docker exec idle-monitor-app php artisan howen:pull-alarms-date-range --from=2026-07-01 --to=2026-07-01 --pages=100 > pull_07-01.log 2>&1 &
nohup docker exec idle-monitor-app php artisan howen:pull-alarms-date-range --from=2026-07-02 --to=2026-07-02 --pages=100 > pull_07-02.log 2>&1 &
nohup docker exec idle-monitor-app php artisan howen:pull-alarms-date-range --from=2026-07-03 --to=2026-07-03 --pages=100 > pull_07-03.log 2>&1 &

# Monitor progress
tail -f pull_*.log

# Check running processes
ps aux | grep "howen:pull"
```

**Keuntungan**:
- ✅ Bisa logout SSH, process tetap jalan
- ✅ Bisa jalankan 10+ tanggal sekaligus
- ✅ Log tersimpan untuk audit

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

### **1. Start Small**
```bash
# Test dengan 1 hari dulu
docker exec idle-monitor-app php artisan howen:pull-alarms-date-range --from=2026-07-01 --to=2026-07-01 --pages=10

# Jika OK, scale up
```

### **2. Parallel Limit**
```bash
# Max 3-5 parallel processes
# Lebih dari itu bisa overload API Howen
```

### **3. Monitor Logs**
```bash
# Selalu monitor logs untuk detect error
tail -f pull_*.log
docker logs -f idle-monitor-app
```

### **4. Off-Peak Hours**
```bash
# Jalankan di jam sepi (malam/weekend)
# Untuk minimize impact ke production
```

---

## 🎯 COMMON USE CASES

### **Use Case 1: Pull Data Bulan Lalu**

```bash
# July 2026 (1-31)
for day in {01..31}; do
  nohup docker exec idle-monitor-app php artisan howen:pull-alarms-date-range \
    --from=2026-07-$day \
    --to=2026-07-$day \
    --pages=100 \
    > pull_07-$day.log 2>&1 &
  sleep 2  # Delay 2 detik antar start
done
```

**Estimasi**: 31 hari selesai dalam ~5-10 menit (parallel)

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
# Gunakan background job + loop

for month in {01..12}; do
  for day in {01..31}; do
    date="2025-$month-$day"
    
    # Check if date valid
    if date -d "$date" >/dev/null 2>&1; then
      nohup docker exec idle-monitor-app php artisan howen:pull-alarms-date-range \
        --from=$date \
        --to=$date \
        --pages=100 \
        > "pull_$date.log" 2>&1 &
      
      # Limit concurrent processes (max 5)
      while [ $(ps aux | grep -c "howen:pull") -gt 5 ]; do
        sleep 5
      done
    fi
  done
done
```

**Estimasi**: 365 hari selesai dalam ~2-3 jam (parallel 5 processes)

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

### **Issue 3: Process Stuck**

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

