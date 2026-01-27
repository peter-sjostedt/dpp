-- ============================================
-- DPP Foundation Schema
-- Brands, Suppliers, Brand-Supplier relations
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS brand_suppliers;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS brands;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- BRANDS
-- ============================================

CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_name VARCHAR(255) NOT NULL,
    logo_url VARCHAR(500),
    sub_brand VARCHAR(255),
    parent_company VARCHAR(255),
    trader VARCHAR(255),
    trader_location TEXT,

    -- Globala identifierare
    lei CHAR(20) UNIQUE NULL COMMENT 'Legal Entity Identifier (ISO 17442)',
    gs1_company_prefix VARCHAR(12) UNIQUE NULL COMMENT 'GS1 Company Prefix',

    -- API & tenant
    api_key VARCHAR(64) UNIQUE,
    _is_active BOOLEAN DEFAULT TRUE,
    _last_used_at TIMESTAMP NULL,
    _expires_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_brands_api_key (api_key)
);

-- ============================================
-- SUPPLIERS
-- ============================================

CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(255) NOT NULL,
    supplier_location TEXT,
    facility_registry VARCHAR(50) COMMENT '202,00 - GLN, OSH',
    facility_identifier VARCHAR(100) COMMENT '202,10',
    operator_registry VARCHAR(50) COMMENT '203,00',
    operator_identifier VARCHAR(100) COMMENT '203,10',
    country_of_origin_confection VARCHAR(2) COMMENT '204,00 - ISO country code',
    country_of_origin_dyeing VARCHAR(2) COMMENT '205,00',
    country_of_origin_weaving VARCHAR(2) COMMENT '206,00',

    -- Globala identifierare
    lei CHAR(20) UNIQUE NULL COMMENT 'Legal Entity Identifier (ISO 17442)',
    gs1_company_prefix VARCHAR(12) UNIQUE NULL COMMENT 'GS1 Company Prefix',

    -- API & tenant
    api_key VARCHAR(64) UNIQUE,
    _is_active BOOLEAN DEFAULT TRUE,
    _last_used_at TIMESTAMP NULL,
    _expires_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_suppliers_api_key (api_key)
);

-- ============================================
-- BRAND-SUPPLIER RELATIONSHIPS
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
    UNIQUE KEY unique_relation (brand_id, supplier_id),
    INDEX idx_brand_suppliers_brand (brand_id),
    INDEX idx_brand_suppliers_supplier (supplier_id)
);
