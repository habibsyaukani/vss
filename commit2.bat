@echo off
cd /d g:\project\vss\idle-monitor
"C:\Program Files\Git\bin\git.exe" add -A
"C:\Program Files\Git\bin\git.exe" commit -m "Tahap 5-6 COMPLETE - Process Idle Alarms (4 alarms processed, full pipeline working)"
"C:\Program Files\Git\bin\git.exe" push -u origin main
