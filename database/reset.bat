@echo off
echo ============================================
echo DPP Platform - Database Reset
echo ============================================

cd /d "%~dp0"

echo Running schema files...

mysql -u root petersjo_dpp < schema\01_foundation.sql
if %errorlevel% neq 0 goto :error

mysql -u root petersjo_dpp < schema\02_products_batches.sql
if %errorlevel% neq 0 goto :error

mysql -u root petersjo_dpp < schema\03_care_compliance.sql
if %errorlevel% neq 0 goto :error

echo Loading test data...

mysql -u root petersjo_dpp < testdata\healthcare_textiles.sql
if %errorlevel% neq 0 goto :error

echo.
echo ============================================
echo Database reset complete!
echo ============================================
goto :end

:error
echo.
echo ERROR: Database reset failed!
exit /b 1

:end