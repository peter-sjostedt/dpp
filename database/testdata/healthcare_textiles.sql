-- ============================================
-- DPP Test Data: Healthcare Textiles
-- Realistic data for scrubs, patient gowns, lab coats
-- ============================================

-- ============================================
-- BRANDS
-- ============================================

INSERT INTO brands (id, brand_name, logo_url, sub_brand, parent_company, trader, trader_location, lei, gs1_company_prefix, api_key, _is_active) VALUES
(1, 'VårdTex', '/images/vardtex-logo.png', 'VårdTex Pro', 'VårdTex AB', 'VårdTex AB', 'Medicinaregatan 8, 413 90 Göteborg, Sweden', '549300VARDTEX00001SE', '7350012345', 'brand_vardtex_test_key_000', TRUE),
(2, 'MediWear', '/images/mediwear-logo.png', 'MediWear Pro', 'MediWear International AB', 'MediWear International AB', 'Industrivägen 45, 171 48 Solna, Sweden', '549300MEDIWEAR0002SE', '7350098765', 'brand_mediwear_test_key_001', TRUE),
(3, 'HealthTex', '/images/healthtex-logo.png', 'HealthTex Care', 'HealthTex Nordic Oy', 'HealthTex Nordic Oy', 'Terveyskatu 12, 00100 Helsinki, Finland', '549300HEALTHTEX03FI', '6430012345', 'brand_healthtex_test_key_002', TRUE);

-- ============================================
-- SUPPLIERS
-- ============================================

INSERT INTO suppliers (id, supplier_name, supplier_location, facility_registry, facility_identifier, operator_registry, operator_identifier, country_of_origin_confection, country_of_origin_dyeing, country_of_origin_weaving, lei, gs1_company_prefix, api_key, _is_active) VALUES
(1, 'Têxtil Hospitalar Porto', 'Zona Industrial de Maia, 4470-122 Porto, Portugal', 'GLN', '5609876543210', 'GLN', '5609876543210', 'PT', 'PT', 'PT', '549300PORTOTEXTIL01', '5609876543', 'supplier_porto_test_key_000', TRUE),
(2, 'Baltic Textile Factory', 'Klaipeda FEZ, LT-94101 Klaipeda, Lithuania', 'GLN', '4771234567890', 'GLN', '4771234567890', 'LT', 'LT', 'LT', '549300BALTICTEX02LT', '4771234567', 'supplier_baltic_test_key_001', TRUE),
(3, 'Aegean Healthcare Fabrics', 'Organized Industrial Zone, 35410 Izmir, Turkey', 'GLN', '8691234567890', 'GLN', '8691234567890', 'TR', 'TR', 'TR', '549300AEGEANFAB03TR', '8691234567', 'supplier_aegean_test_key_002', TRUE);

-- ============================================
-- BRAND-SUPPLIER RELATIONSHIPS
-- ============================================

INSERT INTO brand_suppliers (brand_id, supplier_id, _is_active) VALUES
(1, 1, TRUE),   -- VårdTex <-> Porto
(1, 2, TRUE),   -- VårdTex <-> Baltic
(2, 1, TRUE),   -- MediWear <-> Porto
(2, 3, TRUE),   -- MediWear <-> Aegean
(3, 2, TRUE),   -- HealthTex <-> Baltic
(3, 3, TRUE);   -- HealthTex <-> Aegean

-- ============================================
-- FACTORY MATERIALS (Porto)
-- ============================================

INSERT INTO factory_materials (id, supplier_id, material_name, material_type, description, _is_active) VALUES
(1, 1, 'PolyCotton Twill 65/35 155gsm', 'Textile', 'Standard healthcare fabric, industrial wash compatible', TRUE),
(2, 1, 'PolyCotton Twill 65/35 195gsm', 'Textile', 'Heavy-duty healthcare fabric for lab coats', TRUE),
(3, 1, 'Polyester Antimicrobial 170gsm', 'Textile', 'Antimicrobial treated polyester', TRUE),
(4, 1, 'Organic Cotton Jersey 160gsm', 'Textile', 'GOTS certified organic cotton for patient wear', TRUE),
(5, 1, 'Polyester Barrier Fabric 120gsm', 'Textile', 'EN 13795 compliant barrier fabric', TRUE);

-- FACTORY MATERIALS (Baltic)
INSERT INTO factory_materials (id, supplier_id, material_name, material_type, description, _is_active) VALUES
(10, 2, 'Baltic Polyester 180gsm', 'Textile', 'High-quality recycled polyester', TRUE),
(11, 2, 'Baltic Blend 50/50 200gsm', 'Textile', 'Poly-cotton blend for durability', TRUE);

-- FACTORY MATERIALS (Aegean)
INSERT INTO factory_materials (id, supplier_id, material_name, material_type, description, _is_active) VALUES
(20, 3, 'Aegean Premium Blend 185gsm', 'Textile', 'Premium healthcare fabric', TRUE),
(21, 3, 'Aegean Surgical Grade 120gsm', 'Textile', 'Surgical grade barrier fabric', TRUE);

-- ============================================
-- MATERIAL COMPOSITIONS
-- ============================================

-- Porto materials
INSERT INTO factory_material_compositions (factory_material_id, content_name, content_value, content_source, recycled, recycled_percentage, recycled_input_source) VALUES
(1, 'Polyester', 65.00, 'Conventional', FALSE, NULL, NULL),
(1, 'Cotton', 35.00, 'Conventional', FALSE, NULL, NULL),
(2, 'Polyester', 65.00, 'Conventional', FALSE, NULL, NULL),
(2, 'Cotton', 35.00, 'Conventional', FALSE, NULL, NULL),
(3, 'Polyester', 100.00, 'Conventional', FALSE, NULL, NULL),
(4, 'Cotton', 100.00, 'Organic', FALSE, NULL, NULL),
(5, 'Polyester', 100.00, 'Conventional', FALSE, NULL, NULL);

-- Baltic materials
INSERT INTO factory_material_compositions (factory_material_id, content_name, content_value, content_source, recycled, recycled_percentage, recycled_input_source) VALUES
(10, 'Polyester', 100.00, 'Recycled', TRUE, 100.00, 'Post-consumer'),
(11, 'Polyester', 50.00, 'Conventional', FALSE, NULL, NULL),
(11, 'Cotton', 50.00, 'Conventional', FALSE, NULL, NULL);

-- Aegean materials
INSERT INTO factory_material_compositions (factory_material_id, content_name, content_value, content_source, recycled, recycled_percentage, recycled_input_source) VALUES
(20, 'Polyester', 65.00, 'Conventional', FALSE, NULL, NULL),
(20, 'Cotton', 35.00, 'Conventional', FALSE, NULL, NULL),
(21, 'Polypropylene', 100.00, 'Conventional', FALSE, NULL, NULL);

-- ============================================
-- MATERIAL CERTIFICATIONS
-- ============================================

INSERT INTO factory_material_certifications (factory_material_id, certification, certification_id, valid_until) VALUES
(1, 'Oeko-Tex Standard 100', 'OTS-2024-001', '2025-12-31'),
(2, 'Oeko-Tex Standard 100', 'OTS-2024-002', '2025-12-31'),
(3, 'Oeko-Tex Standard 100', 'OTS-2024-003', '2025-12-31'),
(4, 'GOTS', 'GOTS-2024-001', '2025-06-30'),
(4, 'Oeko-Tex Standard 100', 'OTS-2024-004', '2025-12-31'),
(5, 'EN 13795', 'EN13795-2024-001', '2026-02-28'),
(10, 'GRS', 'GRS-2024-001', '2025-09-30'),
(21, 'EN 13795', 'EN13795-2024-002', '2026-02-28');

-- ============================================
-- MATERIAL SUPPLY CHAIN
-- ============================================

INSERT INTO factory_material_supply_chain (factory_material_id, sequence, process_step, country, facility_name, facility_identifier) VALUES
(1, 1, 'Fiber production', 'IN', 'Reliance Industries', 'GLN-8901234500001'),
(1, 2, 'Spinning', 'IN', 'Vardhman Textiles', 'GLN-8901234500002'),
(1, 3, 'Weaving', 'PT', 'Têxtil Hospitalar Porto', 'GLN-5609876543210'),
(1, 4, 'Dyeing', 'PT', 'Têxtil Hospitalar Porto', 'GLN-5609876543210'),
(1, 5, 'Finishing', 'PT', 'Têxtil Hospitalar Porto', 'GLN-5609876543210'),
(4, 1, 'Fiber production', 'IN', 'Chetna Organic', 'GLN-8901234500010'),
(4, 2, 'Spinning', 'IN', 'Armstrong Spinning Mills', 'GLN-8901234500011'),
(4, 3, 'Weaving', 'PT', 'Têxtil Hospitalar Porto', 'GLN-5609876543210'),
(4, 4, 'Dyeing', 'PT', 'Têxtil Hospitalar Porto', 'GLN-5609876543210');

-- ============================================
-- PRODUCTS (VårdTex)
-- ============================================

INSERT INTO products (id, brand_id, product_id_system, product_id_value, product_name, description, article_number, commodity_code_system, commodity_code_number, year_of_sale, season_of_sale, price_currency, msrp, category, product_group, type_line_concept, type_item, age_group, gender, market_segment, net_weight, data_carrier_type, data_carrier_material, data_carrier_location, _is_active) VALUES
(1, 1, 'GTIN', '7350012345001', 'Scrubs Tunika', 'Klassisk V-ringad scrubstopp för vårdpersonal. Bröstficka och sidfickor. Tål industriell tvätt 85°C.', 'VT-SCR-TOP-001', 'HS', '6211339000', 2025, 'SP', 'EUR', 34.95, 'clothing', 'Top', 'Healthcare', 'Tunic', 'Adult', 'unisex', 'mid-price', 0.185, 'RFID', 'Woven label', 'Side seam', TRUE),
(2, 1, 'GTIN', '7350012345002', 'Scrubs Byxa', 'Bekväm scrubsbyxa med resår och dragsko. Sidfickor och bakficka. Tål industriell tvätt 85°C.', 'VT-SCR-BTM-001', 'HS', '6203439000', 2025, 'SP', 'EUR', 32.95, 'clothing', 'Bottom', 'Healthcare', 'Pants', 'Adult', 'unisex', 'mid-price', 0.210, 'RFID', 'Woven label', 'Waistband', TRUE),
(3, 1, 'GTIN', '7350012345003', 'Labbrock', 'Klassisk vit labbrock med knäppning. Bröstficka och sidfickor. Antimikrobiellt tyg.', 'VT-LAB-001', 'HS', '6211329000', 2025, 'SP', 'EUR', 59.95, 'clothing', 'Top', 'Healthcare', 'Lab coat', 'Adult', 'unisex', 'mid-price', 0.320, 'RFID', 'Woven label', 'Neck label', TRUE),
(4, 1, 'GTIN', '7350012345004', 'Patientskjorta', 'Öppen patientskjorta med tryckknappsstängning. GOTS-certifierad ekologisk bomull.', 'VT-PAT-001', 'HS', '6211429000', 2025, 'SP', 'EUR', 24.95, 'clothing', 'Top', 'Healthcare', 'Patient gown', 'Adult', 'unisex', 'mid-price', 0.145, 'QR', 'Care label', 'Neck label', TRUE);

-- PRODUCTS (MediWear)
INSERT INTO products (id, brand_id, product_id_system, product_id_value, product_name, description, article_number, commodity_code_system, commodity_code_number, year_of_sale, season_of_sale, price_currency, msrp, category, product_group, type_line_concept, type_item, age_group, gender, market_segment, net_weight, data_carrier_type, data_carrier_material, data_carrier_location, _is_active) VALUES
(10, 2, 'GTIN', '7350098765001', 'MediWear Scrub Top', 'Premium scrub top with modern fit', 'MW-SCR-001', 'HS', '6211339000', 2025, 'SP', 'EUR', 39.95, 'clothing', 'Top', 'Healthcare', 'Tunic', 'Adult', 'unisex', 'premium', 0.195, 'RFID', 'Woven label', 'Side seam', TRUE),
(11, 2, 'GTIN', '7350098765002', 'MediWear Scrub Pants', 'Ergonomic scrub pants with cargo pockets', 'MW-SCR-002', 'HS', '6203439000', 2025, 'SP', 'EUR', 37.95, 'clothing', 'Bottom', 'Healthcare', 'Pants', 'Adult', 'unisex', 'premium', 0.225, 'RFID', 'Woven label', 'Waistband', TRUE);

-- PRODUCTS (HealthTex)
INSERT INTO products (id, brand_id, product_id_system, product_id_value, product_name, description, article_number, commodity_code_system, commodity_code_number, year_of_sale, season_of_sale, price_currency, msrp, category, product_group, type_line_concept, type_item, age_group, gender, market_segment, net_weight, data_carrier_type, data_carrier_material, data_carrier_location, _is_active) VALUES
(20, 3, 'GTIN', '6430012345001', 'HealthTex Basic Tunic', 'Affordable healthcare tunic', 'HT-BAS-001', 'HS', '6211339000', 2025, 'SP', 'EUR', 24.95, 'clothing', 'Top', 'Healthcare', 'Tunic', 'Adult', 'unisex', 'mass-market', 0.170, 'QR', 'Care label', 'Neck label', TRUE);

-- ============================================
-- PRODUCT COMPONENTS
-- ============================================

INSERT INTO product_components (product_id, component, material, content_name, content_value, content_source, recycled, finishes) VALUES
(1, 'Body fabric', 'Textile', 'Polyester', 65.00, 'Conventional', FALSE, 'Anti-microbial'),
(1, 'Body fabric', 'Textile', 'Cotton', 35.00, 'Conventional', FALSE, NULL),
(2, 'Body fabric', 'Textile', 'Polyester', 65.00, 'Conventional', FALSE, 'Anti-microbial'),
(2, 'Body fabric', 'Textile', 'Cotton', 35.00, 'Conventional', FALSE, NULL),
(3, 'Body fabric', 'Textile', 'Polyester', 100.00, 'Conventional', FALSE, 'Anti-microbial'),
(4, 'Body fabric', 'Textile', 'Cotton', 100.00, 'Organic', FALSE, NULL);

-- ============================================
-- PRODUCT VARIANTS
-- ============================================

-- VårdTex Scrubs Tunika
INSERT INTO product_variants (id, product_id, item_number, size, size_country_code, color_brand, color_general, gtin, _is_active) VALUES
(1, 1, 'VT-SCR-TOP-001-CBL-S', 'S', 'EU', 'Ceil Blue', 'blue', '73500123450011', TRUE),
(2, 1, 'VT-SCR-TOP-001-CBL-M', 'M', 'EU', 'Ceil Blue', 'blue', '73500123450012', TRUE),
(3, 1, 'VT-SCR-TOP-001-CBL-L', 'L', 'EU', 'Ceil Blue', 'blue', '73500123450013', TRUE),
(4, 1, 'VT-SCR-TOP-001-CBL-XL', 'XL', 'EU', 'Ceil Blue', 'blue', '73500123450014', TRUE),
(5, 1, 'VT-SCR-TOP-001-NVY-M', 'M', 'EU', 'Navy', 'navy', '73500123450015', TRUE),
(6, 1, 'VT-SCR-TOP-001-NVY-L', 'L', 'EU', 'Navy', 'navy', '73500123450016', TRUE);

-- VårdTex Scrubs Byxa
INSERT INTO product_variants (id, product_id, item_number, size, size_country_code, color_brand, color_general, gtin, _is_active) VALUES
(10, 2, 'VT-SCR-BTM-001-CBL-S', 'S', 'EU', 'Ceil Blue', 'blue', '73500123450021', TRUE),
(11, 2, 'VT-SCR-BTM-001-CBL-M', 'M', 'EU', 'Ceil Blue', 'blue', '73500123450022', TRUE),
(12, 2, 'VT-SCR-BTM-001-CBL-L', 'L', 'EU', 'Ceil Blue', 'blue', '73500123450023', TRUE);

-- VårdTex Labbrock
INSERT INTO product_variants (id, product_id, item_number, size, size_country_code, color_brand, color_general, gtin, _is_active) VALUES
(20, 3, 'VT-LAB-001-WHT-M', 'M', 'EU', 'White', 'white', '73500123450031', TRUE),
(21, 3, 'VT-LAB-001-WHT-L', 'L', 'EU', 'White', 'white', '73500123450032', TRUE);

-- VårdTex Patientskjorta
INSERT INTO product_variants (id, product_id, item_number, size, size_country_code, color_brand, color_general, gtin, _is_active) VALUES
(30, 4, 'VT-PAT-001-WHT-M', 'M', 'EU', 'White', 'white', '73500123450041', TRUE),
(31, 4, 'VT-PAT-001-LBL-M', 'M', 'EU', 'Light Blue', 'blue', '73500123450042', TRUE);

-- MediWear
INSERT INTO product_variants (id, product_id, item_number, size, size_country_code, color_brand, color_general, gtin, _is_active) VALUES
(50, 10, 'MW-SCR-001-TL-M', 'M', 'EU', 'Teal', 'teal', '73500987650011', TRUE),
(51, 10, 'MW-SCR-001-TL-L', 'L', 'EU', 'Teal', 'teal', '73500987650012', TRUE),
(52, 11, 'MW-SCR-002-TL-M', 'M', 'EU', 'Teal', 'teal', '73500987650021', TRUE);

-- HealthTex
INSERT INTO product_variants (id, product_id, item_number, size, size_country_code, color_brand, color_general, gtin, _is_active) VALUES
(60, 20, 'HT-BAS-001-WHT-M', 'M', 'EU', 'White', 'white', '64300123450011', TRUE),
(61, 20, 'HT-BAS-001-WHT-L', 'L', 'EU', 'White', 'white', '64300123450012', TRUE);

-- ============================================
-- BATCHES
-- ============================================

INSERT INTO batches (id, brand_id, supplier_id, product_id, batch_number, po_number, production_date, quantity, _status) VALUES
(1, 1, 1, 1, 'VT-2025-001', 'PO-VT-2025-001', '2025-01-15', 500, 'completed'),
(2, 1, 1, 2, 'VT-2025-002', 'PO-VT-2025-001', '2025-01-15', 500, 'completed'),
(3, 1, 1, 3, 'VT-2025-003', 'PO-VT-2025-002', '2025-01-20', 200, 'completed'),
(4, 1, 1, 4, 'VT-2025-004', 'PO-VT-2025-003', '2025-01-25', 300, 'in_production'),
(10, 2, 1, 10, 'MW-2025-001', 'PO-MW-2025-001', '2025-01-18', 400, 'completed'),
(20, 3, 2, 20, 'HT-2025-001', 'PO-HT-2025-001', '2025-01-22', 1000, 'completed');

-- ============================================
-- BATCH MATERIALS
-- ============================================

INSERT INTO batch_materials (batch_id, factory_material_id, component) VALUES
(1, 1, 'Body fabric'),
(2, 1, 'Body fabric'),
(3, 3, 'Body fabric'),
(4, 4, 'Body fabric'),
(10, 1, 'Body fabric'),
(20, 10, 'Body fabric');

-- ============================================
-- ITEMS
-- ============================================

-- VårdTex items
INSERT INTO items (id, batch_id, product_variant_id, unique_product_id, tid, sgtin, serial_number, _status) VALUES
(1, 1, 2, 'VT-2025-001-00001', 'E280689400001001', '7350012345001.00001', '00001', 'produced'),
(2, 1, 2, 'VT-2025-001-00002', 'E280689400001002', '7350012345001.00002', '00002', 'produced'),
(3, 1, 2, 'VT-2025-001-00003', 'E280689400001003', '7350012345001.00003', '00003', 'shipped'),
(4, 1, 3, 'VT-2025-001-00004', 'E280689400001004', '7350012345001.00004', '00004', 'produced'),
(5, 2, 11, 'VT-2025-002-00001', 'E280689400002001', '7350012345002.00001', '00001', 'produced'),
(6, 3, 20, 'VT-2025-003-00001', 'E280689400003001', '7350012345003.00001', '00001', 'produced');

-- MediWear items
INSERT INTO items (id, batch_id, product_variant_id, unique_product_id, tid, sgtin, serial_number, _status) VALUES
(100, 10, 50, 'MW-2025-001-00001', 'E280689400010001', '7350098765001.00001', '00001', 'produced'),
(101, 10, 50, 'MW-2025-001-00002', 'E280689400010002', '7350098765001.00002', '00002', 'produced');

-- HealthTex items
INSERT INTO items (id, batch_id, product_variant_id, unique_product_id, tid, sgtin, serial_number, _status) VALUES
(200, 20, 60, 'HT-2025-001-00001', 'E280689400020001', '6430012345001.00001', '00001', 'produced'),
(201, 20, 60, 'HT-2025-001-00002', 'E280689400020002', '6430012345001.00002', '00002', 'produced'),
(202, 20, 61, 'HT-2025-001-00003', 'E280689400020003', '6430012345001.00003', '00003', 'produced');

-- ============================================
-- CARE INFORMATION
-- ============================================

INSERT INTO care_information (product_id, care_image_url, care_text, safety_information) VALUES
(1, '/images/care/wash-85.svg', 'Industriell tvätt 85°C. Torktumla på hög värme. Stryk vid behov.', 'Inga kända risker vid normal användning.'),
(2, '/images/care/wash-85.svg', 'Industriell tvätt 85°C. Torktumla på hög värme.', 'Inga kända risker vid normal användning.'),
(3, '/images/care/wash-60.svg', 'Maskintvätt 60°C. Torktumla på medelvärme. Stryk vid behov.', 'Inga kända risker vid normal användning.'),
(4, '/images/care/wash-40.svg', 'Maskintvätt 40°C. Torktumla på låg värme.', 'GOTS-certifierad produkt.');

-- ============================================
-- COMPLIANCE INFO
-- ============================================

INSERT INTO compliance_info (product_id, harmful_substances, harmful_substances_info, certifications, chemical_compliance_standard, microfibers, traceability_provider) VALUES
(1, 'No', NULL, 'Oeko-Tex Standard 100', 'REACH', 'No', 'TextileGenesis'),
(2, 'No', NULL, 'Oeko-Tex Standard 100', 'REACH', 'No', 'TextileGenesis'),
(3, 'No', NULL, 'Oeko-Tex Standard 100', 'REACH', 'Yes', 'TextileGenesis'),
(4, 'No', NULL, 'GOTS, Oeko-Tex Standard 100', 'REACH', 'No', 'TextileGenesis');

-- ============================================
-- CIRCULARITY INFO
-- ============================================

INSERT INTO circularity_info (product_id, recyclability, take_back_instructions, recycling_instructions, circular_design_strategy, repair_instructions) VALUES
(1, 'Mekanisk återvinning möjlig', 'Returnera till VårdTex återvinningsprogram', 'Separera knappar och dragkedjor före återvinning', 'longevity', 'Professionell lagning rekommenderas'),
(2, 'Mekanisk återvinning möjlig', 'Returnera till VårdTex återvinningsprogram', 'Separera resårband före återvinning', 'longevity', 'Professionell lagning rekommenderas'),
(4, 'Kompostering möjlig (100% bomull)', 'Returnera till VårdTex återvinningsprogram', 'Ta bort knappar, kompostera tyget', 'material_cyclability', 'Enkel lagning möjlig');

-- ============================================
-- SUSTAINABILITY INFO
-- ============================================

INSERT INTO sustainability_info (product_id, brand_statement, statement_link, environmental_footprint) VALUES
(1, 'VårdTex arbetar för hållbar vårdtextil genom ansvarsfull produktion och cirkulära lösningar.', 'https://vardtex.se/hallbarhet', 'Carbon footprint: 8.5 kg CO2e'),
(4, 'GOTS-certifierad produkt tillverkad med 100% ekologisk bomull.', 'https://vardtex.se/hallbarhet/gots', 'Carbon footprint: 4.2 kg CO2e');

-- ============================================
-- SUMMARY
-- ============================================

SELECT 'Test data loaded successfully!' AS status;
SELECT COUNT(*) AS brands FROM brands;
SELECT COUNT(*) AS suppliers FROM suppliers;
SELECT COUNT(*) AS brand_supplier_relations FROM brand_suppliers;
SELECT COUNT(*) AS factory_materials FROM factory_materials;
SELECT COUNT(*) AS products FROM products;
SELECT COUNT(*) AS product_variants FROM product_variants;
SELECT COUNT(*) AS batches FROM batches;
SELECT COUNT(*) AS items FROM items;
