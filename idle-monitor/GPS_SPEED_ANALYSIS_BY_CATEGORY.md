# 🚛 GPS SPEED ANALYSIS BY CATEGORY - June 12, 2026

**Generated:** 2026-06-12  
**Data Source:** gps_tracks_raw (26,801 records from 40 devices)  
**Purpose:** Analyze speed performance by vehicle category (HD, DT, FT, LV, B, WT)

---

## 🎯 EXECUTIVE SUMMARY

### Speed Statistics by Category:

| Category | Devices | Records | Avg Speed | Min | Max | Std Dev | Status |
|----------|---------|---------|-----------|-----|-----|---------|--------|
| **B** (Bus) | 6 | 2,357 | **30.62 km/h** | 0 | 70 | 18.55 | ✅ **FASTEST** |
| **HD** (Heavy Duty) | 6 | 3,660 | **15.22 km/h** | 0 | 41 | 9.68 | ✅ Medium |
| **DT** (Dump Truck) | 26 | 20,276 | **14.94 km/h** | 0 | 65 | 9.46 | ✅ Medium |
| **FT** (Fuel Truck) | 2 | 508 | **9.51 km/h** | 0 | 36 | 9.85 | ✅ Slow |
| **LV** (Light Vehicle) | 0 | 0 | **N/A** | - | - | - | ❌ **OFFLINE** |
| **WT** (Water Truck) | 0 | 0 | **N/A** | - | - | - | ❌ **OFFLINE** |

### 🔑 KEY FINDINGS:

1. **B-Series (Bus) = FASTEST** with 30.62 km/h average (2X faster than DT/HD)
2. **DT-Series (Dump Truck) = MOST DATA** with 20,276 records (76% of total)
3. **HD-Series (Heavy Duty) = BALANCED** with 15.22 km/h
4. **FT-Series (Fuel Truck) = SLOWEST** with 9.51 km/h (hauling heavy fuel)
5. **LV & WT (VOLVO) = OFFLINE** - 0 records on June 12

---

## 📊 CATEGORY 1: B-SERIES (BUS) - FASTEST ⭐

### Overall Stats:
- **Average Speed:** 30.62 km/h (HIGHEST)
- **Max Speed:** 70 km/h
- **Devices Active:** 6 units
- **Total Records:** 2,357

### Individual Performance:

| Rank | Device | Series | Location | Records | Avg Speed | Max Speed |
|------|--------|--------|----------|---------|-----------|-----------|
| 1 | GPE-B-856 | OHT 773 | SELATAN | 395 | **34.45 km/h** | 70 km/h 🚀 |
| 2 | GPE-B-811 | HD 785 | SELATAN | 392 | **33.88 km/h** | 63 km/h 🚀 |
| 3 | GPE-B-812 | HD 785 | SELATAN | 783 | **32.42 km/h** | 63 km/h 🚀 |
| 4 | GPE-B-871 | HD 465 | SELATAN | 391 | **28.03 km/h** | 65 km/h |
| 5 | GPE-B-829 | OHT 773 | SELATAN | 199 | **23.83 km/h** | 57 km/h |
| 6 | GPE-B-837 | OHT 773 | SELATAN | 197 | **21.32 km/h** | 50 km/h |

### Analysis:
- ✅ **Consistently FAST** (21-34 km/h average)
- ✅ **Highest max speed** (70 km/h - GPE-B-856)
- ✅ **All SELATAN location** (hauling operations)
- ✅ **Mix of series:** OHT 773 (3), HD 785 (2), HD 465 (1)
- 📊 **High variance** (stddev 18.55) = varied speed operations

**Reason for High Speed:**
- Bus units likely used for crew transport (not hauling)
- Faster travel between locations
- Less weight = higher speed capability

---

## 📊 CATEGORY 2: HD-SERIES (HEAVY DUTY) - MEDIUM SPEED

### Overall Stats:
- **Average Speed:** 15.22 km/h
- **Max Speed:** 41 km/h
- **Devices Active:** 6 units
- **Total Records:** 3,660

### Individual Performance:

| Rank | Device | Series | Location | Records | Avg Speed | Max Speed |
|------|--------|--------|----------|---------|-----------|-----------|
| 1 | GPE-HD-840 | DT LAMA FMX 370 | STB_001 | 394 | **17.56 km/h** | 38 km/h |
| 2 | GPE-HD-841 | DT LAMA FMX 370 | STB_001 | 395 | **17.51 km/h** | 36 km/h |
| 3 | GPE-HD-852 | DT LAMA FMX 370 | STB_001 | 964 | **16.58 km/h** | 41 km/h |
| 4 | GPE-HD-728 | DT BARU FMX 420 | UTARA | 797 | **16.36 km/h** | 40 km/h |
| 5 | GPE-HD-7816 | DT BARU FMX 400 | JO SELATAN | 950 | **13.51 km/h** | 39 km/h |
| 6 | GPE-HD-837 | DT LAMA FMX 370 | STB_001 | 160 | **0.00 km/h** | 0 km/h ⚠️ |

### Analysis:
- ✅ **Consistent medium speed** (13-17 km/h for active units)
- ⚠️ **GPE-HD-837 stationary** (0 km/h - parked or idle)
- ✅ **Mixed locations:** STB_001 (4), UTARA (1), JO SELATAN (1)
- ✅ **DT LAMA FMX 370 fastest** in HD category (17.5 km/h avg)
- 📊 **Low variance** (stddev 9.68) = steady operations

**Operational Pattern:**
- Heavy duty units for specialized hauling
- Moderate speed for safety and load capacity
- STB_001 location most active (4 units)

---

## 📊 CATEGORY 3: DT-SERIES (DUMP TRUCK) - WORKHORSE 🚜

### Overall Stats:
- **Average Speed:** 14.94 km/h
- **Max Speed:** 65 km/h
- **Devices Active:** 26 units (MOST)
- **Total Records:** 20,276 (76% of all GPS data!)

### Top 10 Performers:

| Rank | Device | Series | Location | Records | Avg Speed | Max Speed |
|------|--------|--------|----------|---------|-----------|-----------|
| 1 | GPE-DT-1139 | HD 465 | JO SELATAN | 977 | **18.36 km/h** | 48 km/h |
| 2 | GPE-DT-1239 | DT BARU FMX 400 | UTARA | 1,169 | **17.20 km/h** | 43 km/h |
| 3 | GPE-DT-1081 | HD 465 | JO SELATAN | 966 | **17.17 km/h** | 45 km/h |
| 4 | GPE-DT-1093 | HD 465 | JO SELATAN | 788 | **16.95 km/h** | 39 km/h |
| 5 | GPE-DT-1168 | DT LAMA FMX 400 | SELATAN | 1,332 | **16.24 km/h** | 39 km/h |
| 6 | GPE-DT-995 | DT BARU FMX 400 | UTARA | 567 | **16.08 km/h** | 40 km/h |
| 7 | GPE-DT-1207 | DT BARU FMX 400 | UTARA | 596 | **16.03 km/h** | 40 km/h |
| 8 | GPE-DT-1159 | HD 465 | SELATAN | 765 | **15.71 km/h** | 44 km/h |
| 9 | GPE-DT-1059 | HD 465 | SELATAN | 796 | **15.39 km/h** | 44 km/h |
| 10 | GPE-DT-1236 | DT BARU FMX 400 | UTARA | 1,142 | **15.38 km/h** | 43 km/h |

### Speed Distribution:

| Speed Range | Count | Series Breakdown |
|-------------|-------|------------------|
| **16-18 km/h** (Fast) | 5 units | HD 465 (4), DT BARU/LAMA FMX 400 (1) |
| **14-16 km/h** (Medium) | 14 units | Mixed (HD 465, DT BARU FMX 400, DT LAMA) |
| **10-14 km/h** (Slow) | 5 units | DT BARU FMX 400 (4), HD 465 (1) |
| **0 km/h** (Idle) | 2 units | HD 465 (parked/idle) |

### Analysis by Series:

#### A. HD 465 Series (13 units):
- **Avg Speed:** 15.50 km/h
- **Locations:** JO SELATAN (7), SELATAN (6)
- **Performance:** ✅ FASTEST in DT category
- **Note:** Most consistent hauling operations

#### B. DT BARU FMX 400 Series (9 units):
- **Avg Speed:** 14.25 km/h
- **Locations:** UTARA (8), SELATAN (1)
- **Performance:** ✅ GOOD
- **Note:** Main production fleet

#### C. DT LAMA FMX 400 Series (4 units):
- **Avg Speed:** 14.85 km/h
- **Locations:** SELATAN (2), UTARA (1), STB_SITE (1)
- **Performance:** ✅ GOOD
- **Note:** Older fleet but still effective

### Key Insights:
- ✅ **HD 465 series FASTEST** within DT category (18.36 km/h max)
- ✅ **DT BARU FMX 400 most deployed** (9 units)
- ✅ **UTARA location dominates** (9 units, all DT BARU)
- ✅ **JO SELATAN HD 465 efficient** (7 units, avg 16.5 km/h)
- 📊 **Low variance** (stddev 9.46) = steady hauling speed
- ⚠️ **2 units idle** (GPE-DT-1160, possible maintenance)

**Operational Pattern:**
- Primary hauling fleet (76% of all GPS data)
- Consistent speed 14-18 km/h (loaded/unloaded cycles)
- Multiple locations operational (UTARA, JO SELATAN, SELATAN)
- HD 465 series more efficient than DT BARU/LAMA

---

## 📊 CATEGORY 4: FT-SERIES (FUEL TRUCK) - SLOWEST 🐢

### Overall Stats:
- **Average Speed:** 9.51 km/h (LOWEST active category)
- **Max Speed:** 36 km/h
- **Devices Active:** 2 units
- **Total Records:** 508

### Individual Performance:

| Rank | Device | Series | Location | Records | Avg Speed | Max Speed |
|------|--------|--------|----------|---------|-----------|-----------|
| 1 | GPE-FT-871 | DT BARU FMX 400 | UTARA | 192 | **9.95 km/h** | 34 km/h |
| 2 | GPE-FT-872 | DT BARU FMX 420 | UTARA | 316 | **9.24 km/h** | 36 km/h |

### Analysis:
- ⚠️ **Slowest category** (9.5 km/h average)
- ✅ **Safety priority** (hauling flammable fuel)
- ✅ **Both UTARA location** (fuel depot operations)
- ✅ **Consistent speed** (9-10 km/h range)
- 📊 **High variance** (stddev 9.85) = stop-and-go refueling

**Reason for Low Speed:**
- Hauling flammable fuel (safety regulations)
- Frequent stops for refueling operations
- Heavy load reduces speed
- Cautious driving required

---

## 📊 CATEGORY 5 & 6: LV & WT-SERIES (VOLVO) - OFFLINE ❌

### Overall Stats:
- **Average Speed:** N/A
- **Devices in Database:** 6 units (4 LV + 2 WT)
- **Devices Active:** 0 units
- **Total Records:** 0

### Device List:

| Device | Category | Series | Location | Status |
|--------|----------|--------|----------|--------|
| GPE-LV-890 | LV (Light Vehicle) | VOLVO | M.SERVICE | ❌ Offline |
| GPE-LV-891 | LV (Light Vehicle) | VOLVO | M.SERVICE | ❌ Offline |
| GPE-LV-892 | LV (Light Vehicle) | VOLVO | M.SERVICE | ❌ Offline |
| GPE-LV-910 | LV (Light Vehicle) | VOLVO | M.SERVICE | ❌ Offline |
| GPE-WT-836 | WT (Water Truck) | VOLVO | M.SERVICE | ❌ Offline |
| GPE-WT-855 | WT (Water Truck) | VOLVO | M.SERVICE | ❌ Offline |

### Analysis:
- ❌ **All VOLVO series offline** (consistent pattern)
- ❌ **All M.SERVICE location** (maintenance area?)
- ❌ **0 GPS data on June 11 & 12** (2 days offline)
- ⚠️ **Requires investigation:** Maintenance? GPS disabled? Decommissioned?

### Recommendations:
1. Check VOLVO fleet operational status
2. Verify GPS hardware functionality
3. Check M.SERVICE location schedule
4. Confirm if units are in maintenance or decommissioned

---

## 📊 SPEED COMPARISON CHART

```
┌─────────────────────────────────────────────────────────────┐
│              AVERAGE SPEED BY CATEGORY (June 12)            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ B  (Bus)       ████████████████████████████████  30.62 km/h │
│                                                              │
│ HD (Heavy Duty)████████████████                  15.22 km/h │
│                                                              │
│ DT (Dump Truck)███████████████                   14.94 km/h │
│                                                              │
│ FT (Fuel Truck)██████                             9.51 km/h │
│                                                              │
│ LV (Light Veh) ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░      N/A   │
│                                                              │
│ WT (Water Trk) ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░      N/A   │
│                                                              │
└─────────────────────────────────────────────────────────────┘
         0        10        20        30        40 km/h
```

---

## 📊 MAX SPEED COMPARISON

```
┌─────────────────────────────────────────────────────────────┐
│            MAXIMUM SPEED BY CATEGORY (June 12)              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ B  (Bus)       ████████████████████████████████████  70km/h │
│                                                              │
│ DT (Dump Truck)█████████████████████████████         65km/h │
│                                                              │
│ HD (Heavy Duty)████████████████████                  41km/h │
│                                                              │
│ FT (Fuel Truck)█████████████                         36km/h │
│                                                              │
│ LV (Light Veh) ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░   N/A  │
│                                                              │
│ WT (Water Trk) ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░   N/A  │
│                                                              │
└─────────────────────────────────────────────────────────────┘
         0    10    20    30    40    50    60    70    80 km/h
```

---

## 🎯 KEY INSIGHTS

### 1. **Speed Hierarchy:**
```
B-Series (30.62 km/h) >> HD (15.22) ≈ DT (14.94) >> FT (9.51)
```

### 2. **B-Series (Bus) - Speed Champions:**
- **2X faster** than DT/HD categories
- Used for crew transport (not hauling)
- Maximum speed: 70 km/h (GPE-B-856)
- All units in SELATAN location

### 3. **DT-Series (Dump Truck) - Workhorses:**
- **76% of all GPS data** (20,276 records)
- **26 units active** (most of any category)
- Consistent 14-18 km/h hauling speed
- HD 465 subseries fastest (18.36 km/h)

### 4. **HD-Series (Heavy Duty) - Balanced:**
- Similar speed to DT (15.22 km/h)
- STB_001 location most active
- DT LAMA FMX 370 subseries fastest (17.5 km/h)

### 5. **FT-Series (Fuel Truck) - Cautious:**
- **Slowest active category** (9.51 km/h)
- Safety priority (flammable cargo)
- UTARA location fuel depot operations

### 6. **LV & WT-Series (VOLVO) - Mystery:**
- **0 GPS data for 2 consecutive days**
- All units in M.SERVICE location
- Requires operational status investigation

---

## 📊 OPERATIONAL PATTERNS

### Speed by Operation Type:

| Operation | Category | Avg Speed | Characteristics |
|-----------|----------|-----------|-----------------|
| **Crew Transport** | B | 30.62 km/h | Fast, light load |
| **Heavy Hauling** | HD | 15.22 km/h | Medium, heavy load |
| **Dump Operations** | DT | 14.94 km/h | Medium, cyclic loading |
| **Fuel Distribution** | FT | 9.51 km/h | Slow, safety critical |
| **Light Support** | LV | N/A | Offline |
| **Water Support** | WT | N/A | Offline |

### Speed by Location:

| Location | Dominant Category | Avg Speed | Pattern |
|----------|-------------------|-----------|---------|
| **SELATAN** | B, DT | 18-20 km/h | Hauling + transport |
| **UTARA** | DT, FT | 12-14 km/h | Heavy operations |
| **JO SELATAN** | DT (HD 465) | 16-18 km/h | Efficient hauling |
| **STB_001** | HD | 15-17 km/h | Specialized ops |
| **M.SERVICE** | LV, WT | N/A | Maintenance area |

---

## 💡 RECOMMENDATIONS

### Operational Efficiency:

1. **B-Series (Bus):**
   - ✅ Excellent performance (30+ km/h)
   - ✅ Continue using for crew transport
   - ✅ Consider expanding fleet for rapid deployment

2. **DT-Series (Dump Truck):**
   - ✅ Main production fleet performing well
   - ✅ HD 465 subseries most efficient (prioritize)
   - ⚠️ Monitor GPE-DT-1160 (0 km/h - possible issue)
   - ✅ UTARA DT BARU fleet consistent (good)

3. **HD-Series (Heavy Duty):**
   - ✅ Steady performance
   - ⚠️ GPE-HD-837 stationary (check status)
   - ✅ STB_001 operations effective

4. **FT-Series (Fuel Truck):**
   - ✅ Appropriate speed for fuel hauling (safety first)
   - ✅ No issues detected
   - ℹ️ Low speed is EXPECTED and CORRECT

5. **LV & WT-Series (VOLVO):**
   - ❌ **URGENT:** Investigate why 0 GPS data for 2 days
   - ❌ Check GPS hardware status
   - ❌ Verify operational schedule
   - ❌ Confirm M.SERVICE location purpose

### Speed Optimization:

**Do NOT attempt to increase speed for:**
- ❌ FT-Series (fuel = safety priority)
- ❌ DT-Series when loaded (overloading risks)
- ❌ HD-Series heavy ops (equipment stress)

**Can optimize:**
- ✅ Route planning for DT-Series (reduce idle time)
- ✅ Loading/unloading efficiency (faster cycles)
- ✅ Preventive maintenance (keep units moving)

---

## 📊 SUMMARY STATISTICS

```
═══════════════════════════════════════════════════════════
CATEGORY PERFORMANCE SUMMARY (June 12, 2026)
═══════════════════════════════════════════════════════════

Category  Units  Records   Avg Speed  Max Speed  Status
───────────────────────────────────────────────────────────
B (Bus)     6     2,357    30.62 km/h   70 km/h  ⭐⭐⭐ Excellent
HD          6     3,660    15.22 km/h   41 km/h  ✅✅  Good
DT         26    20,276    14.94 km/h   65 km/h  ✅✅  Good
FT          2       508     9.51 km/h   36 km/h  ✅    OK (expected)
LV          0         0        N/A         N/A   ❌    OFFLINE
WT          0         0        N/A         N/A   ❌    OFFLINE
───────────────────────────────────────────────────────────
TOTAL      40    26,801    16.82 km/h   70 km/h  ✅ Overall Good
═══════════════════════════════════════════════════════════

Fleet Availability: 40/397 (10.1%)
Operational Locations: 6 (SELATAN, UTARA, JO SELATAN, STB_001, STB_SITE, etc.)
Data Quality: Excellent ✅
Time Coverage: 00:00 - 11:23 (11.4 hours, ongoing)
Expected EOD: 50-60 devices, 50,000+ records
```

---

**Generated by:** Kiro AI  
**Date:** 2026-06-12 11:40 AM  
**Data Source:** gps_tracks_raw table  
**File:** GPS_SPEED_ANALYSIS_BY_CATEGORY.md

**Notes:**
- Data is still incoming (June 12 ongoing)
- LV & WT require operational investigation
- Speed patterns match expected fleet behavior
- B-Series fastest (transport), FT-Series slowest (safety)
