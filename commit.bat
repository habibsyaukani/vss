@echo off
cd /d g:\project\vss\idle-monitor
"C:\Program Files\Git\bin\git.exe" add -A
"C:\Program Files\Git\bin\git.exe" commit -m "Tahap 4 - Import Alarm Raw SUCCESS (2 alarms imported, queue processing working)"
"C:\Program Files\Git\bin\git.exe" push -u origin main
