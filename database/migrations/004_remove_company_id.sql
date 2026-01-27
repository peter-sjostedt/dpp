-- ============================================
-- Migration 004: Remove company_id columns
-- EXECUTE ONLY AFTER ALL CODE HAS BEEN UPDATED
-- ============================================

USE petersjo_dpp;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- Remove company_id from products
-- ============================================
-- Check if foreign key exists before dropping
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'petersjo_dpp'
    AND TABLE_NAME = 'products'
    AND CONSTRAINT_NAME = 'fk_products_company');

SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE products DROP FOREIGN KEY fk_products_company',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Drop index if exists
DROP INDEX IF EXISTS idx_products_company ON products;

-- Drop column
ALTER TABLE products DROP COLUMN IF EXISTS company_id;

-- ============================================
-- Remove company_id from batches
-- ============================================
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'petersjo_dpp'
    AND TABLE_NAME = 'batches'
    AND CONSTRAINT_NAME = 'fk_batches_company');

SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE batches DROP FOREIGN KEY fk_batches_company',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

DROP INDEX IF EXISTS idx_batches_company ON batches;
ALTER TABLE batches DROP COLUMN IF EXISTS company_id;

-- ============================================
-- Remove company_id from items
-- ============================================
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'petersjo_dpp'
    AND TABLE_NAME = 'items'
    AND CONSTRAINT_NAME = 'fk_items_company');

SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE items DROP FOREIGN KEY fk_items_company',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

DROP INDEX IF EXISTS idx_items_company ON items;
ALTER TABLE items DROP COLUMN IF EXISTS company_id;

-- ============================================
-- Remove company_id from brands
-- ============================================
-- The FK name might be different based on how it was created
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'petersjo_dpp'
    AND TABLE_NAME = 'brands'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY');

-- Try common FK names
ALTER TABLE brands DROP FOREIGN KEY IF EXISTS brands_ibfk_1;
ALTER TABLE brands DROP FOREIGN KEY IF EXISTS fk_brands_company;

DROP INDEX IF EXISTS idx_brands_company ON brands;
ALTER TABLE brands DROP COLUMN IF EXISTS company_id;

-- ============================================
-- Remove company_id from suppliers
-- ============================================
ALTER TABLE suppliers DROP FOREIGN KEY IF EXISTS suppliers_ibfk_1;
ALTER TABLE suppliers DROP FOREIGN KEY IF EXISTS fk_suppliers_company;

DROP INDEX IF EXISTS idx_suppliers_company ON suppliers;
ALTER TABLE suppliers DROP COLUMN IF EXISTS company_id;

SET FOREIGN_KEY_CHECKS = 1;
