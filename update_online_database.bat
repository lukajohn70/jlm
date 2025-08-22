@echo off
echo ========================================
echo JLM Online Database Update Script
echo ========================================
echo.

echo This script will update your ONLINE/PRODUCTION database (gwylvxeo_jlm).
echo.
echo WARNING: This will modify your PRODUCTION database!
echo Make sure you have a backup before proceeding.
echo.

set /p confirm="Are you sure you want to continue? (y/N): "
if /i not "%confirm%"=="y" (
    echo Update cancelled.
    pause
    exit /b 0
)

echo.
echo Using ONLINE database credentials from config file:
echo Database: gwylvxeo_jlm
echo User: gwylvxeo_gwylvxeo
echo.
echo Please enter your database password:
echo.

REM Check if the update script exists
if not exist "sql\update_online_database.sql" (
    echo Error: sql\update_online_database.sql not found!
    echo Please make sure the file exists in the sql directory.
    pause
    exit /b 1
)

echo Running online database update...
echo.

REM Run the online update script using production credentials
REM Using the database credentials from config file
mysql -u gwylvxeo_gwylvxeo -p -e "USE gwylvxeo_jlm; SOURCE sql/update_online_database.sql;"

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo Online database update completed successfully!
    echo ========================================
    echo.
    echo Your online database now has all the enhanced features:
    echo ✅ Enhanced contact management with ticket tracking
    echo ✅ Complete booking management system
    echo ✅ Email subscription and campaign system
    echo ✅ Performance optimizations
    echo.
) else (
    echo.
    echo ========================================
    echo Online database update failed!
    echo Please check the error messages above.
    echo ========================================
)

echo.
pause 