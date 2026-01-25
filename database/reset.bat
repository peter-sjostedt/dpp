@echo off
echo ============================================
echo DPP Platform - Database Reset
echo ============================================

cd /d "%~dp0"

echo Running schema files...

mysql -u root < schema\01_foundation.sql
if %errorlevel% neq 0 goto :error

mysql -u root < schema\02_products_batches.sql
if %errorlevel% neq 0 goto :error

mysql -u root < schema\03_care_compliance_export.sql
if %errorlevel% neq 0 goto :error

mysql -u root < schema\04_testdata.sql
if %errorlevel% neq 0 goto :error

mysql -u root < schema\05_testdata_continued.sql
if %errorlevel% neq 0 goto :error

mysql -u root < schema\06_testdata_items.sql
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
