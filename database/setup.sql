-- ============================================
-- DPP Platform - Komplett Setup/Reset
-- Kör alla schema-filer i ordning
-- ============================================

-- Användning:
-- mysql -u root -p < database/setup.sql
-- eller i MySQL Workbench/phpMyAdmin

SOURCE database/schema/01_foundation.sql;
SOURCE database/schema/02_products_batches.sql;
SOURCE database/schema/03_care_compliance_export.sql;
SOURCE database/schema/04_testdata.sql;
SOURCE database/schema/05_testdata_continued.sql;
SOURCE database/schema/06_testdata_items.sql;
