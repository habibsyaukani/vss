@echo off
REM ====================================================
REM SSH Connection Helper
REM ====================================================

echo.
echo Connecting to server...
echo.
echo Server: 103.130.6.115
echo User: khabib
echo.
echo TIPS:
echo - Pastikan password SSH benar
echo - Jika gagal 3x, tunggu 5 menit (account lockout)
echo - Gunakan password yang sama dengan sebelumnya
echo.
pause

ssh khabib@103.130.6.115

pause
