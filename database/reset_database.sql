-- ============================================
-- DPP Platform - Complete Database Reset
-- Multi-tenant version (brands/suppliers with API keys)
-- ============================================

USE petersjo_dpp;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- DROP ALL TABLES
-- ============================================

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
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS companies;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- BRANDS (with API keys, no company_id)
-- ============================================

CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_name VARCHAR(255) NOT NULL,
    logo_url VARCHAR(500),
    sub_brand VARCHAR(255),
    parent_company VARCHAR(255),
    trader_name VARCHAR(255),
    trader_address TEXT,
    lei CHAR(20) UNIQUE NULL,
    gs1_company_prefix VARCHAR(12) UNIQUE NULL,
    api_key VARCHAR(64) UNIQUE,
    _is_active BOOLEAN DEFAULT TRUE,
    _last_used_at TIMESTAMP NULL,
    _expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_brands_api_key (api_key)
);

-- ============================================
-- SUPPLIERS (with API keys, no company_id)
-- ============================================

CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(255) NOT NULL,
    supplier_location TEXT,
    facility_registry ENUM('GLN', 'OSH', 'OTHER'),
    facility_identifier VARCHAR(100),
    operator_registry ENUM('GLN', 'EORI', 'VAT', 'OTHER'),
    operator_identifier VARCHAR(100),
    lei CHAR(20) UNIQUE NULL,
    gs1_company_prefix VARCHAR(12) UNIQUE NULL,
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
    _internal_code VARCHAR(50),
    material_name VARCHAR(255) NOT NULL,
    material_type ENUM('textile', 'trim', 'packaging', 'other') DEFAULT 'textile',
    net_weight_per_meter DECIMAL(8,4),
    width_cm INT,
    _is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
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
    FOREIGN KEY (factory_material_id) REFERENCES factory_materials(id) ON DELETE CASCADE
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
    FOREIGN KEY (factory_material_id) REFERENCES factory_materials(id) ON DELETE CASCADE
);

CREATE TABLE factory_material_supply_chain (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factory_material_id INT NOT NULL,
    sequence INT NOT NULL,
    process_stage ENUM('fiber', 'spinning', 'weaving_knitting', 'dyeing', 'finishing', 'cutting_sewing', 'assembly') NOT NULL,
    supplier_name VARCHAR(255) NOT NULL,
    country CHAR(2) NOT NULL,
    facility_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (factory_material_id) REFERENCES factory_materials(id) ON DELETE CASCADE,
    INDEX idx_supply_chain_material (factory_material_id)
);

-- ============================================
-- PRODUCTS (with brand_id, no company_id)
-- ============================================

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,
    gtin VARCHAR(14),
    product_name VARCHAR(255) NOT NULL,
    description TEXT,
    photo_url VARCHAR(500),
    article_number VARCHAR(100),
    commodity_code_system ENUM('HS', 'CN', 'TARIC') DEFAULT 'HS',
    commodity_code_number VARCHAR(20),
    year_of_sale YEAR,
    season_of_sale VARCHAR(50),
    price_currency CHAR(3) DEFAULT 'EUR',
    msrp DECIMAL(10,2),
    category ENUM('clothing', 'accessories', 'footwear', 'textile_article') DEFAULT 'clothing',
    product_group VARCHAR(100),
    line VARCHAR(100),
    garment_type VARCHAR(100),
    age_group ENUM('Adult', 'Child', 'Baby') DEFAULT 'Adult',
    gender ENUM('male', 'female', 'unisex') DEFAULT 'unisex',
    market_segment ENUM('budget', 'mid-price', 'premium', 'luxury') DEFAULT 'mid-price',
    weight_kg DECIMAL(6,3),
    _is_active BOOLEAN DEFAULT TRUE,
    data_carrier_type ENUM('QR', 'RFID', 'NFC', 'Barcode', 'Digital_watermark') DEFAULT 'QR',
    data_carrier_material ENUM('hangtag', 'care_label', 'woven_label', 'printed', 'embedded') DEFAULT 'care_label',
    data_carrier_location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id),
    INDEX idx_products_brand (brand_id),
    INDEX idx_products_gtin (gtin)
);

-- ============================================
-- PRODUCT COMPONENTS
-- ============================================

CREATE TABLE product_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    component_name VARCHAR(100) NOT NULL,
    component_type ENUM('main_fabric', 'lining', 'interlining', 'trim', 'button', 'zipper', 'label', 'thread', 'elastic', 'other') NOT NULL,
    percentage_of_product DECIMAL(5,2),
    _is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE component_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_id INT NOT NULL,
    fiber_type VARCHAR(100) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    country_of_origin CHAR(2),
    is_recycled BOOLEAN DEFAULT FALSE,
    recycled_percentage DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (component_id) REFERENCES product_components(id) ON DELETE CASCADE
);

-- ============================================
-- CARE, COMPLIANCE, CERTIFICATIONS
-- ============================================

CREATE TABLE care_information (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL UNIQUE,
    wash_code VARCHAR(20),
    wash_temperature INT,
    bleach_code VARCHAR(20),
    dry_code VARCHAR(20),
    iron_code VARCHAR(20),
    professional_care_code VARCHAR(20),
    additional_instructions TEXT,
    industrial_wash_compatible BOOLEAN DEFAULT FALSE,
    industrial_wash_max_temp INT,
    industrial_wash_cycles INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE compliance_information (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL UNIQUE,
    contains_svhc BOOLEAN DEFAULT FALSE,
    svhc_details TEXT,
    microfiber_release BOOLEAN DEFAULT FALSE,
    microfiber_details TEXT,
    reach_compliant BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    certification_type ENUM('Oeko_Tex', 'GOTS', 'GRS', 'OCS', 'RCS', 'BCI', 'EU_Ecolabel', 'Blue_Angel', 'Nordic_Swan', 'other') NOT NULL,
    certification_other VARCHAR(255),
    certificate_number VARCHAR(100),
    valid_from DATE,
    valid_until DATE,
    document_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE circularity_information (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL UNIQUE,
    recyclability_score ENUM('A', 'B', 'C', 'D', 'E'),
    recycled_content_percentage DECIMAL(5,2) DEFAULT 0,
    take_back_program BOOLEAN DEFAULT FALSE,
    take_back_instructions TEXT,
    repair_services BOOLEAN DEFAULT FALSE,
    repair_instructions TEXT,
    disassembly_instructions TEXT,
    recommended_disposal TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE sustainability_information (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL UNIQUE,
    carbon_footprint_kg DECIMAL(10,3),
    water_usage_liters DECIMAL(10,2),
    energy_usage_kwh DECIMAL(10,2),
    sustainability_claims TEXT,
    environmental_impact_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ============================================
-- PRODUCT VARIANTS
-- ============================================

CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    size VARCHAR(20),
    size_system ENUM('EU', 'US', 'UK', 'INT', 'Custom') DEFAULT 'EU',
    color_name VARCHAR(100),
    color_code VARCHAR(50),
    _is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_variants_product (product_id)
);

-- ============================================
-- BATCHES (with brand_id)
-- ============================================

CREATE TABLE batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,
    product_variant_id INT NOT NULL,
    batch_number VARCHAR(100) NOT NULL,
    po_number VARCHAR(100),
    quantity INT,
    production_date DATE,
    _status ENUM('planned', 'in_production', 'completed', 'cancelled') DEFAULT 'planned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id),
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id),
    INDEX idx_batches_brand (brand_id),
    INDEX idx_batches_variant (product_variant_id)
);

CREATE TABLE batch_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    factory_material_id INT NOT NULL,
    quantity_used DECIMAL(10,2),
    unit ENUM('meters', 'kg', 'pieces') DEFAULT 'meters',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    FOREIGN KEY (factory_material_id) REFERENCES factory_materials(id)
);

CREATE TABLE batch_suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    supplier_id INT NOT NULL,
    production_stage ENUM('fiber', 'spinning', 'weaving_knitting', 'dyeing', 'finishing', 'cutting_sewing', 'assembly') NOT NULL,
    country_of_origin CHAR(2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- ============================================
-- ITEMS (with brand_id)
-- ============================================

CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,
    batch_id INT NOT NULL,
    sgtin VARCHAR(50) UNIQUE,
    tid VARCHAR(50),
    _status ENUM('active', 'sold', 'returned', 'recycled', 'destroyed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id),
    FOREIGN KEY (batch_id) REFERENCES batches(id),
    INDEX idx_items_brand (brand_id),
    INDEX idx_items_batch (batch_id),
    INDEX idx_items_sgtin (sgtin)
);

-- ============================================
-- DPP EXPORTS
-- ============================================

CREATE TABLE dpp_exports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    item_id INT,
    export_type ENUM('product', 'item') NOT NULL,
    dpp_json JSON,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (item_id) REFERENCES items(id)
);

-- ============================================
-- TEST DATA: BRANDS
-- ============================================

INSERT INTO brands (id, brand_name, logo_url, sub_brand, parent_company, trader_name, trader_address, lei, gs1_company_prefix, api_key, _is_active) VALUES
(1, 'VårdTex', '/images/vardtex-logo.png', 'VårdTex Pro', 'VårdTex AB', 'VårdTex AB', 'Medicinaregatan 8, 413 90 Göteborg, Sweden', '549300VARDTEX00001SE', '7350012345', 'brand_vardtex_test_key_000', TRUE),
(2, 'MediWear', '/images/mediwear-logo.png', 'MediWear Pro', 'MediWear International AB', 'MediWear International AB', 'Industrivägen 45, 171 48 Solna, Sweden', '549300MEDIWEAR0002SE', '7350098765', 'brand_mediwear_test_key_001', TRUE),
(3, 'HealthTex', '/images/healthtex-logo.png', 'HealthTex Care', 'HealthTex Nordic Oy', 'HealthTex Nordic Oy', 'Terveyskatu 12, 00100 Helsinki, Finland', '549300HEALTHTEX03FI', '6430012345', 'brand_healthtex_test_key_002', TRUE),
(4, 'CleanCare Textiles', '/images/cleancare-logo.png', NULL, 'CleanCare Group AS', 'CleanCare Group AS', 'Renholdsveien 8, 0580 Oslo, Norway', '549300CLEANCARE04NO', '7090012345', 'brand_cleancare_test_key_003', TRUE);

-- ============================================
-- TEST DATA: SUPPLIERS
-- ============================================

INSERT INTO suppliers (id, supplier_name, supplier_location, facility_registry, facility_identifier, operator_registry, operator_identifier, lei, gs1_company_prefix, api_key, _is_active) VALUES
(1, 'Têxtil Hospitalar Porto', 'Zona Industrial de Maia, 4470-122 Porto, Portugal', 'GLN', '5609876543210', 'GLN', '5609876543210', '549300PORTOTEXTIL01', '5609876543', 'supplier_porto_test_key_000', TRUE),
(2, 'Baltic Textile Factory', 'Klaipėda FEZ, LT-94101 Klaipėda, Lithuania', 'GLN', '4771234567890', 'GLN', '4771234567890', '549300BALTICTEX02LT', '4771234567', 'supplier_baltic_test_key_001', TRUE),
(3, 'Tunisia Medical Textiles', 'Zone Industrielle, 5000 Monastir, Tunisia', 'GLN', '6191234567890', 'GLN', '6191234567890', '549300TUNISIATEX03T', '6191234567', 'supplier_tunisia_test_key_002', TRUE),
(4, 'Aegean Fabrics SA', 'Organized Industrial Zone, 35410 Izmir, Turkey', 'GLN', '8691234567890', 'GLN', '8691234567890', '549300AEGEANFAB04TR', '8691234567', 'supplier_aegean_test_key_003', TRUE),
(5, 'Shanghai Medical Textile Co', 'Pudong New Area, Shanghai 201203, China', 'GLN', '6901234567890', 'GLN', '6901234567890', '549300SHANGHAITX05C', '6901234567', 'supplier_shanghai_test_key_004', TRUE);

-- ============================================
-- TEST DATA: BRAND-SUPPLIER RELATIONSHIPS
-- ============================================

INSERT INTO brand_suppliers (brand_id, supplier_id, _is_active) VALUES
(1, 1, TRUE),   -- VårdTex <-> Porto
(1, 2, TRUE),   -- VårdTex <-> Baltic
(2, 1, TRUE),   -- MediWear <-> Porto
(2, 3, TRUE),   -- MediWear <-> Tunisia
(2, 4, TRUE),   -- MediWear <-> Aegean
(3, 2, TRUE),   -- HealthTex <-> Baltic
(3, 4, TRUE),   -- HealthTex <-> Aegean
(4, 5, TRUE),   -- CleanCare <-> Shanghai
(1, 3, FALSE);  -- VårdTex <-> Tunisia (inactive)

-- ============================================
-- TEST DATA: FACTORY MATERIALS
-- ============================================

-- Porto materials
INSERT INTO factory_materials (id, supplier_id, _internal_code, material_name, material_type, net_weight_per_meter, width_cm, _is_active) VALUES
(1, 1, 'PC-6535-155', 'PolyCotton Twill 65/35 155gsm', 'textile', 0.155, 150, TRUE),
(2, 1, 'PC-6535-195', 'PolyCotton Twill 65/35 195gsm', 'textile', 0.195, 150, TRUE),
(3, 1, 'PES-ANTI-170', 'Polyester Antimicrobial 170gsm', 'textile', 0.170, 160, TRUE),
(4, 1, 'COT-ORG-160', 'Organic Cotton Jersey 160gsm', 'textile', 0.160, 140, TRUE),
(5, 1, 'PES-BARR-120', 'Polyester Barrier Fabric 120gsm', 'textile', 0.120, 150, TRUE);

-- Baltic materials
INSERT INTO factory_materials (id, supplier_id, _internal_code, material_name, material_type, net_weight_per_meter, width_cm, _is_active) VALUES
(10, 2, 'LT-POLY-180', 'Baltic Polyester 180gsm', 'textile', 0.180, 155, TRUE),
(11, 2, 'LT-BLEND-200', 'Baltic Blend 50/50 200gsm', 'textile', 0.200, 150, TRUE),
(12, 2, 'LT-ANTI-165', 'Baltic Antimicrobial 165gsm', 'textile', 0.165, 160, TRUE);

-- Tunisia materials
INSERT INTO factory_materials (id, supplier_id, _internal_code, material_name, material_type, net_weight_per_meter, width_cm, _is_active) VALUES
(20, 3, 'TN-COT-140', 'Tunisia Cotton Poplin 140gsm', 'textile', 0.140, 145, TRUE),
(21, 3, 'TN-POLY-160', 'Tunisia Polyester 160gsm', 'textile', 0.160, 150, TRUE),
(22, 3, 'TN-ECO-150', 'Tunisia Eco-Blend 150gsm', 'textile', 0.150, 150, TRUE);

-- Aegean materials
INSERT INTO factory_materials (id, supplier_id, _internal_code, material_name, material_type, net_weight_per_meter, width_cm, _is_active) VALUES
(30, 4, 'TR-PREM-185', 'Aegean Premium Blend 185gsm', 'textile', 0.185, 155, TRUE),
(31, 4, 'TR-SURG-120', 'Aegean Surgical Grade 120gsm', 'textile', 0.120, 160, TRUE),
(32, 4, 'TR-FIRE-210', 'Aegean Fire Retardant 210gsm', 'textile', 0.210, 150, TRUE);

-- Shanghai materials
INSERT INTO factory_materials (id, supplier_id, _internal_code, material_name, material_type, net_weight_per_meter, width_cm, _is_active) VALUES
(40, 5, 'CN-POLY-170', 'Shanghai Polyester 170gsm', 'textile', 0.170, 150, TRUE),
(41, 5, 'CN-BLEND-190', 'Shanghai PC Blend 190gsm', 'textile', 0.190, 155, TRUE),
(42, 5, 'CN-DISP-080', 'Shanghai Disposable SMS 80gsm', 'textile', 0.080, 160, TRUE);

-- ============================================
-- TEST DATA: MATERIAL COMPOSITIONS
-- ============================================

INSERT INTO factory_material_compositions (factory_material_id, fiber_type, percentage, fiber_source, is_recycled) VALUES
(1, 'Polyester', 65.00, 'conventional', FALSE),
(1, 'Cotton', 35.00, 'conventional', FALSE),
(2, 'Polyester', 65.00, 'conventional', FALSE),
(2, 'Cotton', 35.00, 'conventional', FALSE),
(3, 'Polyester', 100.00, 'conventional', FALSE),
(4, 'Cotton', 100.00, 'organic', FALSE),
(5, 'Polyester', 100.00, 'conventional', FALSE),
(10, 'Polyester', 100.00, 'conventional', FALSE),
(11, 'Polyester', 50.00, 'conventional', FALSE),
(11, 'Cotton', 50.00, 'conventional', FALSE),
(12, 'Polyester', 100.00, 'conventional', FALSE),
(20, 'Cotton', 100.00, 'conventional', FALSE),
(21, 'Polyester', 100.00, 'conventional', FALSE),
(22, 'Polyester', 50.00, 'recycled', TRUE),
(22, 'Cotton', 50.00, 'organic', FALSE),
(30, 'Polyester', 65.00, 'conventional', FALSE),
(30, 'Cotton', 35.00, 'conventional', FALSE),
(31, 'Polypropylene', 100.00, 'conventional', FALSE),
(32, 'Polyester', 80.00, 'conventional', FALSE),
(32, 'Aramid', 20.00, 'conventional', FALSE),
(40, 'Polyester', 100.00, 'conventional', FALSE),
(41, 'Polyester', 65.00, 'conventional', FALSE),
(41, 'Cotton', 35.00, 'conventional', FALSE),
(42, 'Polypropylene', 100.00, 'conventional', FALSE);

-- ============================================
-- TEST DATA: PRODUCTS
-- ============================================

-- VårdTex products
INSERT INTO products (id, brand_id, gtin, product_name, description, article_number, commodity_code_system, commodity_code_number, year_of_sale, price_currency, msrp, category, product_group, garment_type, age_group, gender, _is_active, data_carrier_type, data_carrier_material, data_carrier_location) VALUES
(1, 1, '7350012345001', 'Scrubs Tunika', 'Klassisk V-ringad scrubstopp', 'VT-SCR-TOP-001', 'HS', '6211339000', 2025, 'EUR', 34.95, 'clothing', 'Top', 'Tunic', 'Adult', 'unisex', TRUE, 'RFID', 'woven_label', 'side_seam'),
(2, 1, '7350012345002', 'Scrubs Byxa', 'Bekväm scrubsbyxa med resår', 'VT-SCR-BTM-001', 'HS', '6203439000', 2025, 'EUR', 32.95, 'clothing', 'Bottom', 'Pants', 'Adult', 'unisex', TRUE, 'RFID', 'woven_label', 'waistband'),
(3, 1, '7350012345003', 'Labbrock', 'Klassisk vit labbrock', 'VT-LAB-001', 'HS', '6211329000', 2025, 'EUR', 59.95, 'clothing', 'Top', 'Lab coat', 'Adult', 'unisex', TRUE, 'RFID', 'woven_label', 'neck_label');

-- MediWear products
INSERT INTO products (id, brand_id, gtin, product_name, description, article_number, commodity_code_system, commodity_code_number, year_of_sale, price_currency, msrp, category, product_group, garment_type, age_group, gender, _is_active, data_carrier_type, data_carrier_material, data_carrier_location) VALUES
(10, 2, '7350098765001', 'MediWear Scrub Top', 'Premium scrub top', 'MW-SCR-001', 'HS', '6211339000', 2025, 'EUR', 39.95, 'clothing', 'Top', 'Tunic', 'Adult', 'unisex', TRUE, 'RFID', 'woven_label', 'side_seam'),
(11, 2, '7350098765002', 'MediWear Scrub Pants', 'Ergonomic scrub pants', 'MW-SCR-002', 'HS', '6203439000', 2025, 'EUR', 37.95, 'clothing', 'Bottom', 'Pants', 'Adult', 'unisex', TRUE, 'RFID', 'woven_label', 'waistband');

-- HealthTex products
INSERT INTO products (id, brand_id, gtin, product_name, description, article_number, commodity_code_system, commodity_code_number, year_of_sale, price_currency, msrp, category, product_group, garment_type, age_group, gender, _is_active, data_carrier_type, data_carrier_material, data_carrier_location) VALUES
(20, 3, '6430012345001', 'HealthTex Basic Tunic', 'Affordable healthcare tunic', 'HT-BAS-001', 'HS', '6211339000', 2025, 'EUR', 24.95, 'clothing', 'Top', 'Tunic', 'Adult', 'unisex', TRUE, 'QR', 'care_label', 'neck_label'),
(21, 3, '6430012345002', 'HealthTex Comfort Pants', 'Elastic waist pants', 'HT-BAS-002', 'HS', '6203439000', 2025, 'EUR', 22.95, 'clothing', 'Bottom', 'Pants', 'Adult', 'unisex', TRUE, 'QR', 'care_label', 'waistband');

-- CleanCare products
INSERT INTO products (id, brand_id, gtin, product_name, description, article_number, commodity_code_system, commodity_code_number, year_of_sale, price_currency, msrp, category, product_group, garment_type, age_group, gender, _is_active, data_carrier_type, data_carrier_material, data_carrier_location) VALUES
(30, 4, '7090012345001', 'CleanCare Disposable Gown', 'Single-use protective gown', 'CC-DISP-001', 'HS', '6210109000', 2025, 'EUR', 4.95, 'clothing', 'Top', 'Surgical gown', 'Adult', 'unisex', TRUE, 'QR', 'hangtag', 'attached'),
(31, 4, '7090012345002', 'CleanCare Isolation Gown', 'Level 2 isolation gown', 'CC-ISO-001', 'HS', '6210109000', 2025, 'EUR', 12.95, 'clothing', 'Top', 'Surgical gown', 'Adult', 'unisex', TRUE, 'QR', 'hangtag', 'attached');

-- ============================================
-- TEST DATA: PRODUCT VARIANTS
-- ============================================

-- VårdTex variants
INSERT INTO product_variants (id, product_id, sku, size, size_system, color_name, color_code, _is_active) VALUES
(1, 1, 'VT-SCR-TOP-001-CBL-S', 'S', 'EU', 'Ceil Blue', 'blue', TRUE),
(2, 1, 'VT-SCR-TOP-001-CBL-M', 'M', 'EU', 'Ceil Blue', 'blue', TRUE),
(3, 1, 'VT-SCR-TOP-001-CBL-L', 'L', 'EU', 'Ceil Blue', 'blue', TRUE),
(4, 2, 'VT-SCR-BTM-001-CBL-S', 'S', 'EU', 'Ceil Blue', 'blue', TRUE),
(5, 2, 'VT-SCR-BTM-001-CBL-M', 'M', 'EU', 'Ceil Blue', 'blue', TRUE),
(6, 3, 'VT-LAB-001-WHT-M', 'M', 'EU', 'White', 'white', TRUE),
(7, 3, 'VT-LAB-001-WHT-L', 'L', 'EU', 'White', 'white', TRUE);

-- MediWear variants
INSERT INTO product_variants (id, product_id, sku, size, size_system, color_name, color_code, _is_active) VALUES
(50, 10, 'MW-SCR-001-TL-S', 'S', 'EU', 'Teal', 'teal', TRUE),
(51, 10, 'MW-SCR-001-TL-M', 'M', 'EU', 'Teal', 'teal', TRUE),
(52, 10, 'MW-SCR-001-TL-L', 'L', 'EU', 'Teal', 'teal', TRUE),
(53, 11, 'MW-SCR-002-TL-S', 'S', 'EU', 'Teal', 'teal', TRUE),
(54, 11, 'MW-SCR-002-TL-M', 'M', 'EU', 'Teal', 'teal', TRUE);

-- HealthTex variants
INSERT INTO product_variants (id, product_id, sku, size, size_system, color_name, color_code, _is_active) VALUES
(60, 20, 'HT-BAS-001-WHT-M', 'M', 'EU', 'White', 'white', TRUE),
(61, 20, 'HT-BAS-001-WHT-L', 'L', 'EU', 'White', 'white', TRUE),
(62, 21, 'HT-BAS-002-WHT-M', 'M', 'EU', 'White', 'white', TRUE),
(63, 21, 'HT-BAS-002-WHT-L', 'L', 'EU', 'White', 'white', TRUE);

-- CleanCare variants
INSERT INTO product_variants (id, product_id, sku, size, size_system, color_name, color_code, _is_active) VALUES
(70, 30, 'CC-DISP-001-BLU-M', 'M', 'EU', 'Blue', 'blue', TRUE),
(71, 30, 'CC-DISP-001-BLU-L', 'L', 'EU', 'Blue', 'blue', TRUE),
(72, 31, 'CC-ISO-001-YLW-M', 'M', 'EU', 'Yellow', 'yellow', TRUE),
(73, 31, 'CC-ISO-001-YLW-L', 'L', 'EU', 'Yellow', 'yellow', TRUE);

-- ============================================
-- TEST DATA: BATCHES
-- ============================================

-- VårdTex batches
INSERT INTO batches (id, brand_id, product_variant_id, batch_number, po_number, quantity, production_date, _status) VALUES
(1, 1, 1, 'VT-2025-001', 'PO-VT-2025-001', 500, '2025-01-05', 'completed'),
(2, 1, 2, 'VT-2025-002', 'PO-VT-2025-001', 800, '2025-01-05', 'completed'),
(3, 1, 6, 'VT-2025-003', 'PO-VT-2025-002', 200, '2025-01-08', 'completed');

-- MediWear batches
INSERT INTO batches (id, brand_id, product_variant_id, batch_number, po_number, quantity, production_date, _status) VALUES
(10, 2, 50, 'MW-2025-001', 'PO-MW-2025-001', 500, '2025-01-10', 'completed'),
(11, 2, 51, 'MW-2025-002', 'PO-MW-2025-001', 800, '2025-01-10', 'completed');

-- HealthTex batches
INSERT INTO batches (id, brand_id, product_variant_id, batch_number, po_number, quantity, production_date, _status) VALUES
(20, 3, 60, 'HT-2025-001', 'PO-HT-2025-001', 1000, '2025-01-15', 'completed'),
(21, 3, 61, 'HT-2025-002', 'PO-HT-2025-001', 1000, '2025-01-15', 'completed');

-- CleanCare batches
INSERT INTO batches (id, brand_id, product_variant_id, batch_number, po_number, quantity, production_date, _status) VALUES
(30, 4, 70, 'CC-2025-001', 'PO-CC-2025-001', 5000, '2025-01-20', 'completed'),
(31, 4, 72, 'CC-2025-002', 'PO-CC-2025-002', 2000, '2025-01-22', 'in_production');

-- ============================================
-- TEST DATA: BATCH MATERIALS
-- ============================================

INSERT INTO batch_materials (batch_id, factory_material_id, quantity_used, unit) VALUES
(1, 1, 100.5, 'meters'),
(2, 1, 160.0, 'meters'),
(3, 3, 50.0, 'meters'),
(10, 1, 100.5, 'meters'),
(11, 2, 160.0, 'meters'),
(20, 10, 180.0, 'meters'),
(21, 30, 180.0, 'meters'),
(30, 42, 400.0, 'meters'),
(31, 42, 160.0, 'meters');

-- ============================================
-- TEST DATA: BATCH SUPPLIERS
-- ============================================

INSERT INTO batch_suppliers (batch_id, supplier_id, production_stage, country_of_origin) VALUES
(1, 1, 'cutting_sewing', 'PT'),
(2, 1, 'cutting_sewing', 'PT'),
(3, 1, 'cutting_sewing', 'PT'),
(10, 1, 'cutting_sewing', 'PT'),
(11, 1, 'cutting_sewing', 'PT'),
(20, 2, 'cutting_sewing', 'LT'),
(21, 4, 'cutting_sewing', 'TR'),
(30, 5, 'cutting_sewing', 'CN'),
(31, 5, 'cutting_sewing', 'CN');

-- ============================================
-- TEST DATA: ITEMS
-- ============================================

-- VårdTex items
INSERT INTO items (id, brand_id, batch_id, sgtin, tid, _status) VALUES
(1, 1, 1, '7350012345001.00001', 'E280689400001001', 'active'),
(2, 1, 1, '7350012345001.00002', 'E280689400001002', 'active'),
(3, 1, 2, '7350012345001.00003', 'E280689400001003', 'active'),
(4, 1, 3, '7350012345003.00001', 'E280689400001004', 'active');

-- MediWear items
INSERT INTO items (id, brand_id, batch_id, sgtin, tid, _status) VALUES
(100, 2, 10, '7350098765001.00001', 'E280689400004002', 'active'),
(101, 2, 10, '7350098765001.00002', 'E280689400004003', 'active'),
(102, 2, 11, '7350098765001.00003', 'E280689400004004', 'active');

-- HealthTex items
INSERT INTO items (id, brand_id, batch_id, sgtin, tid, _status) VALUES
(200, 3, 20, '6430012345001.00001', 'E280689400005001', 'active'),
(201, 3, 20, '6430012345001.00002', 'E280689400005002', 'active'),
(202, 3, 21, '6430012345002.00001', 'E280689400005003', 'active');

-- CleanCare items
INSERT INTO items (id, brand_id, batch_id, sgtin, tid, _status) VALUES
(300, 4, 30, '7090012345001.00001', 'E280689400006001', 'active'),
(301, 4, 30, '7090012345001.00002', 'E280689400006002', 'active'),
(302, 4, 31, '7090012345002.00001', 'E280689400006003', 'active');

-- ============================================
-- SUMMARY
-- ============================================
SELECT 'Database reset complete!' AS status;
SELECT COUNT(*) AS brands FROM brands;
SELECT COUNT(*) AS suppliers FROM suppliers;
SELECT COUNT(*) AS brand_supplier_relations FROM brand_suppliers;
SELECT COUNT(*) AS products FROM products;
SELECT COUNT(*) AS batches FROM batches;
SELECT COUNT(*) AS items FROM items;
