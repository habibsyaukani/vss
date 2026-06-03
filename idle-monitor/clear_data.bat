@echo off
"C:\laragon\bin\mysql\mysql-8.0-winx64\bin\mysql.exe" -u root -h 127.0.0.1 vss -e "DELETE FROM idle_alarms; DELETE FROM alarm_raw; DELETE FROM devices; DELETE FROM import_logs; SELECT 'Tables cleared'; SELECT 'alarm_raw:' as table_name, COUNT(*) as count FROM alarm_raw; SELECT 'idle_alarms:', COUNT(*) FROM idle_alarms; SELECT 'devices:', COUNT(*) FROM devices;"
