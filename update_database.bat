@echo off
echo ========================================
echo JLM Database Schema Update Script
echo ========================================
echo.

echo Please enter your MySQL root password when prompted.
echo.

REM Check if the safe update script exists
if not exist "sql\safe_update_schema.sql" (
    echo Error: sql\safe_update_schema.sql not found!
    echo Please make sure the file exists in the sql directory.
    pause
    exit /b 1
)

echo Running database schema update...
echo.

REM Run the safe update script
mysql -u root -p -e "USE gwylvxeo_jlm; SOURCE sql/safe_update_schema.sql;"

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo Database update completed successfully!
    echo ========================================
) else (
    echo.
    echo ========================================
    echo Database update failed!
    echo Please check the error messages above.
    echo ========================================
)

echo.
pause 