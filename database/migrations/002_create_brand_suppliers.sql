-- ============================================
-- Migration 002: Create brand_suppliers junction table
-- ============================================

USE petersjo_dpp;

-- ============================================
-- CREATE BRAND_SUPPLIERS JUNCTION TABLE
-- ============================================

CREATE TABLE brand_suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,
    supplier_id INT NOT NULL,
    _is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_brand_supplier (brand_id, supplier_id),
    INDEX idx_brand_suppliers_brand (brand_id),
    INDEX idx_brand_suppliers_supplier (supplier_id)
);

-- ============================================
-- POPULATE FROM EXISTING RELATIONSHIPS
-- ============================================

-- Create relationships based on existing company ownership
-- (brands and suppliers that share the same company_id)
INSERT IGNORE INTO brand_suppliers (brand_id, supplier_id)
SELECT DISTINCT br.id, s.id
FROM brands br
JOIN suppliers s ON br.company_id = s.company_id;

-- Also create relationships based on batch_suppliers
-- (suppliers that have been used in batches for products of each brand)
INSERT IGNORE INTO brand_suppliers (brand_id, supplier_id)
SELECT DISTINCT p.brand_id, bs.supplier_id
FROM batch_suppliers bs
JOIN batches b ON bs.batch_id = b.id
JOIN product_variants pv ON b.product_variant_id = pv.id
JOIN products p ON pv.product_id = p.id
WHERE p.brand_id IS NOT NULL;
