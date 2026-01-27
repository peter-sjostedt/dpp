-- ============================================
-- DPP Platform - Del 1: Drop + Grundstruktur
-- Multi-tenant version (brands/suppliers with API keys)
-- ============================================

USE petersjo_dpp;

SET FOREIGN_KEY_CHECKS = 0;

-- DROP ALLA TABELLER
DROP TABLE IF EXISTS validation_status;
DROP TABLE IF EXISTS dpp_exports;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS batch_materials;
DROP TABLE IF EXISTS batch_suppliers;
DROP TABLE IF EXISTS batches;
DROP TABLE IF EXISTS product_variants;
DROP TABLE IF EXISTS sustainability_information;
DROP TABLE IF EXISTS circularity_information;
DROP TABLE IF EXISTS chemical_compliance;
DROP TABLE IF EXISTS certifications;
DROP TABLE IF EXISTS compliance_information;
DROP TABLE IF EXISTS care_information;
DROP TABLE IF EXISTS component_materials;
DROP TABLE IF EXISTS product_components;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS factory_material_supply_chain;
DROP TABLE IF EXISTS factory_material_certifications;
DROP TABLE IF EXISTS factory_material_compositions;
DROP TABLE IF EXISTS factory_materials;
DROP TABLE IF EXISTS brand_suppliers;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS brands;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- BRANDS (with API keys, LEI, GS1)
-- ============================================

CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_name VARCHAR(255) NOT NULL,
    logo_url VARCHAR(500),
    sub_brand VARCHAR(255),
    parent_company VARCHAR(255),
    trader_name VARCHAR(255),
    trader_address TEXT,
    lei CHAR(20) UNIQUE NULL COMMENT 'Legal Entity Identifier (ISO 17442)',
    gs1_company_prefix VARCHAR(12) UNIQUE NULL COMMENT 'GS1 Company Prefix (6-12 digits)',
    api_key VARCHAR(64) UNIQUE,
    _is_active BOOLEAN DEFAULT TRUE,
    _last_used_at TIMESTAMP NULL,
    _expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_brands_api_key (api_key)
);

-- ============================================
-- SUPPLIERS (with API keys, LEI, GS1)
-- ============================================

CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(255) NOT NULL,
    supplier_location TEXT,
    facility_registry ENUM('GLN', 'OSH', 'OTHER'),
    facility_identifier VARCHAR(100),
    operator_registry ENUM('GLN', 'EORI', 'VAT', 'OTHER'),
    operator_identifier VARCHAR(100),
    lei CHAR(20) UNIQUE NULL COMMENT 'Legal Entity Identifier (ISO 17442)',
    gs1_company_prefix VARCHAR(12) UNIQUE NULL COMMENT 'GS1 Company Prefix (6-12 digits)',
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
    UNIQUE KEY unique_brand_supplier (brand_id, supplier_id),
    INDEX idx_brand_suppliers_brand (brand_id),
    INDEX idx_brand_suppliers_supplier (supplier_id)
);

-- ============================================
-- FACTORY MATERIALS
-- ============================================

CREATE TABLE factory_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    _internal_code VARCHAR(100),
    material_name VARCHAR(255) NOT NULL,
    material_type ENUM('textile', 'trim', 'packaging', 'other') DEFAULT 'textile',
    net_weight_per_meter DECIMAL(8,4),
    width_cm INT,
    _is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_material_code (supplier_id, _internal_code),
    INDEX idx_factory_materials_supplier (supplier_id)
);

CREATE TABLE factory_material_compositions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factory_material_id INT NOT NULL,
    fiber_type VARCHAR(100) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    fiber_source ENUM('conventional', 'organic', 'recycled', 'bio-based') DEFAULT 'conventional',
    material_trademark VARCHAR(255),
    is_recycled BOOLEAN DEFAULT FALSE,
    recycled_percentage DECIMAL(5,2),
    recycled_source ENUM('pre_consumer', 'post_consumer', 'mixed'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (factory_material_id) REFERENCES factory_materials(id) ON DELETE CASCADE,
    INDEX idx_compositions_material (factory_material_id)
);

CREATE TABLE factory_material_certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factory_material_id INT NOT NULL,
    certification_type ENUM('Oeko_Tex', 'GOTS', 'GRS', 'OCS', 'RCS', 'BCI', 'STANDARD_100', 'other') NOT NULL,
    certification_other VARCHAR(255),
    scope ENUM('material', 'facility', 'product') DEFAULT 'material',
    certificate_number VARCHAR(100),
    valid_from DATE,
    valid_until DATE,
    document_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (factory_material_id) REFERENCES factory_materials(id) ON DELETE CASCADE,
    INDEX idx_certifications_material (factory_material_id),
    INDEX idx_certs_expiry (valid_until)
);

CREATE TABLE factory_material_supply_chain (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factory_material_id INT NOT NULL,
    sequence INT NOT NULL DEFAULT 0,
    process_stage ENUM('fiber', 'spinning', 'weaving_knitting', 'dyeing', 'finishing', 'cutting_sewing', 'assembly') NOT NULL,
    supplier_name VARCHAR(255) NOT NULL,
    country CHAR(2) NOT NULL,
    facility_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (factory_material_id) REFERENCES factory_materials(id) ON DELETE CASCADE,
    INDEX idx_supply_chain_material (factory_material_id)
);
