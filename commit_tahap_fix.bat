@echo off
cd /d g:\project\vss\idle-monitor
"C:\Program Files\Git\bin\git.exe" add -A
"C:\Program Files\Git\bin\git.exe" commit -m "Fix duration extraction - extract dur value from endDetail (not alarmTimeLength)"
"C:\Program Files\Git\bin\git.exe" push -u origin main
