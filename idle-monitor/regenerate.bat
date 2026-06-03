@echo off
REM Regenerate data dengan correct mapping
echo Running data regeneration...
php artisan command:regenerate-data
echo Done!
pause
