@echo off
cd /d g:\project\vss\idle-monitor
"C:\Program Files\Git\bin\git.exe" add -A
"C:\Program Files\Git\bin\git.exe" commit -m "TAHAP 6 COMPLETE - Full data pipeline working (Howen API -> alarm_raw -> idle_alarms with validation)"
"C:\Program Files\Git\bin\git.exe" push -u origin main
