-- ============================================
-- DPP Platform - Multi-tenant Migration
-- ============================================
-- Adds API key authentication and company_id filtering

USE petersjo_dpp;

-- ============================================
-- 1. UPPDATERA COMPANIES-TABELLEN
-- ============================================

ALTER TABLE companies
ADD COLUMN api_key VARCHAR(64) UNIQUE AFTER org_number,
ADD COLUMN _is_active BOOLEAN DEFAULT TRUE AFTER api_key,
ADD COLUMN _last_used_at TIMESTAMP NULL AFTER _is_active,
ADD COLUMN _expires_at TIMESTAMP NULL AFTER _last_used_at;

-- Generera API-nycklar för befintliga företag
UPDATE companies SET api_key = CONCAT('dpp_', LOWER(HEX(RANDOM_BYTES(24)))) WHERE api_key IS NULL;

-- ============================================
-- 2. LÄGG TILL company_id PÅ PRODUCTS
-- ============================================

-- Först lägg till kolumnen som nullable
ALTER TABLE products
ADD COLUMN company_id INT NULL AFTER id;

-- Populera från brand → company relationship
UPDATE products p
JOIN brands b ON p.brand_id = b.id
SET p.company_id = b.company_id;

-- Gör kolumnen NOT NULL och lägg till FK + index
ALTER TABLE products
MODIFY COLUMN company_id INT NOT NULL,
ADD CONSTRAINT fk_products_company FOREIGN KEY (company_id) REFERENCES companies(id),
ADD INDEX idx_products_company (company_id);

-- ============================================
-- 3. LÄGG TILL company_id PÅ BATCHES
-- ============================================

ALTER TABLE batches
ADD COLUMN company_id INT NULL AFTER id;

-- Populera från variant → product → brand → company
UPDATE batches b
JOIN product_variants pv ON b.product_variant_id = pv.id
JOIN products p ON pv.product_id = p.id
SET b.company_id = p.company_id;

ALTER TABLE batches
MODIFY COLUMN company_id INT NOT NULL,
ADD CONSTRAINT fk_batches_company FOREIGN KEY (company_id) REFERENCES companies(id),
ADD INDEX idx_batches_company (company_id);

-- ============================================
-- 4. LÄGG TILL company_id PÅ ITEMS
-- ============================================

ALTER TABLE items
ADD COLUMN company_id INT NULL AFTER id;

-- Populera från batch → company
UPDATE items i
JOIN batches b ON i.batch_id = b.id
SET i.company_id = b.company_id;

ALTER TABLE items
MODIFY COLUMN company_id INT NOT NULL,
ADD CONSTRAINT fk_items_company FOREIGN KEY (company_id) REFERENCES companies(id),
ADD INDEX idx_items_company (company_id);

-- ============================================
-- 5. VERIFIERING
-- ============================================

-- Visa genererade API-nycklar
SELECT id, name, api_key, _is_active FROM companies;

-- Verifiera att company_id är korrekt populerat
SELECT 'products' as tbl, COUNT(*) as total, COUNT(company_id) as with_company FROM products
UNION ALL
SELECT 'batches', COUNT(*), COUNT(company_id) FROM batches
UNION ALL
SELECT 'items', COUNT(*), COUNT(company_id) FROM items;
