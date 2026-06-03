@echo off
REM Check database status
"C:\laragon\bin\mysql\mysql-8.0-winx64\bin\mysql.exe" -u root -h 127.0.0.1 vss -e "SELECT 'alarm_raw:' as table_name, COUNT(*) as count FROM alarm_raw; SELECT 'import_logs:', COUNT(*) FROM import_logs; SELECT 'jobs:', COUNT(*) FROM jobs; SELECT 'Last 3 import logs:'; SELECT job_name, status, total_record, message FROM import_logs ORDER BY id DESC LIMIT 3;"
