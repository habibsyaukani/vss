@echo off
cd /d g:\project\vss\idle-monitor
"C:\Program Files\Git\bin\git.exe" add -A
"C:\Program Files\Git\bin\git.exe" commit -m "Use real Howen device naming (GPE-*) and add idle alarm validation (end_speed > 0, duration >= 5min)"
"C:\Program Files\Git\bin\git.exe" push -u origin main
