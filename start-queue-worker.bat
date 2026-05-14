@echo off
echo Starting Laravel Queue Worker...
echo Press Ctrl+C to stop
echo.

:loop
cd /d D:\websites\ERP\erp_project\erp_project
php artisan queue:work --tries=3 --timeout=7200 --sleep=3 --max-jobs=1000 --max-time=3600

echo.
echo Queue worker stopped. Restarting in 5 seconds...
timeout /t 5
goto loop

