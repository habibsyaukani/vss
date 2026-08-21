"""
Script untuk update series VOLVO dan location M.SERVICE
Berdasarkan gambar yang diberikan
"""

import csv
from datetime import datetime
import shutil
import os

# File paths
input_file = 'devices_update_data.csv'
backup_file = f'devices_update_data_backup_{datetime.now().strftime("%Y%m%d%H%M%S")}.csv'

print("===========================================")
print("UPDATE VOLVO SERIES & M.SERVICE LOCATION")
print("===========================================\n")

# Backup original file
if os.path.exists(input_file):
    shutil.copy(input_file, backup_file)
    print(f"✅ Backup created: {backup_file}\n")

# Unit codes dari Gambar 1 - untuk update series ke VOLVO
volvo_units = [
    'GPE932', 'GPE937', 'GPE951', 'GPE952', 'GPE953', 'GPE955',
    'GPE998', 'GPE999', 'GPE1000', 'GPE1001', 'GPE1002', 'GPE1003',
    'GPE1005', 'GPE1006', 'GPE1007', 'GPE1008'
]

# Unit codes dari Gambar 2 - untuk update location ke M.SERVICE
# Mapping dari device code GPE-DT-28xx ke unit_code GPE11xx
mservice_units = [
    'GPE1105',  # GPE-DT-2801
    'GPE1106',  # GPE-DT-2802
    'GPE1108',  # GPE-DT-2803
    'GPE1109',  # GPE-DT-2805
    'GPE1110',  # GPE-DT-2806
    'GPE1112',  # GPE-DT-2807
    'GPE1113',  # GPE-DT-2808
    'GPE1125',  # GPE-DT-2809
    'GPE1126',  # GPE-DT-2810
    'GPE1127',  # GPE-DT-2811
    'GPE1128'   # GPE-DT-2812
]

# Read CSV
rows = []
with open(input_file, 'r', encoding='utf-8') as f:
    reader = csv.reader(f)
    rows = list(reader)

print(f"Total devices: {len(rows) - 1}\n")

# Counters
volvo_updates = 0
mservice_updates = 0
volvo_list = []
mservice_list = []

# Process data (skip header row)
for i in range(1, len(rows)):
    device_code = rows[i][0]
    unit_code = rows[i][1]
    series = rows[i][2]
    location = rows[i][3]
    
    # Check untuk VOLVO series update
    if unit_code in volvo_units:
        old_series = series
        rows[i][2] = 'VOLVO'
        volvo_updates += 1
        volvo_list.append(f"{device_code} ({unit_code}): {old_series} → VOLVO")
    
    # Check untuk M.SERVICE location update
    if unit_code in mservice_units:
        old_location = location
        rows[i][3] = 'M.SERVICE'
        mservice_updates += 1
        mservice_list.append(f"{device_code} ({unit_code}): {old_location} → M.SERVICE")

# Write updated CSV
with open(input_file, 'w', encoding='utf-8', newline='') as f:
    writer = csv.writer(f)
    writer.writerows(rows)

# Display results
print("📊 UPDATE SUMMARY")
print("===========================================")
print(f"🔵 VOLVO Series Updates: {volvo_updates} devices")
print(f"🔵 M.SERVICE Location Updates: {mservice_updates} devices")
print(f"✅ Total Updates: {volvo_updates + mservice_updates}\n")

if volvo_list:
    print("📋 VOLVO SERIES UPDATES:")
    print("-------------------------------------------")
    for item in volvo_list:
        print(f"  • {item}")
    print()

if mservice_list:
    print("📋 M.SERVICE LOCATION UPDATES:")
    print("-------------------------------------------")
    for item in mservice_list:
        print(f"  • {item}")
    print()

print("===========================================")
print("✅ CSV file updated successfully!")
print(f"📁 File: {input_file}")
print(f"💾 Backup: {backup_file}")
print("===========================================")
