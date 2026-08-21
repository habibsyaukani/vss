Set WshShell = CreateObject("WScript.Shell")
WshShell.CurrentDirectory = "g:\project\vss\idle-monitor"

' Jalankan Laravel Scheduler (untuk trigger job tiap menit)
WshShell.Run "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan schedule:work", 0, false

' Jalankan Laravel Queue Worker (untuk memproses tarikan data)
WshShell.Run "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan queue:work --timeout=120 --tries=3", 0, false
