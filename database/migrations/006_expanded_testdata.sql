-- ============================================
-- Migration 006: Expanded test data for multi-tenant testing
-- ============================================

USE petersjo_dpp;

-- ============================================
-- ADDITIONAL BRANDS
-- ============================================

INSERT INTO brands (id, brand_name, logo_url, sub_brand, parent_company, trader_name, trader_address, api_key, _is_active) VALUES
(2, 'MediWear', '/images/mediwear-logo.png', 'MediWear Pro', 'MediWear International AB', 'MediWear International AB', 'Industrivägen 45, 171 48 Solna, Sweden', 'brand_mediwear_test_key_001', TRUE),
(3, 'HealthTex', '/images/healthtex-logo.png', 'HealthTex Care', 'HealthTex Nordic Oy', 'HealthTex Nordic Oy', 'Terveyskatu 12, 00100 Helsinki, Finland', 'brand_healthtex_test_key_002', TRUE),
(4, 'CleanCare Textiles', '/images/cleancare-logo.png', NULL, 'CleanCare Group AS', 'CleanCare Group AS', 'Renholdsveien 8, 0580 Oslo, Norway', 'brand_cleancare_test_key_003', TRUE);

-- Update existing brand with test API key
UPDATE brands SET api_key = 'brand_vardtex_test_key_000' WHERE id = 1 AND api_key IS NULL;

-- ============================================
-- ADDITIONAL SUPPLIERS (Factories)
-- ============================================

INSERT INTO suppliers (id, supplier_name, supplier_location, facility_registry, facility_identifier, operator_registry, operator_identifier, api_key, _is_active) VALUES
(2, 'Baltic Textile Factory', 'Klaipėda FEZ, LT-94101 Klaipėda, Lithuania', 'GLN', '4771234567890', 'GLN', '4771234567890', 'supplier_baltic_test_key_001', TRUE),
(3, 'Tunisia Medical Textiles', 'Zone Industrielle, 5000 Monastir, Tunisia', 'GLN', '6191234567890', 'GLN', '6191234567890', 'supplier_tunisia_test_key_002', TRUE),
(4, 'Aegean Fabrics SA', 'Organized Industrial Zone, 35410 Izmir, Turkey', 'GLN', '8691234567890', 'GLN', '8691234567890', 'supplier_aegean_test_key_003', TRUE),
(5, 'Shanghai Medical Textile Co', 'Pudong New Area, Shanghai 201203, China', 'GLN', '6901234567890', 'GLN', '6901234567890', 'supplier_shanghai_test_key_004', TRUE);

-- Update existing supplier with test API key
UPDATE suppliers SET api_key = 'supplier_porto_test_key_000' WHERE id = 1 AND api_key IS NULL;

-- ============================================
-- BRAND-SUPPLIER RELATIONSHIPS
-- ============================================

-- Clear any auto-generated relationships and set up explicit ones
DELETE FROM brand_suppliers;

INSERT INTO brand_suppliers (brand_id, supplier_id, _is_active) VALUES
-- VårdTex works with Porto (existing) and Baltic
(1, 1, TRUE),   -- VårdTex <-> Porto
(1, 2, TRUE),   -- VårdTex <-> Baltic

-- MediWear works with Porto, Tunisia, and Aegean
(2, 1, TRUE),   -- MediWear <-> Porto
(2, 3, TRUE),   -- MediWear <-> Tunisia
(2, 4, TRUE),   -- MediWear <-> Aegean

-- HealthTex works with Baltic and Aegean
(3, 2, TRUE),   -- HealthTex <-> Baltic
(3, 4, TRUE),   -- HealthTex <-> Aegean

-- CleanCare works only with Shanghai (exclusive)
(4, 5, TRUE),   -- CleanCare <-> Shanghai

-- An inactive relationship (for testing)
(1, 3, FALSE);  -- VårdTex <-> Tunisia (inactive)

-- ============================================
-- ADDITIONAL FACTORY MATERIALS (for new suppliers)
-- ============================================

-- Baltic Textile Factory materials
INSERT INTO factory_materials (id, supplier_id, _internal_code, material_name, material_type, net_weight_per_meter, width_cm, _is_active) VALUES
(10, 2, 'LT-POLY-180', 'Baltic Polyester 180gsm', 'textile', 0.180, 155, TRUE),
(11, 2, 'LT-BLEND-200', 'Baltic Blend 50/50 200gsm', 'textile', 0.200, 150, TRUE),
(12, 2, 'LT-ANTI-165', 'Baltic Antimicrobial 165gsm', 'textile', 0.165, 160, TRUE);

-- Tunisia Medical Textiles materials
INSERT INTO factory_materials (id, supplier_id, _internal_code, material_name, material_type, net_weight_per_meter, width_cm, _is_active) VALUES
(20, 3, 'TN-COT-140', 'Tunisia Cotton Poplin 140gsm', 'textile', 0.140, 145, TRUE),
(21, 3, 'TN-POLY-160', 'Tunisia Polyester 160gsm', 'textile', 0.160, 150, TRUE),
(22, 3, 'TN-ECO-150', 'Tunisia Eco-Blend 150gsm', 'textile', 0.150, 150, TRUE);

-- Aegean Fabrics materials
INSERT INTO factory_materials (id, supplier_id, _internal_code, material_name, material_type, net_weight_per_meter, width_cm, _is_active) VALUES
(30, 4, 'TR-PREM-185', 'Aegean Premium Blend 185gsm', 'textile', 0.185, 155, TRUE),
(31, 4, 'TR-SURG-120', 'Aegean Surgical Grade 120gsm', 'textile', 0.120, 160, TRUE),
(32, 4, 'TR-FIRE-210', 'Aegean Fire Retardant 210gsm', 'textile', 0.210, 150, TRUE);

-- Shanghai Medical Textile materials
INSERT INTO factory_materials (id, supplier_id, _internal_code, material_name, material_type, net_weight_per_meter, width_cm, _is_active) VALUES
(40, 5, 'CN-POLY-170', 'Shanghai Polyester 170gsm', 'textile', 0.170, 150, TRUE),
(41, 5, 'CN-BLEND-190', 'Shanghai PC Blend 190gsm', 'textile', 0.190, 155, TRUE),
(42, 5, 'CN-DISP-080', 'Shanghai Disposable SMS 80gsm', 'textile', 0.080, 160, TRUE);

-- ============================================
-- COMPOSITIONS FOR NEW MATERIALS
-- ============================================

-- Baltic materials compositions
INSERT INTO factory_material_compositions (factory_material_id, fiber_type, percentage, fiber_source, is_recycled) VALUES
(10, 'Polyester', 100.00, 'conventional', FALSE),
(11, 'Polyester', 50.00, 'conventional', FALSE),
(11, 'Cotton', 50.00, 'conventional', FALSE),
(12, 'Polyester', 100.00, 'conventional', FALSE);

-- Tunisia materials compositions
INSERT INTO factory_material_compositions (factory_material_id, fiber_type, percentage, fiber_source, is_recycled) VALUES
(20, 'Cotton', 100.00, 'conventional', FALSE),
(21, 'Polyester', 100.00, 'conventional', FALSE),
(22, 'Polyester', 50.00, 'recycled', TRUE),
(22, 'Cotton', 50.00, 'organic', FALSE);

-- Aegean materials compositions
INSERT INTO factory_material_compositions (factory_material_id, fiber_type, percentage, fiber_source, is_recycled) VALUES
(30, 'Polyester', 65.00, 'conventional', FALSE),
(30, 'Cotton', 35.00, 'conventional', FALSE),
(31, 'Polypropylene', 100.00, 'conventional', FALSE),
(32, 'Polyester', 80.00, 'conventional', FALSE),
(32, 'Aramid', 20.00, 'conventional', FALSE);

-- Shanghai materials compositions
INSERT INTO factory_material_compositions (factory_material_id, fiber_type, percentage, fiber_source, is_recycled) VALUES
(40, 'Polyester', 100.00, 'conventional', FALSE),
(41, 'Polyester', 65.00, 'conventional', FALSE),
(41, 'Cotton', 35.00, 'conventional', FALSE),
(42, 'Polypropylene', 100.00, 'conventional', FALSE);

-- ============================================
-- ADDITIONAL PRODUCTS (for new brands)
-- ============================================

-- MediWear products
INSERT INTO products (id, brand_id, gtin, product_name, description, article_number, commodity_code_system, commodity_code_number, year_of_sale, price_currency, msrp, category, product_group, garment_type, age_group, gender, _is_active, data_carrier_type, data_carrier_material, data_carrier_location) VALUES
(10, 2, '7350098765001', 'MediWear Scrub Top', 'Premium scrub top with moisture-wicking technology', 'MW-SCR-001', 'HS', '6211339000', 2025, 'EUR', 39.95, 'clothing', 'Top', 'Tunic', 'Adult', 'unisex', TRUE, 'RFID', 'woven_label', 'side_seam'),
(11, 2, 'MediWear Scrub Pants', '7350098765002', 'Ergonomic scrub pants with stretch panels', 'MW-SCR-002', 'HS', '6203439000', 2025, 'EUR', 37.95, 'clothing', 'Bottom', 'Pants', 'Adult', 'unisex', TRUE, 'RFID', 'woven_label', 'waistband');

-- HealthTex products
INSERT INTO products (id, brand_id, gtin, product_name, description, article_number, commodity_code_system, commodity_code_number, year_of_sale, price_currency, msrp, category, product_group, garment_type, age_group, gender, _is_active, data_carrier_type, data_carrier_material, data_carrier_location) VALUES
(20, 3, '6430012345001', 'HealthTex Basic Tunic', 'Affordable healthcare tunic for daily use', 'HT-BAS-001', 'HS', '6211339000', 2025, 'EUR', 24.95, 'clothing', 'Top', 'Tunic', 'Adult', 'unisex', TRUE, 'QR', 'care_label', 'neck_label'),
(21, 3, '6430012345002', 'HealthTex Comfort Pants', 'Elastic waist pants for all-day comfort', 'HT-BAS-002', 'HS', '6203439000', 2025, 'EUR', 22.95, 'clothing', 'Bottom', 'Pants', 'Adult', 'unisex', TRUE, 'QR', 'care_label', 'waistband');

-- CleanCare products
INSERT INTO products (id, brand_id, gtin, product_name, description, article_number, commodity_code_system, commodity_code_number, year_of_sale, price_currency, msrp, category, product_group, garment_type, age_group, gender, _is_active, data_carrier_type, data_carrier_material, data_carrier_location) VALUES
(30, 4, '7090012345001', 'CleanCare Disposable Gown', 'Single-use protective gown', 'CC-DISP-001', 'HS', '6210109000', 2025, 'EUR', 4.95, 'clothing', 'Top', 'Surgical gown', 'Adult', 'unisex', TRUE, 'QR', 'hangtag', 'attached'),
(31, 4, '7090012345002', 'CleanCare Isolation Gown', 'Level 2 isolation gown', 'CC-ISO-001', 'HS', '6210109000', 2025, 'EUR', 12.95, 'clothing', 'Top', 'Surgical gown', 'Adult', 'unisex', TRUE, 'QR', 'hangtag', 'attached');

-- ============================================
-- PRODUCT VARIANTS FOR NEW PRODUCTS
-- ============================================

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
-- BATCHES FOR NEW BRANDS (with brand_id)
-- ============================================

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
-- BATCH MATERIALS (link batches to factory materials)
-- ============================================

-- MediWear uses Porto materials
INSERT INTO batch_materials (batch_id, factory_material_id, quantity_used, unit) VALUES
(10, 1, 100.5, 'meters'),
(11, 2, 160.0, 'meters');

-- HealthTex uses Baltic and Aegean materials
INSERT INTO batch_materials (batch_id, factory_material_id, quantity_used, unit) VALUES
(20, 10, 180.0, 'meters'),
(21, 30, 180.0, 'meters');

-- CleanCare uses Shanghai materials
INSERT INTO batch_materials (batch_id, factory_material_id, quantity_used, unit) VALUES
(30, 42, 400.0, 'meters'),
(31, 42, 160.0, 'meters');

-- ============================================
-- BATCH SUPPLIERS (link batches to production facilities)
-- ============================================

INSERT INTO batch_suppliers (batch_id, supplier_id, production_stage, country_of_origin) VALUES
(10, 1, 'cutting_sewing', 'PT'),
(11, 1, 'cutting_sewing', 'PT'),
(20, 2, 'cutting_sewing', 'LT'),
(21, 4, 'cutting_sewing', 'TR'),
(30, 5, 'cutting_sewing', 'CN'),
(31, 5, 'cutting_sewing', 'CN');

-- ============================================
-- ITEMS FOR NEW BATCHES (with brand_id)
-- ============================================

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
-- SUMMARY OF TEST API KEYS
-- ============================================
--
-- BRANDS:
-- VårdTex:      brand_vardtex_test_key_000
-- MediWear:     brand_mediwear_test_key_001
-- HealthTex:    brand_healthtex_test_key_002
-- CleanCare:    brand_cleancare_test_key_003
--
-- SUPPLIERS:
-- Porto:        supplier_porto_test_key_000
-- Baltic:       supplier_baltic_test_key_001
-- Tunisia:      supplier_tunisia_test_key_002
-- Aegean:       supplier_aegean_test_key_003
-- Shanghai:     supplier_shanghai_test_key_004
--
-- RELATIONSHIPS:
-- VårdTex     -> Porto, Baltic, (Tunisia inactive)
-- MediWear    -> Porto, Tunisia, Aegean
-- HealthTex   -> Baltic, Aegean
-- CleanCare   -> Shanghai (exclusive)
--
