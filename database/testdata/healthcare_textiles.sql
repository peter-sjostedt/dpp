-- ============================================
-- COMPREHENSIVE TEST DATA - Healthcare Textiles DPP
-- Total reset + all tenants + dashboard scenarios
-- Generated for schema: 01_foundation, 02_products_batches, 03_care_compliance
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- FULL RESET
TRUNCATE TABLE dpp_exports;
TRUNCATE TABLE sustainability_info;
TRUNCATE TABLE circularity_info;
TRUNCATE TABLE product_certifications;
TRUNCATE TABLE compliance_info;
TRUNCATE TABLE care_information;
TRUNCATE TABLE items;
TRUNCATE TABLE batch_materials;
TRUNCATE TABLE batches;
TRUNCATE TABLE purchase_order_lines;
TRUNCATE TABLE purchase_orders;
TRUNCATE TABLE product_variants;
TRUNCATE TABLE product_components;
TRUNCATE TABLE products;
TRUNCATE TABLE factory_material_supply_chain;
TRUNCATE TABLE factory_material_certifications;
TRUNCATE TABLE factory_material_compositions;
TRUNCATE TABLE factory_materials;
TRUNCATE TABLE brand_suppliers;
TRUNCATE TABLE suppliers;
TRUNCATE TABLE brands;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- SECTION 1: BRANDS
-- ============================================

INSERT INTO brands (id, brand_name, logo_url, sub_brand, parent_company, trader, trader_location, lei, gs1_company_prefix, api_key, _is_active) VALUES
(1, 'VårdTex', '/images/vardtex-logo.png', 'VårdTex Pro', 'VårdTex AB', 'VårdTex AB', 'Medicinaregatan 8, 413 90 Göteborg, Sweden', '549300VARDTEX00001SE', '7350012345', 'brand_vardtex_test_key_000', TRUE),
(2, 'MediWear', '/images/mediwear-logo.png', 'MediWear Pro', 'MediWear International AB', 'MediWear International AB', 'Industrivägen 45, 171 48 Solna, Sweden', '549300MEDIWEAR0002SE', '7350098765', 'brand_mediwear_test_key_001', TRUE),
(3, 'HealthTex', '/images/healthtex-logo.png', 'HealthTex Care', 'HealthTex Nordic Oy', 'HealthTex Nordic Oy', 'Terveyskatu 12, 00100 Helsinki, Finland', '549300HEALTHTEX03FI', '6430012345', 'brand_healthtex_test_key_002', TRUE),
(4, 'NordicCare Textil', '/images/nordiccare-logo.png', NULL, 'NordicCare Textil AB', 'NordicCare Textil AB', 'Textilvägen 5, 507 30 Borås, Sweden', '549300NORDICCARE04SE', '7350054321', 'brand_nordiccare_test_key_003', TRUE);

-- ============================================
-- SECTION 2: SUPPLIERS
-- ============================================

INSERT INTO suppliers (id, supplier_name, supplier_location, facility_registry, facility_identifier, operator_registry, operator_identifier, country_of_origin_confection, country_of_origin_dyeing, country_of_origin_weaving, lei, gs1_company_prefix, api_key, _is_active) VALUES
(1, 'Têxtil Hospitalar Porto', 'Zona Industrial de Maia, 4470-122 Porto, Portugal', 'GLN', '5609876543210', 'GLN', '5609876543210', 'PT', 'PT', 'PT', '549300PORTOTEXTIL01', '5609876543', 'supplier_porto_test_key_000', TRUE),
(2, 'Baltic Medical Textiles', 'Klaipeda FEZ, LT-94101 Klaipeda, Lithuania', 'GLN', '4771234567890', 'GLN', '4771234567890', 'LT', 'LT', 'LT', '549300BALTICTEX02LT', '4771234567', 'supplier_baltic_test_key_001', TRUE),
(3, 'Anatolia Healthcare Fabrics', 'Organized Industrial Zone, 35410 Denizli, Turkey', 'GLN', '8691234567890', 'GLN', '8691234567890', 'TR', 'TR', 'TR', '549300ANATOLIAF03TR', '8691234567', 'supplier_anatolia_test_key_002', TRUE),
(4, 'NordicCare Textil', 'Textilvägen 5, 507 30 Borås, Sweden', 'GLN', '7350054321000', 'GLN', '7350054321000', 'SE', 'SE', 'SE', '549300NORDICCARE04SE', '7350054321', 'supplier_nordiccare_test_key_003', TRUE);

-- ============================================
-- SECTION 3: BRAND-SUPPLIER RELATIONS
-- ============================================

INSERT INTO brand_suppliers (brand_id, supplier_id, _is_active) VALUES
(1, 1, TRUE),  -- VårdTex ↔ Porto (primary)
(1, 2, TRUE),  -- VårdTex ↔ Baltic
(2, 1, TRUE),  -- MediWear ↔ Porto
(2, 3, TRUE),  -- MediWear ↔ Anatolia
(3, 2, TRUE),  -- HealthTex ↔ Baltic
(3, 3, TRUE),  -- HealthTex ↔ Anatolia
(4, 1, TRUE),  -- NordicCare (brand) ↔ Porto
(4, 4, TRUE),  -- NordicCare (brand) ↔ NordicCare (supplier = themselves)
(1, 4, TRUE);  -- VårdTex ↔ NordicCare (supplier)

-- ============================================
-- SECTION 4: FACTORY MATERIALS (Supplier-owned)
-- ============================================

-- Porto (supplier 1): materials 1-7
INSERT INTO factory_materials (id, supplier_id, material_name, material_type, description, _is_active) VALUES
(1, 1, 'PolyCotton Twill 65/35 155gsm', 'Textile', 'Standard healthcare fabric, industrial wash compatible', TRUE),
(2, 1, 'PolyCotton Twill 65/35 195gsm', 'Textile', 'Heavy-duty healthcare fabric for lab coats', TRUE),
(3, 1, 'Polyester Antimicrobial 170gsm', 'Textile', 'Antimicrobial treated polyester', TRUE),
(4, 1, 'Organic Cotton Jersey 160gsm', 'Textile', 'GOTS certified organic cotton for patient wear', TRUE),
(5, 1, 'Polyester Barrier Fabric 120gsm', 'Textile', 'EN 13795 compliant barrier fabric', TRUE),
(6, 1, 'Recycled Polyester 150gsm', 'Textile', 'Discontinued recycled polyester blend', FALSE),
(7, 1, 'New Cotton Batch 170gsm', 'Textile', 'New material pending composition data', TRUE);

-- Baltic (supplier 2): materials 10-11
INSERT INTO factory_materials (id, supplier_id, material_name, material_type, description, _is_active) VALUES
(10, 2, 'Baltic Recycled Polyester 180gsm', 'Textile', 'High-quality recycled polyester', TRUE),
(11, 2, 'Baltic Blend 50/50 200gsm', 'Textile', 'Poly-cotton blend for durability', TRUE);

-- Anatolia (supplier 3): materials 20-21
INSERT INTO factory_materials (id, supplier_id, material_name, material_type, description, _is_active) VALUES
(20, 3, 'Anatolia Premium Blend 185gsm', 'Textile', 'Premium healthcare fabric', TRUE),
(21, 3, 'Anatolia Surgical Grade 120gsm', 'Textile', 'Surgical grade barrier fabric', TRUE);

-- NordicCare (supplier 4): materials 30-31
INSERT INTO factory_materials (id, supplier_id, material_name, material_type, description, _is_active) VALUES
(30, 4, 'Borås PolyCotton 65/35 160gsm', 'Textile', 'Swedish-made polycotton for healthcare', TRUE),
(31, 4, 'Borås Organic Cotton 180gsm', 'Textile', 'GOTS certified organic cotton, Swedish finishing', TRUE);

-- ============================================
-- SECTION 4b: FACTORY MATERIAL COMPOSITIONS
-- Material 7 has NO compositions → dashboard: incomplete_materials
-- ============================================

-- Porto materials
INSERT INTO factory_material_compositions (factory_material_id, content_name, content_value, content_source, recycled, recycled_percentage, recycled_input_source) VALUES
(1, 'Polyester', 65.00, 'Conventional', FALSE, NULL, NULL),
(1, 'Cotton', 35.00, 'Conventional', FALSE, NULL, NULL),
(2, 'Polyester', 65.00, 'Conventional', FALSE, NULL, NULL),
(2, 'Cotton', 35.00, 'Conventional', FALSE, NULL, NULL),
(3, 'Polyester', 100.00, 'Conventional', FALSE, NULL, NULL),
(4, 'Cotton', 100.00, 'Organic', FALSE, NULL, NULL),
(5, 'Polyester', 100.00, 'Conventional', FALSE, NULL, NULL),
(6, 'Polyester', 100.00, 'Recycled', TRUE, 80.00, 'Post-consumer');
-- Material 7: NO compositions → dashboard trigger

-- Baltic materials
INSERT INTO factory_material_compositions (factory_material_id, content_name, content_value, content_source, recycled, recycled_percentage, recycled_input_source) VALUES
(10, 'Polyester', 100.00, 'Recycled', TRUE, 100.00, 'Post-consumer'),
(11, 'Polyester', 50.00, 'Conventional', FALSE, NULL, NULL),
(11, 'Cotton', 50.00, 'Conventional', FALSE, NULL, NULL);

-- Anatolia materials
INSERT INTO factory_material_compositions (factory_material_id, content_name, content_value, content_source, recycled, recycled_percentage, recycled_input_source) VALUES
(20, 'Polyester', 65.00, 'Conventional', FALSE, NULL, NULL),
(20, 'Cotton', 35.00, 'Conventional', FALSE, NULL, NULL),
(21, 'Polypropylene', 100.00, 'Conventional', FALSE, NULL, NULL);

-- NordicCare materials
INSERT INTO factory_material_compositions (factory_material_id, content_name, content_value, content_source, recycled, recycled_percentage, recycled_input_source) VALUES
(30, 'Polyester', 65.00, 'Conventional', FALSE, NULL, NULL),
(30, 'Cotton', 35.00, 'Conventional', FALSE, NULL, NULL),
(31, 'Cotton', 100.00, 'Organic', FALSE, NULL, NULL);

-- ============================================
-- SECTION 4c: FACTORY MATERIAL CERTIFICATIONS
-- Materials 1 GOTS expires 2026-03-10, Material 3 GRS expires 2026-02-28
-- → dashboard: expiring_certifications (within 30 days of 2026-02-15)
-- ============================================

INSERT INTO factory_material_certifications (factory_material_id, certification, certification_id, valid_until) VALUES
(1, 'Oeko-Tex Standard 100', 'OTS-2024-001', '2027-12-31'),
(1, 'GOTS', 'GOTS-PT-10001', '2026-03-10'),
(2, 'Oeko-Tex Standard 100', 'OTS-2024-002', '2027-12-31'),
(3, 'Oeko-Tex Standard 100', 'OTS-2024-003', '2027-12-31'),
(3, 'GRS', 'GRS-PT-30001', '2026-02-28'),
(4, 'GOTS', 'GOTS-PT-40001', '2027-06-30'),
(4, 'Oeko-Tex Standard 100', 'OTS-2024-004', '2027-12-31'),
(5, 'EN 13795', 'EN13795-2024-001', '2027-02-28'),
(10, 'GRS', 'GRS-BT-10001', '2027-09-30'),
(21, 'EN 13795', 'EN13795-2024-002', '2027-02-28'),
(30, 'Oeko-Tex Standard 100', 'OTS-NC-30001', '2027-12-31'),
(31, 'GOTS', 'GOTS-NC-31001', '2027-06-30');

-- ============================================
-- SECTION 4d: FACTORY MATERIAL SUPPLY CHAIN
-- ============================================

-- Material 1: PolyCotton Twill (Porto)
INSERT INTO factory_material_supply_chain (factory_material_id, sequence, process_step, country, facility_name, facility_identifier) VALUES
(1, 1, 'Fiber production', 'IN', 'Reliance Industries', 'GLN-8901234500001'),
(1, 2, 'Spinning', 'IN', 'Vardhman Textiles', 'GLN-8901234500002'),
(1, 3, 'Weaving', 'PT', 'Têxtil Hospitalar Porto', 'GLN-5609876543210'),
(1, 4, 'Dyeing', 'PT', 'Têxtil Hospitalar Porto', 'GLN-5609876543210'),
(1, 5, 'Finishing', 'PT', 'Têxtil Hospitalar Porto', 'GLN-5609876543210');

-- Material 2: PolyCotton Heavy (Porto)
INSERT INTO factory_material_supply_chain (factory_material_id, sequence, process_step, country, facility_name, facility_identifier) VALUES
(2, 1, 'Fiber production', 'IN', 'Reliance Industries', 'GLN-8901234500001'),
(2, 2, 'Spinning', 'IN', 'Vardhman Textiles', 'GLN-8901234500002'),
(2, 3, 'Weaving', 'PT', 'Têxtil Hospitalar Porto', 'GLN-5609876543210'),
(2, 4, 'Finishing', 'PT', 'Têxtil Hospitalar Porto', 'GLN-5609876543210');

-- Material 3: Antimicrobial Polyester (Porto)
INSERT INTO factory_material_supply_chain (factory_material_id, sequence, process_step, country, facility_name, facility_identifier) VALUES
(3, 1, 'Spinning', 'PT', 'Porto Spinning Mill', 'GLN-PT-SP-001'),
(3, 2, 'Weaving', 'PT', 'Têxtil Hospitalar Porto', 'GLN-5609876543210'),
(3, 3, 'Finishing', 'PT', 'Porto Antimicrobial Treatment', 'GLN-PT-AM-001');

-- Material 4: Organic Cotton (Porto)
INSERT INTO factory_material_supply_chain (factory_material_id, sequence, process_step, country, facility_name, facility_identifier) VALUES
(4, 1, 'Fiber production', 'IN', 'Chetna Organic', 'GLN-8901234500010'),
(4, 2, 'Spinning', 'IN', 'Armstrong Spinning Mills', 'GLN-8901234500011'),
(4, 3, 'Weaving', 'PT', 'Têxtil Hospitalar Porto', 'GLN-5609876543210'),
(4, 4, 'Dyeing', 'PT', 'Têxtil Hospitalar Porto', 'GLN-5609876543210');

-- Material 10: Baltic Recycled Polyester
INSERT INTO factory_material_supply_chain (factory_material_id, sequence, process_step, country, facility_name, facility_identifier) VALUES
(10, 1, 'Fiber production', 'LT', 'Baltic Recycling Center', 'GLN-LT-RC-001'),
(10, 2, 'Spinning', 'LT', 'Kaunas Spinning Works', 'GLN-LT-SP-001'),
(10, 3, 'Weaving', 'LT', 'Baltic Medical Textiles', 'GLN-4771234567890'),
(10, 4, 'Finishing', 'LT', 'Baltic Medical Textiles', 'GLN-4771234567890');

-- Material 20: Anatolia Premium Blend
INSERT INTO factory_material_supply_chain (factory_material_id, sequence, process_step, country, facility_name, facility_identifier) VALUES
(20, 1, 'Spinning', 'TR', 'Denizli Cotton Spinners', 'GLN-TR-SP-001'),
(20, 2, 'Weaving', 'TR', 'Anatolia Healthcare Fabrics', 'GLN-8691234567890'),
(20, 3, 'Dyeing', 'TR', 'Denizli Dyeworks', 'GLN-TR-DY-001');

-- Material 30: Borås PolyCotton (NordicCare)
INSERT INTO factory_material_supply_chain (factory_material_id, sequence, process_step, country, facility_name, facility_identifier) VALUES
(30, 1, 'Spinning', 'SE', 'NordicCare Textil', 'GLN-7350054321000'),
(30, 2, 'Weaving', 'SE', 'NordicCare Textil', 'GLN-7350054321000'),
(30, 3, 'Dyeing', 'SE', 'NordicCare Textil', 'GLN-7350054321000'),
(30, 4, 'Finishing', 'SE', 'NordicCare Textil', 'GLN-7350054321000');

-- ============================================
-- SECTION 5: PRODUCTS
-- VårdTex = 10 (high volume), MediWear = 2, HealthTex = 2
-- ============================================

INSERT INTO products (id, brand_id, gtin_type, gtin, product_name, description, article_number, commodity_code_system, commodity_code_number, year_of_sale, season_of_sale, price_currency, msrp, category, product_group, type_line_concept, type_item, age_group, gender, market_segment, net_weight, weight_unit, data_carrier_type, data_carrier_material, data_carrier_location, _is_active) VALUES
-- VårdTex products (1-10)
(1,  1, 'GTIN', '7350012345001', 'Scrubs Tunika',          'Klassisk V-ringad scrubstopp för vårdpersonal. Bröstficka och sidfickor. Tål industriell tvätt 85°C.', 'VT-SCR-TOP-001', 'HS', '6211339000', 2025, 'SP', 'EUR', 34.95, 'clothing', 'Top', 'Healthcare', 'Tunic',         'Adult', 'Unisex', 'mid-price', 0.185, 'kg', 'RFID', 'Woven label', 'Side seam', TRUE),
(2,  1, 'GTIN', '7350012345002', 'Scrubs Byxa',            'Bekväm scrubsbyxa med resår och dragsko. Sidfickor och bakficka. Tål industriell tvätt 85°C.',        'VT-SCR-BTM-001', 'HS', '6203439000', 2025, 'SP', 'EUR', 32.95, 'clothing', 'Bottom', 'Healthcare', 'Pants',       'Adult', 'Unisex', 'mid-price', 0.210, 'kg', 'RFID', 'Woven label', 'Waistband', TRUE),
(3,  1, 'GTIN', '7350012345003', 'Labbrock',               'Klassisk vit labbrock med knäppning. Bröstficka och sidfickor. Antimikrobiellt tyg.',                 'VT-LAB-001',     'HS', '6211329000', 2025, 'SP', 'EUR', 59.95, 'clothing', 'Top', 'Healthcare', 'Lab coat',      'Adult', 'Unisex', 'mid-price', 0.320, 'kg', 'RFID', 'Woven label', 'Neck label', TRUE),
(4,  1, 'GTIN', '7350012345004', 'Patientskjorta',         'Öppen patientskjorta med tryckknappsstängning. GOTS-certifierad ekologisk bomull.',                   'VT-PAT-001',     'HS', '6211429000', 2025, 'SP', 'EUR', 24.95, 'clothing', 'Top', 'Healthcare', 'Patient gown',  'Adult', 'Unisex', 'mid-price', 0.145, 'kg', 'QR', 'Care label', 'Neck label', TRUE),
(5,  1, 'GTIN', '7350012345005', 'Operationsrock',         'Återanvändbar operationsrock med ryggstängning. EN 13795 kompatibel.',                                'VT-SG-001',      'HS', '6210100000', 2025, 'SP', 'EUR', 45.00, 'clothing', 'Top', 'Healthcare', 'Gown',          'Adult', 'Unisex', 'mid-price', 0.280, 'kg', 'RFID', 'Woven label', 'Side seam', TRUE),
(6,  1, 'GTIN', '7350012345006', 'Isoleringsrock',         'Vätskeresistent isoleringsrock nivå 2. Flergångsanvändning.',                                        'VT-IG-001',      'HS', '6210100000', 2025, 'SP', 'EUR', 38.00, 'clothing', 'Top', 'Healthcare', 'Gown',          'Adult', 'Unisex', 'mass-market', 0.180, 'kg', 'QR', 'Care label', 'Neck label', TRUE),
(7,  1, 'GTIN', '7350012345007', 'Uppvärmningsjacka',      'Dragkedja-jacka för klinisk personal. 100% återvunnen polyester med antimikrobiell behandling.',      'VT-WJ-001',      'HS', '6201939000', 2025, 'SP', 'EUR', 48.00, 'clothing', 'Top', 'Healthcare', 'Jacket',        'Adult', 'Unisex', 'mid-price', 0.350, 'kg', 'RFID', 'Woven label', 'Side seam', TRUE),
(8,  1, 'GTIN', '7350012345008', 'Patientrock',            'Helångs patientrock med bälte. Mjuk bomullsjersey.',                                                 'VT-PR-001',      'HS', '6211429000', 2025, 'SP', 'EUR', 28.00, 'clothing', 'Top', 'Healthcare', 'Gown',          'Adult', 'Unisex', 'mass-market', 0.380, 'kg', 'QR', 'Care label', 'Neck label', TRUE),
(9,  1, 'GTIN', '7350012345009', 'Operationsmössa',        'Återanvändbar operationsmössa. Polyester/bomull-blandning.',                                         'VT-SC-001',      'HS', '6505009000', 2025, 'SP', 'EUR',  6.50, 'clothing', 'Top', 'Healthcare', 'Other',         'Adult', 'Unisex', 'mass-market', 0.040, 'kg', 'QR', 'Care label', 'Inside', TRUE),
(10, 1, 'GTIN', '7350012345010', 'Skyddsoverall',          'Heltäckande skyddsoverall med huva. Polypropylen barriärtyg.',                                       'VT-PC-001',      'HS', '6210100000', 2025, 'SP', 'EUR', 39.00, 'clothing', 'Top', 'Healthcare', 'Other',         'Adult', 'Unisex', 'mass-market', 0.280, 'kg', 'QR', 'Care label', 'Inside', TRUE),
-- MediWear products (11-12)
(11, 2, 'GTIN', '7350098765001', 'MediWear Scrub Top',     'Premium scrub top with modern fit. Antimicrobial treatment.',                                        'MW-SCR-001',     'HS', '6211339000', 2025, 'SP', 'EUR', 39.95, 'clothing', 'Top', 'Healthcare', 'Tunic',         'Adult', 'Unisex', 'premium', 0.195, 'kg', 'RFID', 'Woven label', 'Side seam', TRUE),
(12, 2, 'GTIN', '7350098765002', 'MediWear Scrub Pants',   'Ergonomic scrub pants with cargo pockets.',                                                          'MW-SCR-002',     'HS', '6203439000', 2025, 'SP', 'EUR', 37.95, 'clothing', 'Bottom', 'Healthcare', 'Pants',       'Adult', 'Unisex', 'premium', 0.225, 'kg', 'RFID', 'Woven label', 'Waistband', TRUE),
-- HealthTex products (13-14)
(13, 3, 'GTIN', '6430012345001', 'HealthTex Basic Tunic',  'Affordable healthcare tunic for everyday use.',                                                       'HT-BAS-001',     'HS', '6211339000', 2025, 'SP', 'EUR', 24.95, 'clothing', 'Top', 'Healthcare', 'Tunic',         'Adult', 'Unisex', 'mass-market', 0.170, 'kg', 'QR', 'Care label', 'Neck label', TRUE),
(14, 3, 'GTIN', '6430012345002', 'HealthTex Scrub Dress',  'A-line scrub dress with pockets. Comfortable fit.',                                                   'HT-SD-001',      'HS', '6204440000', 2025, 'SP', 'EUR', 28.50, 'clothing', 'Top', 'Healthcare', 'Other',         'Adult', 'Female', 'mid-price', 0.230, 'kg', 'QR', 'Care label', 'Neck label', TRUE),
-- NordicCare products (15-16)
(15, 4, 'GTIN', '7350054321001', 'NordicCare Scrub Top',  'Svensktillverkad scrubstopp i Borås polybomull. Tål industriell tvätt 85°C.',                          'NC-SCR-001',     'HS', '6211339000', 2025, 'SP', 'SEK', 295.00, 'clothing', 'Top', 'Healthcare', 'Tunic',        'Adult', 'Unisex', 'mid-price', 0.190, 'kg', 'RFID', 'Woven label', 'Side seam', TRUE),
(16, 4, 'GTIN', '7350054321002', 'NordicCare Scrub Pants','Svensktillverkad scrubsbyxa med resår. Tål industriell tvätt 85°C.',                                   'NC-SCR-002',     'HS', '6203439000', 2025, 'SP', 'SEK', 285.00, 'clothing', 'Bottom', 'Healthcare', 'Pants',     'Adult', 'Unisex', 'mid-price', 0.215, 'kg', 'RFID', 'Woven label', 'Waistband', TRUE);

-- ============================================
-- SECTION 5b: PRODUCT COMPONENTS
-- Products 1-9, 11-14 have components. Product 10 has NONE → incomplete
-- ============================================

INSERT INTO product_components (product_id, component, material, content_name, content_value, content_source, recycled, recycled_percentage, finishes) VALUES
-- Product 1: Scrubs Tunika (PolyCotton 65/35)
(1, 'Body fabric', 'Textile', 'Polyester', 65.00, 'Conventional', FALSE, NULL, 'Anti-microbial'),
(1, 'Body fabric', 'Textile', 'Cotton', 35.00, 'Conventional', FALSE, NULL, NULL),
-- Product 2: Scrubs Byxa (PolyCotton 65/35)
(2, 'Body fabric', 'Textile', 'Polyester', 65.00, 'Conventional', FALSE, NULL, 'Anti-microbial'),
(2, 'Body fabric', 'Textile', 'Cotton', 35.00, 'Conventional', FALSE, NULL, NULL),
-- Product 3: Labbrock (Antimicrobial Polyester)
(3, 'Body fabric', 'Textile', 'Polyester', 100.00, 'Conventional', FALSE, NULL, 'Anti-microbial'),
-- Product 4: Patientskjorta (Organic Cotton)
(4, 'Body fabric', 'Textile', 'Cotton', 100.00, 'Organic', FALSE, NULL, NULL),
-- Product 5: Operationsrock (Barrier Fabric)
(5, 'Body fabric', 'Textile', 'Polyester', 100.00, 'Conventional', FALSE, NULL, 'Fluid-resistant'),
-- Product 6: Isoleringsrock (PolyCotton)
(6, 'Body fabric', 'Textile', 'Polyester', 65.00, 'Conventional', FALSE, NULL, NULL),
(6, 'Body fabric', 'Textile', 'Cotton', 35.00, 'Conventional', FALSE, NULL, NULL),
-- Product 7: Uppvärmningsjacka (Recycled Polyester)
(7, 'Body fabric', 'Textile', 'Polyester', 100.00, 'Recycled', TRUE, 100.00, 'Anti-microbial'),
-- Product 8: Patientrock (Organic Cotton Jersey)
(8, 'Body fabric', 'Textile', 'Cotton', 100.00, 'Organic', FALSE, NULL, NULL),
-- Product 9: Operationsmössa (PolyCotton)
(9, 'Body fabric', 'Textile', 'Polyester', 65.00, 'Conventional', FALSE, NULL, NULL),
(9, 'Body fabric', 'Textile', 'Cotton', 35.00, 'Conventional', FALSE, NULL, NULL),
-- Product 10: Skyddsoverall → NO components (incomplete)
-- Product 11: MediWear Scrub Top
(11, 'Body fabric', 'Textile', 'Polyester', 65.00, 'Conventional', FALSE, NULL, 'Anti-microbial'),
(11, 'Body fabric', 'Textile', 'Cotton', 35.00, 'Conventional', FALSE, NULL, NULL),
-- Product 12: MediWear Scrub Pants
(12, 'Body fabric', 'Textile', 'Polyester', 65.00, 'Conventional', FALSE, NULL, NULL),
(12, 'Body fabric', 'Textile', 'Cotton', 35.00, 'Conventional', FALSE, NULL, NULL),
-- Product 13: HealthTex Basic Tunic
(13, 'Body fabric', 'Textile', 'Polyester', 50.00, 'Conventional', FALSE, NULL, NULL),
(13, 'Body fabric', 'Textile', 'Cotton', 50.00, 'Conventional', FALSE, NULL, NULL),
-- Product 14: HealthTex Scrub Dress
(14, 'Body fabric', 'Textile', 'Polyester', 65.00, 'Conventional', FALSE, NULL, NULL),
(14, 'Body fabric', 'Textile', 'Cotton', 35.00, 'Conventional', FALSE, NULL, NULL),
-- Product 15: NordicCare Scrub Top (PolyCotton)
(15, 'Body fabric', 'Textile', 'Polyester', 65.00, 'Conventional', FALSE, NULL, 'Anti-microbial'),
(15, 'Body fabric', 'Textile', 'Cotton', 35.00, 'Conventional', FALSE, NULL, NULL),
-- Product 16: NordicCare Scrub Pants (PolyCotton)
(16, 'Body fabric', 'Textile', 'Polyester', 65.00, 'Conventional', FALSE, NULL, NULL),
(16, 'Body fabric', 'Textile', 'Cotton', 35.00, 'Conventional', FALSE, NULL, NULL);

-- ============================================
-- SECTION 5c: PRODUCT VARIANTS (size/color with GTIN)
-- ============================================

INSERT INTO product_variants (id, product_id, item_number, size, size_country_code, color_brand, color_general, gtin, _is_active) VALUES
-- Product 1: Scrubs Tunika (6 variants: 4 Ceil Blue + 2 Navy)
(1,  1, 'VT-SCR-TOP-001-CBL-S',   'S',   'EU', 'Ceil Blue', 'blue', '73500123450011', TRUE),
(2,  1, 'VT-SCR-TOP-001-CBL-M',   'M',   'EU', 'Ceil Blue', 'blue', '73500123450012', TRUE),
(3,  1, 'VT-SCR-TOP-001-CBL-L',   'L',   'EU', 'Ceil Blue', 'blue', '73500123450013', TRUE),
(4,  1, 'VT-SCR-TOP-001-CBL-XL',  'XL',  'EU', 'Ceil Blue', 'blue', '73500123450014', TRUE),
(5,  1, 'VT-SCR-TOP-001-NVY-M',   'M',   'EU', 'Navy',      'navy', '73500123450015', TRUE),
(6,  1, 'VT-SCR-TOP-001-NVY-L',   'L',   'EU', 'Navy',      'navy', '73500123450016', TRUE),
-- Product 2: Scrubs Byxa (4 variants)
(10, 2, 'VT-SCR-BTM-001-CBL-S',   'S',   'EU', 'Ceil Blue', 'blue', '73500123450021', TRUE),
(11, 2, 'VT-SCR-BTM-001-CBL-M',   'M',   'EU', 'Ceil Blue', 'blue', '73500123450022', TRUE),
(12, 2, 'VT-SCR-BTM-001-CBL-L',   'L',   'EU', 'Ceil Blue', 'blue', '73500123450023', TRUE),
(13, 2, 'VT-SCR-BTM-001-CBL-XL',  'XL',  'EU', 'Ceil Blue', 'blue', '73500123450024', TRUE),
-- Product 3: Labbrock (4 variants)
(20, 3, 'VT-LAB-001-WHT-S',       'S',   'EU', 'White',     'white', '73500123450031', TRUE),
(21, 3, 'VT-LAB-001-WHT-M',       'M',   'EU', 'White',     'white', '73500123450032', TRUE),
(22, 3, 'VT-LAB-001-WHT-L',       'L',   'EU', 'White',     'white', '73500123450033', TRUE),
(23, 3, 'VT-LAB-001-WHT-XL',      'XL',  'EU', 'White',     'white', '73500123450034', TRUE),
-- Product 4: Patientskjorta (4 variants: 2 White + 2 Light Blue)
(30, 4, 'VT-PAT-001-WHT-M',       'M',   'EU', 'White',      'white', '73500123450041', TRUE),
(31, 4, 'VT-PAT-001-WHT-L',       'L',   'EU', 'White',      'white', '73500123450042', TRUE),
(32, 4, 'VT-PAT-001-LBL-M',       'M',   'EU', 'Light Blue', 'blue',  '73500123450043', TRUE),
(33, 4, 'VT-PAT-001-LBL-L',       'L',   'EU', 'Light Blue', 'blue',  '73500123450044', TRUE),
-- Product 5: Operationsrock (4 variants)
(34, 5, 'VT-SG-001-LBL-M',        'M',   'EU', 'Light Blue', 'blue',  '73500123450051', TRUE),
(35, 5, 'VT-SG-001-LBL-L',        'L',   'EU', 'Light Blue', 'blue',  '73500123450052', TRUE),
(36, 5, 'VT-SG-001-LBL-XL',       'XL',  'EU', 'Light Blue', 'blue',  '73500123450053', TRUE),
(37, 5, 'VT-SG-001-LBL-XXL',      'XXL', 'EU', 'Light Blue', 'blue',  '73500123450054', TRUE),
-- Product 6: Isoleringsrock (4 variants)
(38, 6, 'VT-IG-001-YLW-M',        'M',   'EU', 'Signal Yellow', 'yellow', '73500123450061', TRUE),
(39, 6, 'VT-IG-001-YLW-L',        'L',   'EU', 'Signal Yellow', 'yellow', '73500123450062', TRUE),
(40, 6, 'VT-IG-001-YLW-XL',       'XL',  'EU', 'Signal Yellow', 'yellow', '73500123450063', TRUE),
(41, 6, 'VT-IG-001-YLW-XXL',      'XXL', 'EU', 'Signal Yellow', 'yellow', '73500123450064', TRUE),
-- Product 7: Uppvärmningsjacka (4 variants)
(42, 7, 'VT-WJ-001-NVY-S',        'S',   'EU', 'Navy',      'navy', '73500123450071', TRUE),
(43, 7, 'VT-WJ-001-NVY-M',        'M',   'EU', 'Navy',      'navy', '73500123450072', TRUE),
(44, 7, 'VT-WJ-001-NVY-L',        'L',   'EU', 'Navy',      'navy', '73500123450073', TRUE),
(45, 7, 'VT-WJ-001-NVY-XL',       'XL',  'EU', 'Navy',      'navy', '73500123450074', TRUE),
-- Product 8: Patientrock (4 variants)
(46, 8, 'VT-PR-001-LBL-M',        'M',   'EU', 'Light Blue', 'blue', '73500123450081', TRUE),
(47, 8, 'VT-PR-001-LBL-L',        'L',   'EU', 'Light Blue', 'blue', '73500123450082', TRUE),
(48, 8, 'VT-PR-001-LBL-XL',       'XL',  'EU', 'Light Blue', 'blue', '73500123450083', TRUE),
(49, 8, 'VT-PR-001-LBL-XXL',      'XXL', 'EU', 'Light Blue', 'blue', '73500123450084', TRUE),
-- Product 9: Operationsmössa (3 variants)
(50, 9, 'VT-SC-001-LBL-M',        'M',   'EU', 'Light Blue', 'blue',  '73500123450091', TRUE),
(51, 9, 'VT-SC-001-LBL-L',        'L',   'EU', 'Light Blue', 'blue',  '73500123450092', TRUE),
(52, 9, 'VT-SC-001-GRN-OS',       'One Size', 'EU', 'Surgical Green', 'green', '73500123450093', TRUE),
-- Product 10: Skyddsoverall (5 variants)
(53, 10, 'VT-PC-001-WHT-S',       'S',   'EU', 'White',     'white', '73500123450101', TRUE),
(54, 10, 'VT-PC-001-WHT-M',       'M',   'EU', 'White',     'white', '73500123450102', TRUE),
(55, 10, 'VT-PC-001-WHT-L',       'L',   'EU', 'White',     'white', '73500123450103', TRUE),
(56, 10, 'VT-PC-001-WHT-XL',      'XL',  'EU', 'White',     'white', '73500123450104', TRUE),
(57, 10, 'VT-PC-001-WHT-XXL',     'XXL', 'EU', 'White',     'white', '73500123450105', TRUE),
-- Product 11: MediWear Scrub Top (4 variants)
(60, 11, 'MW-SCR-001-TL-S',       'S',   'EU', 'Teal',      'teal', '73500987650011', TRUE),
(61, 11, 'MW-SCR-001-TL-M',       'M',   'EU', 'Teal',      'teal', '73500987650012', TRUE),
(62, 11, 'MW-SCR-001-TL-L',       'L',   'EU', 'Teal',      'teal', '73500987650013', TRUE),
(63, 11, 'MW-SCR-001-TL-XL',      'XL',  'EU', 'Teal',      'teal', '73500987650014', TRUE),
-- Product 12: MediWear Scrub Pants (4 variants)
(64, 12, 'MW-SCR-002-TL-S',       'S',   'EU', 'Teal',      'teal', '73500987650021', TRUE),
(65, 12, 'MW-SCR-002-TL-M',       'M',   'EU', 'Teal',      'teal', '73500987650022', TRUE),
(66, 12, 'MW-SCR-002-TL-L',       'L',   'EU', 'Teal',      'teal', '73500987650023', TRUE),
(67, 12, 'MW-SCR-002-TL-XL',      'XL',  'EU', 'Teal',      'teal', '73500987650024', TRUE),
-- Product 13: HealthTex Basic Tunic (4 variants)
(70, 13, 'HT-BAS-001-WHT-S',      'S',   'EU', 'White',     'white', '64300123450011', TRUE),
(71, 13, 'HT-BAS-001-WHT-M',      'M',   'EU', 'White',     'white', '64300123450012', TRUE),
(72, 13, 'HT-BAS-001-WHT-L',      'L',   'EU', 'White',     'white', '64300123450013', TRUE),
(73, 13, 'HT-BAS-001-WHT-XL',     'XL',  'EU', 'White',     'white', '64300123450014', TRUE),
-- Product 14: HealthTex Scrub Dress (4 variants)
(74, 14, 'HT-SD-001-NVY-S',       'S',   'EU', 'Navy',      'navy', '64300123450021', TRUE),
(75, 14, 'HT-SD-001-NVY-M',       'M',   'EU', 'Navy',      'navy', '64300123450022', TRUE),
(76, 14, 'HT-SD-001-NVY-L',       'L',   'EU', 'Navy',      'navy', '64300123450023', TRUE),
(77, 14, 'HT-SD-001-NVY-XL',      'XL',  'EU', 'Navy',      'navy', '64300123450024', TRUE),
-- Product 15: NordicCare Scrub Top (4 variants)
(80, 15, 'NC-SCR-001-CBL-S',      'S',   'EU', 'Ceil Blue', 'blue', '73500543210011', TRUE),
(81, 15, 'NC-SCR-001-CBL-M',      'M',   'EU', 'Ceil Blue', 'blue', '73500543210012', TRUE),
(82, 15, 'NC-SCR-001-CBL-L',      'L',   'EU', 'Ceil Blue', 'blue', '73500543210013', TRUE),
(83, 15, 'NC-SCR-001-CBL-XL',     'XL',  'EU', 'Ceil Blue', 'blue', '73500543210014', TRUE),
-- Product 16: NordicCare Scrub Pants (4 variants)
(84, 16, 'NC-SCR-002-CBL-S',      'S',   'EU', 'Ceil Blue', 'blue', '73500543210021', TRUE),
(85, 16, 'NC-SCR-002-CBL-M',      'M',   'EU', 'Ceil Blue', 'blue', '73500543210022', TRUE),
(86, 16, 'NC-SCR-002-CBL-L',      'L',   'EU', 'Ceil Blue', 'blue', '73500543210023', TRUE),
(87, 16, 'NC-SCR-002-CBL-XL',     'XL',  'EU', 'Ceil Blue', 'blue', '73500543210024', TRUE);

-- ============================================
-- SECTION 6: PURCHASE ORDERS
-- VårdTex→Porto: all statuses (PO 1-10)
-- MediWear→Porto (PO 11), MediWear→Anatolia (PO 12)
-- HealthTex→Baltic (PO 13), HealthTex→Anatolia (PO 14)
-- ============================================

INSERT INTO purchase_orders (id, brand_id, supplier_id, product_id, po_number, quantity, requested_delivery_date, _status) VALUES
-- VårdTex → Porto (high volume, all statuses)
(1,  1, 1, 1,  'PO-VT-2025-001A', 500,  '2025-06-01', 'fulfilled'),
(2,  1, 1, 2,  'PO-VT-2025-001B', 500,  '2025-06-01', 'fulfilled'),
(3,  1, 1, 3,  'PO-VT-2025-002',  200,  '2025-05-15', 'fulfilled'),
(4,  1, 1, 4,  'PO-VT-2025-003',  300,  '2025-05-20', 'accepted'),
(5,  1, 1, 5,  'PO-VT-2025-004',  400,  '2025-06-15', 'sent'),
(6,  1, 1, 6,  'PO-VT-2025-005',  400,  '2025-05-01', 'sent'),
(7,  1, 1, 7,  'PO-VT-2025-006',  150,  '2025-04-01', 'accepted'),
(8,  1, 1, 8,  'PO-VT-2025-007',  200,  '2025-04-15', 'accepted'),
(9,  1, 1, 9,  'PO-VT-2025-008', 1000,  '2025-07-01', 'cancelled'),
(10, 1, 1, 10, 'PO-VT-2025-009',  300,  '2025-06-15', 'draft'),
-- MediWear → Porto + Anatolia
(11, 2, 1, 11, 'PO-MW-2025-001',  400,  '2025-06-01', 'fulfilled'),
(12, 2, 3, 12, 'PO-MW-2025-002',  150,  '2025-07-01', 'sent'),
-- HealthTex → Baltic + Anatolia
(13, 3, 2, 13, 'PO-HT-2025-001', 1000,  '2025-06-15', 'fulfilled'),
(14, 3, 3, 14, 'PO-HT-2025-002',  100,  '2025-08-01', 'draft'),
-- NordicCare as brand → Porto
(15, 4, 1, 15, 'PO-NC-2025-001',  200,  '2025-06-01', 'accepted'),
-- VårdTex → NordicCare as supplier
(16, 1, 4, 1,  'PO-VT-2025-010',  300,  '2025-07-01', 'accepted'),
-- NordicCare self-supply (brand → own supplier)
(17, 4, 4, 16, 'PO-NC-2025-002',  100,  '2025-06-15', 'accepted');

-- ============================================
-- SECTION 6b: PURCHASE ORDER LINES
-- ============================================

-- PO 1: Scrubs Tunika (500 st)
INSERT INTO purchase_order_lines (purchase_order_id, product_variant_id, quantity) VALUES
(1, 1, 80), (1, 2, 140), (1, 3, 120), (1, 4, 60), (1, 5, 60), (1, 6, 40);
-- PO 2: Scrubs Byxa (500 st)
INSERT INTO purchase_order_lines (purchase_order_id, product_variant_id, quantity) VALUES
(2, 10, 100), (2, 11, 180), (2, 12, 150), (2, 13, 70);
-- PO 3: Labbrock (200 st)
INSERT INTO purchase_order_lines (purchase_order_id, product_variant_id, quantity) VALUES
(3, 20, 30), (3, 21, 70), (3, 22, 70), (3, 23, 30);
-- PO 4: Patientskjorta (300 st)
INSERT INTO purchase_order_lines (purchase_order_id, product_variant_id, quantity) VALUES
(4, 30, 80), (4, 31, 80), (4, 32, 70), (4, 33, 70);
-- PO 5: Operationsrock (400 st)
INSERT INTO purchase_order_lines (purchase_order_id, product_variant_id, quantity) VALUES
(5, 34, 80), (5, 35, 130), (5, 36, 130), (5, 37, 60);
-- PO 6: Isoleringsrock (400 st)
INSERT INTO purchase_order_lines (purchase_order_id, product_variant_id, quantity) VALUES
(6, 38, 80), (6, 39, 130), (6, 40, 130), (6, 41, 60);
-- PO 7: Uppvärmningsjacka (150 st)
INSERT INTO purchase_order_lines (purchase_order_id, product_variant_id, quantity) VALUES
(7, 42, 25), (7, 43, 50), (7, 44, 50), (7, 45, 25);
-- PO 8: Patientrock (200 st)
INSERT INTO purchase_order_lines (purchase_order_id, product_variant_id, quantity) VALUES
(8, 46, 40), (8, 47, 60), (8, 48, 60), (8, 49, 40);
-- PO 9: Operationsmössa (1000 st) - cancelled
INSERT INTO purchase_order_lines (purchase_order_id, product_variant_id, quantity) VALUES
(9, 50, 300), (9, 51, 400), (9, 52, 300);
-- PO 10: Skyddsoverall (300 st) - draft
INSERT INTO purchase_order_lines (purchase_order_id, product_variant_id, quantity) VALUES
(10, 53, 30), (10, 54, 80), (10, 55, 90), (10, 56, 70), (10, 57, 30);
-- PO 11: MediWear Scrub Top (400 st)
INSERT INTO purchase_order_lines (purchase_order_id, product_variant_id, quantity) VALUES
(11, 60, 60), (11, 61, 140), (11, 62, 140), (11, 63, 60);
-- PO 12: MediWear Scrub Pants (150 st) - sent
INSERT INTO purchase_order_lines (purchase_order_id, product_variant_id, quantity) VALUES
(12, 64, 25), (12, 65, 50), (12, 66, 50), (12, 67, 25);
-- PO 13: HealthTex Basic Tunic (1000 st)
INSERT INTO purchase_order_lines (purchase_order_id, product_variant_id, quantity) VALUES
(13, 70, 150), (13, 71, 350), (13, 72, 350), (13, 73, 150);
-- PO 14: HealthTex Scrub Dress (100 st) - draft
INSERT INTO purchase_order_lines (purchase_order_id, product_variant_id, quantity) VALUES
(14, 74, 15), (14, 75, 30), (14, 76, 35), (14, 77, 20);
-- PO 15: NordicCare Scrub Top → Porto (200 st)
INSERT INTO purchase_order_lines (purchase_order_id, product_variant_id, quantity) VALUES
(15, 80, 30), (15, 81, 70), (15, 82, 70), (15, 83, 30);
-- PO 16: VårdTex Scrubs Tunika → NordicCare (300 st)
INSERT INTO purchase_order_lines (purchase_order_id, product_variant_id, quantity) VALUES
(16, 1, 50), (16, 2, 90), (16, 3, 80), (16, 4, 40), (16, 5, 25), (16, 6, 15);
-- PO 17: NordicCare self-supply Scrub Pants (100 st)
INSERT INTO purchase_order_lines (purchase_order_id, product_variant_id, quantity) VALUES
(17, 84, 15), (17, 85, 35), (17, 86, 35), (17, 87, 15);

-- ============================================
-- SECTION 7: BATCHES
-- Batch 6: NO materials → dashboard: batches_without_materials
-- Batch 5, 9: in_production + NO items → dashboard: batches_without_items
-- ============================================

INSERT INTO batches (id, purchase_order_id, batch_number, production_date, quantity, _status) VALUES
-- PO 1 (Scrubs Tunika, fulfilled): 2 batches (fabric roll change)
(1,  1, 'PT-2025-001', '2025-01-15', 350, 'completed'),
(2,  1, 'PT-2025-002', '2025-01-22', 150, 'completed'),
-- PO 2 (Scrubs Byxa, fulfilled): 1 batch
(3,  2, 'PT-2025-003', '2025-01-15', 500, 'completed'),
-- PO 3 (Labbrock, fulfilled): 1 batch
(4,  3, 'PT-2025-004', '2025-01-20', 200, 'completed'),
-- PO 4 (Patientskjorta, accepted): 1 batch in_production
(5,  4, 'PT-2025-005', '2025-02-01', 300, 'in_production'),
-- PO 6 (Isoleringsrock, sent): 1 batch in_production, NO materials
(6,  6, 'PT-2025-006', '2025-03-01', 400, 'in_production'),
-- PO 7 (Uppvärmningsjacka, accepted): 1 batch completed
(7,  7, 'PT-2025-007', '2025-02-15', 150, 'completed'),
-- PO 8 (Patientrock, accepted): 1 batch completed
(8,  8, 'PT-2025-008', '2025-02-20', 200, 'completed'),
-- PO 8 (Patientrock): 2nd batch in_production, has material but NO items
(9,  8, 'PT-2025-009', '2025-03-10', 50,  'in_production'),
-- PO 11 (MediWear Scrub Top, fulfilled): 1 batch
(10, 11, 'PT-2025-010', '2025-01-18', 400, 'completed'),
-- PO 13 (HealthTex Basic Tunic, fulfilled): 1 batch
(20, 13, 'BT-2025-001', '2025-01-22', 1000, 'completed'),
-- PO 16 (VårdTex→NordicCare, accepted): 1 batch
(30, 16, 'NC-2025-001', '2025-04-01', 300, 'in_production'),
-- PO 17 (NordicCare self-supply, accepted): 1 batch
(31, 17, 'NC-2025-002', '2025-04-10', 100, 'completed');

-- ============================================
-- SECTION 7b: BATCH MATERIALS
-- Batch 6 has NONE → dashboard trigger
-- ============================================

INSERT INTO batch_materials (batch_id, factory_material_id, component) VALUES
(1,  1, 'Body fabric'),   -- Tunika batch 1: PolyCotton Twill 155gsm
(2,  1, 'Body fabric'),   -- Tunika batch 2: PolyCotton Twill 155gsm (diff roll)
(3,  1, 'Body fabric'),   -- Byxa: PolyCotton Twill 155gsm
(4,  3, 'Body fabric'),   -- Labbrock: Antimicrobial Polyester
(5,  4, 'Body fabric'),   -- Patientskjorta: Organic Cotton
-- Batch 6: NO materials → dashboard: batches_without_materials
(7,  3, 'Body fabric'),   -- Uppvärmningsjacka: Antimicrobial Polyester
(8,  4, 'Body fabric'),   -- Patientrock: Organic Cotton Jersey
(9,  4, 'Body fabric'),   -- Patientrock batch 2: Organic Cotton Jersey (has material, no items)
(10, 1, 'Body fabric'),   -- MediWear Scrub Top: PolyCotton Twill
(20, 10, 'Body fabric');   -- HealthTex Basic Tunic: Baltic Recycled Polyester
-- NordicCare as supplier
INSERT INTO batch_materials (batch_id, factory_material_id, component) VALUES
(30, 30, 'Body fabric'),  -- VårdTex→NordicCare: Borås PolyCotton
(31, 30, 'Body fabric');   -- NordicCare self-supply: Borås PolyCotton

-- ============================================
-- SECTION 7c: ITEMS (serialized products with RFID TID)
-- Batch 5: in_production, 0 items → dashboard trigger
-- Batch 9: in_production, 0 items → dashboard trigger
-- ============================================

INSERT INTO items (batch_id, product_variant_id, unique_product_id, tid, sgtin, serial_number, _status) VALUES
-- Batch 1: Scrubs Tunika batch 1 (350 st, showing 4 samples)
(1, 2, 'VT-2025-001-00001', 'E280689400001001', '7350012345001.00001', '00001', 'produced'),
(1, 2, 'VT-2025-001-00002', 'E280689400001002', '7350012345001.00002', '00002', 'produced'),
(1, 3, 'VT-2025-001-00003', 'E280689400001003', '7350012345001.00003', '00003', 'shipped'),
(1, 1, 'VT-2025-001-00004', 'E280689400001004', '7350012345001.00004', '00004', 'shipped'),
-- Batch 2: Scrubs Tunika batch 2 (150 st, showing 2 samples)
(2, 2, 'VT-2025-002-00001', 'E280689400002001', '7350012345001.00005', '00001', 'produced'),
(2, 5, 'VT-2025-002-00002', 'E280689400002002', '7350012345001.00006', '00002', 'produced'),
-- Batch 3: Scrubs Byxa (500 st, showing 3 samples)
(3, 11, 'VT-2025-003-00001', 'E280689400003001', '7350012345002.00001', '00001', 'produced'),
(3, 12, 'VT-2025-003-00002', 'E280689400003002', '7350012345002.00002', '00002', 'shipped'),
(3, 10, 'VT-2025-003-00003', 'E280689400003003', '7350012345002.00003', '00003', 'produced'),
-- Batch 4: Labbrock (200 st, showing 3 samples)
(4, 21, 'VT-2025-004-00001', 'E280689400004001', '7350012345003.00001', '00001', 'produced'),
(4, 22, 'VT-2025-004-00002', 'E280689400004002', '7350012345003.00002', '00002', 'produced'),
(4, 20, 'VT-2025-004-00003', 'E280689400004003', '7350012345003.00003', '00003', 'shipped'),
-- Batch 7: Uppvärmningsjacka (150 st, showing 3 samples)
(7, 43, 'VT-2025-007-00001', 'E280689400007001', '7350012345007.00001', '00001', 'shipped'),
(7, 44, 'VT-2025-007-00002', 'E280689400007002', '7350012345007.00002', '00002', 'shipped'),
(7, 42, 'VT-2025-007-00003', 'E280689400007003', '7350012345007.00003', '00003', 'shipped'),
-- Batch 8: Patientrock (200 st, showing 3 samples)
(8, 47, 'VT-2025-008-00001', 'E280689400008001', '7350012345008.00001', '00001', 'shipped'),
(8, 46, 'VT-2025-008-00002', 'E280689400008002', '7350012345008.00002', '00002', 'shipped'),
(8, 48, 'VT-2025-008-00003', 'E280689400008003', '7350012345008.00003', '00003', 'shipped'),
-- Batch 10: MediWear Scrub Top (400 st, showing 2 samples)
(10, 61, 'MW-2025-010-00001', 'E280689400010001', '7350098765001.00001', '00001', 'produced'),
(10, 62, 'MW-2025-010-00002', 'E280689400010002', '7350098765001.00002', '00002', 'produced'),
-- Batch 20: HealthTex Basic Tunic (1000 st, showing 3 samples)
(20, 71, 'HT-2025-001-00001', 'E280689400020001', '6430012345001.00001', '00001', 'produced'),
(20, 72, 'HT-2025-001-00002', 'E280689400020002', '6430012345001.00002', '00002', 'produced'),
(20, 70, 'HT-2025-001-00003', 'E280689400020003', '6430012345001.00003', '00003', 'produced'),
-- Batch 31: NordicCare self-supply Scrub Pants (100 st, 2 samples)
(31, 85, 'NC-2025-002-00001', 'E280689400031001', '7350054321002.00001', '00001', 'produced'),
(31, 86, 'NC-2025-002-00002', 'E280689400031002', '7350054321002.00002', '00002', 'produced');

-- ============================================
-- SECTION 8: CARE INFORMATION
-- Products 1-4, 7, 8, 11-14 have care → complete
-- Products 5, 6, 9, 10 missing care → incomplete (contributes to dashboard)
-- ============================================

INSERT INTO care_information (product_id, care_image_url, care_text, safety_information) VALUES
(1,  '/images/care/wash-85.svg', 'Industriell tvätt 85°C. Torktumla på hög värme. Stryk vid behov.', 'Inga kända risker vid normal användning.'),
(2,  '/images/care/wash-85.svg', 'Industriell tvätt 85°C. Torktumla på hög värme.', 'Inga kända risker vid normal användning.'),
(3,  '/images/care/wash-60.svg', 'Maskintvätt 60°C. Torktumla på medelvärme. Stryk vid behov.', 'Inga kända risker vid normal användning.'),
(4,  '/images/care/wash-40.svg', 'Maskintvätt 40°C. Torktumla på låg värme.', 'GOTS-certifierad produkt.'),
(7,  '/images/care/wash-40.svg', 'Maskintvätt 40°C. Torktumla på låg värme. Bleka ej.', 'Antimikrobiell behandling kan minska efter 50+ tvättar.'),
(8,  '/images/care/wash-40.svg', 'Maskintvätt 40°C. Torktumla på låg värme.', NULL),
(11, '/images/care/wash-60.svg', 'Machine wash 60°C. Tumble dry medium.', NULL),
(12, '/images/care/wash-60.svg', 'Machine wash 60°C. Tumble dry medium.', NULL),
(13, '/images/care/wash-60.svg', 'Machine wash 60°C. Tumble dry medium.', NULL),
(14, '/images/care/wash-60.svg', 'Machine wash 60°C. Tumble dry medium.', NULL),
(15, '/images/care/wash-85.svg', 'Industriell tvätt 85°C. Torktumla på hög värme.', 'Inga kända risker vid normal användning.'),
(16, '/images/care/wash-85.svg', 'Industriell tvätt 85°C. Torktumla på hög värme.', NULL);

-- ============================================
-- SECTION 9: COMPLIANCE INFORMATION
-- Products 1-4, 7, 8, 11-14 have compliance
-- Products 5, 6, 9, 10 missing compliance → incomplete
-- ============================================

INSERT INTO compliance_info (product_id, harmful_substances, harmful_substances_info, certifications, chemical_compliance_standard, chemical_compliance_validation, microfibers, traceability_provider) VALUES
(1,  'No', NULL, 'Oeko-Tex Standard 100', 'REACH', 'Validated Q1 2025', 'No',  'Hospitex DPP'),
(2,  'No', NULL, 'Oeko-Tex Standard 100', 'REACH', 'Validated Q1 2025', 'No',  'Hospitex DPP'),
(3,  'No', NULL, 'Oeko-Tex Standard 100', 'REACH', 'Validated Q1 2025', 'Yes', 'Hospitex DPP'),
(4,  'No', NULL, 'GOTS, Oeko-Tex Standard 100', 'REACH', 'Validated Q1 2025', 'No', 'Hospitex DPP'),
(7,  'No', NULL, 'GRS', 'REACH', 'Validated Q4 2024', 'Yes', 'Hospitex DPP'),
(8,  'No', NULL, 'Oeko-Tex Standard 100', 'REACH', 'Validated Q1 2025', 'No',  'Hospitex DPP'),
(11, 'No', NULL, 'Oeko-Tex Standard 100', 'REACH', 'Validated Q1 2025', 'No',  'Hospitex DPP'),
(12, 'No', NULL, 'Oeko-Tex Standard 100', 'REACH', 'Validated Q1 2025', 'No',  'Hospitex DPP'),
(13, 'No', NULL, NULL, 'REACH', 'Validated Q1 2025', 'No', 'Hospitex DPP'),
(14, 'No', NULL, NULL, 'REACH', 'Validated Q1 2025', 'No', 'Hospitex DPP'),
(15, 'No', NULL, 'Oeko-Tex Standard 100', 'REACH', 'Validated Q1 2025', 'No', 'Hospitex DPP'),
(16, 'No', NULL, 'Oeko-Tex Standard 100', 'REACH', 'Validated Q1 2025', 'No', 'Hospitex DPP');

-- ============================================
-- SECTION 10: PRODUCT CERTIFICATIONS
-- ============================================

INSERT INTO product_certifications (product_id, certification_name, validation_document_url, valid_until) VALUES
(1,  'Oeko-Tex Standard 100', 'https://example.com/certs/vt-tunika-oekotex.pdf', '2027-12-31'),
(3,  'Oeko-Tex Standard 100', 'https://example.com/certs/vt-labbrock-oekotex.pdf', '2027-12-31'),
(4,  'GOTS', 'https://example.com/certs/vt-patient-gots.pdf', '2027-06-30'),
(7,  'GRS', 'https://example.com/certs/vt-jacket-grs.pdf', '2026-06-30');

-- ============================================
-- SECTION 11: CIRCULARITY
-- Products 1, 2, 4, 7, 8 have circularity
-- ============================================

INSERT INTO circularity_info (product_id, recyclability, take_back_instructions, recycling_instructions, circular_design_strategy, repair_instructions) VALUES
(1, 'Mekanisk återvinning möjlig', 'Returnera till VårdTex återvinningsprogram', 'Separera knappar och dragkedjor före återvinning', 'longevity', 'Professionell lagning rekommenderas'),
(2, 'Mekanisk återvinning möjlig', 'Returnera till VårdTex återvinningsprogram', 'Separera resårband före återvinning', 'longevity', 'Professionell lagning rekommenderas'),
(4, 'Kompostering möjlig (100% bomull)', 'Returnera till VårdTex återvinningsprogram', 'Ta bort knappar, kompostera tyget', 'material_cyclability', 'Enkel lagning möjlig'),
(7, 'Textilåtervinning via insamlingsprogram', 'Returnera till VårdTex insamlingspunkt eller sjukhusets textilretur', 'Ta bort dragkedja före återvinning. 100% polyester lämpar sig för mekanisk återvinning.', 'design_for_recyclability', 'Byt dragkedja hos auktoriserat reparationscenter.'),
(8, 'Textilåtervinning via insamlingsprogram', 'Returnera till VårdTex insamlingspunkt eller sjukhusets textilretur', 'Ta bort bälte före återvinning. 100% bomull lämpar sig för kompostering eller återvinning.', 'design_for_longevity', 'Byt bältesband. Laga sömmar på sjukhusets textilavdelning.');

-- ============================================
-- SECTION 12: SUSTAINABILITY
-- Products 1, 4, 7 have sustainability
-- ============================================

INSERT INTO sustainability_info (product_id, brand_statement, statement_link, environmental_footprint) VALUES
(1, 'VårdTex arbetar för hållbar vårdtextil genom ansvarsfull produktion och cirkulära lösningar.', 'https://vardtex.se/hallbarhet', 'Carbon footprint: 8.5 kg CO2e'),
(4, 'GOTS-certifierad produkt tillverkad med 100% ekologisk bomull.', 'https://vardtex.se/hallbarhet/gots', 'Carbon footprint: 4.2 kg CO2e'),
(7, 'VårdTex är engagerade i hållbar vårdtextil. Denna jacka är tillverkad av 100% återvunnen polyester med antimikrobiell behandling.', 'https://vardtex.se/hallbarhet', 'Carbon footprint: 6.1 kg CO2e');

-- ============================================
-- SECTION 13: DPP EXPORTS (products 1, 2 already exported)
-- ============================================

INSERT INTO dpp_exports (product_id, batch_id, export_type, export_format, exported_to, exported_at) VALUES
(1, 1, 'batch', 'json', 'Trace4Value Pilot Host', '2025-03-05 10:30:00'),
(1, 2, 'batch', 'json', 'Trace4Value Pilot Host', '2025-03-05 10:45:00'),
(2, 3, 'batch', 'json', 'Trace4Value Pilot Host', '2025-03-18 14:15:00');

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

SELECT 'Test data loaded successfully!' AS status;
SELECT COUNT(*) AS brands FROM brands;
SELECT COUNT(*) AS suppliers FROM suppliers;
SELECT COUNT(*) AS brand_supplier_relations FROM brand_suppliers;
SELECT COUNT(*) AS factory_materials FROM factory_materials;
SELECT COUNT(*) AS products FROM products;
SELECT COUNT(*) AS product_variants FROM product_variants;
SELECT COUNT(*) AS purchase_orders FROM purchase_orders;
SELECT COUNT(*) AS purchase_order_lines FROM purchase_order_lines;
SELECT COUNT(*) AS batches FROM batches;
SELECT COUNT(*) AS batch_materials FROM batch_materials;
SELECT COUNT(*) AS items FROM items;

-- ============================================
-- VERIFICATION SUMMARY
-- ============================================
--
-- DUAL-ROLE TENANT:
--   NordicCare Textil = brand (id=4) + supplier (id=4)
--   Brand key: brand_nordiccare_test_key_003
--   Supplier key: supplier_nordiccare_test_key_003
--   As brand: 2 products (15, 16), 2 POs (15→Porto, 17→self)
--   As supplier: 2 POs incoming (16 from VårdTex, 17 self), 2 batches, 2 materials
--
-- DASHBOARD TRIGGERS (Brand = VårdTex):
--   pending_orders: 2 (PO 5=sent, PO 6=sent)
--   completed_batches: 6 (batch 1, 2, 3, 4, 7, 8)
--   incomplete_products: 4 (product 5=no care/compliance, 6=no care/compliance,
--                           9=no care/compliance, 10=no components/care/compliance)
--   export_ready: ~4 (products 3, 4, 7, 8 = complete data + no export)
--   expiring_certifications: 2 (material 1 GOTS→2026-03-10, material 3 GRS→2026-02-28)
--
-- DASHBOARD TRIGGERS (Supplier = Porto):
--   pending_orders: 2 (PO 5=sent, PO 6=sent)
--   batches_without_materials: 1 (batch 6)
--   batches_without_items: 2 (batch 5=in_production, batch 9=in_production)
--   incomplete_materials: 1 (material 7 = no compositions)
--   expiring_certifications: 2 (material 1 GOTS→2026-03-10, material 3 GRS→2026-02-28)
--
-- MULTI-TENANT VERIFICATION:
--   VårdTex (brand 1): 10 products, 10+1 POs (Porto + NordicCare)
--   MediWear (brand 2): 2 products, 2 POs (Porto + Anatolia)
--   HealthTex (brand 3): 2 products, 2 POs (Baltic + Anatolia)
--   NordicCare (brand 4): 2 products, 2 POs (Porto + self)
--   Each brand can only see own products/POs via API key
--
-- PO STATUS DISTRIBUTION:
--   draft: 2 (PO 10, 14)
--   sent: 2 (PO 5, 6)
--   accepted: 6 (PO 4, 7, 8, 15, 16, 17)
--   fulfilled: 5 (PO 1, 2, 3, 11, 13)
--   cancelled: 1 (PO 9)
--
-- TOTALS:
--   4 brands, 4 suppliers, 9 relations
--   13 factory materials (1 inactive, 1 without compositions)
--   16 products, 85 variants
--   17 purchase orders, ~75 PO lines
--   13 batches, 12 batch_material links
--   27 items (serialized with RFID TID + SGTIN)
--   12 care records, 12 compliance records
--   5 circularity, 3 sustainability
--   4 product certifications, 12 material certifications
--   3 DPP exports