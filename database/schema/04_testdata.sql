-- ============================================
-- DPP Platform - Del 4: Testdata
-- ============================================

USE petersjo_dpp;

-- ============================================
-- FÖRETAG & ANVÄNDARE
-- ============================================

INSERT INTO companies (id, name, org_number) VALUES
(1, 'VårdTex AB', '556123-4567'),
(2, 'Têxtil Hospitalar Porto Lda', 'PT509876543');

INSERT INTO users (id, company_id, email, password_hash, role) VALUES
(1, 1, 'maria.lindberg@vardtex.se', '$2y$10$dummy_hash_for_testing', 'admin'),
(2, 1, 'johan.berg@vardtex.se', '$2y$10$dummy_hash_for_testing', 'brand_user'),
(3, 2, 'antonio.silva@textilarporto.pt', '$2y$10$dummy_hash_for_testing', 'admin'),
(4, 2, 'sofia.costa@textilarporto.pt', '$2y$10$dummy_hash_for_testing', 'factory_user');

-- ============================================
-- VARUMÄRKE & LEVERANTÖR
-- ============================================

INSERT INTO brands (id, company_id, brand_name, logo_url, sub_brand, parent_company, trader_name, trader_address) VALUES
(1, 1, 'VårdTex', '/images/vardtex-logo.png', 'VårdTex Pro', 'VårdTex AB', 'VårdTex AB', 'Medicinaregatan 8, 413 90 Göteborg, Sweden');

INSERT INTO suppliers (id, company_id, supplier_name, supplier_location, facility_registry, facility_identifier, operator_registry, operator_identifier) VALUES
(1, 2, 'Têxtil Hospitalar Porto', 'Zona Industrial de Maia, 4470-122 Porto, Portugal', 'GLN', '5609876543210', 'GLN', '5609876543210');

-- ============================================
-- FABRIKENS TYGLAGER
-- ============================================

INSERT INTO factory_materials (id, supplier_id, _internal_code, material_name, material_type, net_weight_per_meter, width_cm, _is_active) VALUES
(1, 1, 'PC-6535-155', 'PolyCotton Twill 65/35 155gsm', 'textile', 0.155, 150, TRUE),
(2, 1, 'PC-6535-195', 'PolyCotton Twill 65/35 195gsm', 'textile', 0.195, 150, TRUE),
(3, 1, 'PES-ANTI-170', 'Polyester Antimicrobial 170gsm', 'textile', 0.170, 160, TRUE),
(4, 1, 'COT-ORG-160', 'Organic Cotton Jersey 160gsm', 'textile', 0.160, 140, TRUE),
(5, 1, 'PES-BARR-120', 'Polyester Barrier Fabric 120gsm', 'textile', 0.120, 150, TRUE),
(6, 1, 'TRIM-SNAP-01', 'Stainless Steel Snaps', 'trim', NULL, NULL, TRUE),
(7, 1, 'TRIM-CORD-01', 'Polyester Drawcord', 'trim', NULL, NULL, TRUE);

-- ============================================
-- FIBERSAMMANSÄTTNING
-- ============================================

INSERT INTO factory_material_compositions (id, factory_material_id, fiber_type, percentage, fiber_source, material_trademark, is_recycled, recycled_percentage, recycled_source) VALUES
(1, 1, 'Polyester', 65.00, 'conventional', NULL, FALSE, NULL, NULL),
(2, 1, 'Cotton', 35.00, 'conventional', NULL, FALSE, NULL, NULL),
(3, 2, 'Polyester', 65.00, 'conventional', NULL, FALSE, NULL, NULL),
(4, 2, 'Cotton', 35.00, 'conventional', NULL, FALSE, NULL, NULL),
(5, 3, 'Polyester', 100.00, 'conventional', 'Sanitized', FALSE, NULL, NULL),
(6, 4, 'Cotton', 100.00, 'organic', NULL, FALSE, NULL, NULL),
(7, 5, 'Polyester', 100.00, 'conventional', NULL, FALSE, NULL, NULL),
(8, 6, 'Steel', 100.00, 'conventional', NULL, FALSE, NULL, NULL),
(9, 7, 'Polyester', 100.00, 'recycled', NULL, TRUE, 100.00, 'post_consumer');

-- ============================================
-- CERTIFIERINGAR
-- ============================================

INSERT INTO factory_material_certifications (id, factory_material_id, certification_type, certification_other, scope, certificate_number, valid_from, valid_until, document_url) VALUES
(1, 1, 'Oeko_Tex', 'Standard 100 Class I', 'material', 'OTS-2024-HC-001', '2024-01-01', '2025-12-31', '/certs/oeko-pc6535-155.pdf'),
(2, 2, 'Oeko_Tex', 'Standard 100 Class I', 'material', 'OTS-2024-HC-002', '2024-01-01', '2025-12-31', '/certs/oeko-pc6535-195.pdf'),
(3, 3, 'Oeko_Tex', 'Standard 100 Class I', 'material', 'OTS-2024-HC-003', '2024-01-01', '2025-12-31', '/certs/oeko-pes-anti.pdf'),
(4, 4, 'GOTS', NULL, 'material', 'GOTS-2024-12345', '2024-02-01', '2025-01-31', '/certs/gots-cot-org.pdf'),
(5, 4, 'Oeko_Tex', 'Standard 100 Class I', 'material', 'OTS-2024-HC-004', '2024-01-01', '2025-12-31', '/certs/oeko-cot-org.pdf'),
(6, 5, 'other', 'EN 13795 Standard Performance', 'material', 'EN13795-2024-001', '2024-03-01', '2026-02-28', '/certs/en13795-barrier.pdf');

-- ============================================
-- LEVERANTÖRSKEDJA
-- ============================================

INSERT INTO factory_material_supply_chain (id, factory_material_id, sequence, process_stage, supplier_name, country, facility_id) VALUES
(1, 1, 1, 'fiber', 'Reliance Industries', 'IN', 'GLN-8901234500001'),
(2, 1, 2, 'fiber', 'Vardhman Textiles', 'IN', 'GLN-8901234500002'),
(3, 1, 3, 'spinning', 'Vardhman Textiles', 'IN', 'GLN-8901234500002'),
(4, 1, 4, 'weaving_knitting', 'Textil Hospitalar Porto', 'PT', 'GLN-5609876543210'),
(5, 1, 5, 'dyeing', 'Textil Hospitalar Porto', 'PT', 'GLN-5609876543210'),
(6, 2, 1, 'fiber', 'Reliance Industries', 'IN', 'GLN-8901234500001'),
(7, 2, 2, 'fiber', 'Vardhman Textiles', 'IN', 'GLN-8901234500002'),
(8, 2, 3, 'spinning', 'Vardhman Textiles', 'IN', 'GLN-8901234500002'),
(9, 2, 4, 'weaving_knitting', 'Textil Hospitalar Porto', 'PT', 'GLN-5609876543210'),
(10, 2, 5, 'dyeing', 'Textil Hospitalar Porto', 'PT', 'GLN-5609876543210'),
(11, 3, 1, 'fiber', 'Toray Industries', 'JP', 'GLN-4901234500001'),
(12, 3, 2, 'spinning', 'Toray Industries', 'JP', 'GLN-4901234500001'),
(13, 3, 3, 'weaving_knitting', 'Textil Hospitalar Porto', 'PT', 'GLN-5609876543210'),
(14, 3, 4, 'finishing', 'Sanitized AG', 'CH', 'GLN-7601234500001'),
(15, 4, 1, 'fiber', 'Chetna Organic', 'IN', 'GLN-8901234500010'),
(16, 4, 2, 'spinning', 'Armstrong Spinning Mills', 'IN', 'GLN-8901234500011'),
(17, 4, 3, 'weaving_knitting', 'Textil Hospitalar Porto', 'PT', 'GLN-5609876543210'),
(18, 4, 4, 'dyeing', 'Textil Hospitalar Porto', 'PT', 'GLN-5609876543210');

-- ============================================
-- PRODUKTER
-- ============================================

INSERT INTO products (id, brand_id, gtin, product_name, description, photo_url, article_number, commodity_code_system, commodity_code_number, year_of_sale, season_of_sale, price_currency, msrp, category, product_group, line, garment_type, age_group, gender, market_segment, weight_kg, _is_active, data_carrier_type, data_carrier_material, data_carrier_location) VALUES
(1, 1, '7350012345001', 'Scrubs Tunika', 'Klassisk V-ringad scrubstopp for vardpersonal. Brostficka och sidfickor. Tal industriell tvatt 85C.', '/images/scrubs-tunika.jpg', 'VT-SCR-TOP-001', 'HS', '6211339000', 2025, 'SP', 'EUR', 34.95, 'clothing', 'Top', 'VardTex Pro', 'Tunic', 'Adult', 'unisex', 'mid-price', 0.185, TRUE, 'RFID', 'woven_label', 'side_seam'),
(2, 1, '7350012345002', 'Scrubs Byxa', 'Bekvam scrubsbyxa med resar och dragsko. Sidfickor och bakficka. Tal industriell tvatt 85C.', '/images/scrubs-byxa.jpg', 'VT-SCR-BTM-001', 'HS', '6203439000', 2025, 'SP', 'EUR', 32.95, 'clothing', 'Bottom', 'VardTex Pro', 'Pants', 'Adult', 'unisex', 'mid-price', 0.210, TRUE, 'RFID', 'woven_label', 'waistband'),
(3, 1, '7350012345003', 'Labbrock', 'Klassisk vit labbrock med knappning. Brostficka och sidfickor. Antimikrobiellt tyg.', '/images/labbrock.jpg', 'VT-LAB-001', 'HS', '6211329000', 2025, 'SP', 'EUR', 59.95, 'clothing', 'Top', 'VardTex Pro', 'Lab coat', 'Adult', 'unisex', 'mid-price', 0.320, TRUE, 'RFID', 'woven_label', 'neck_label'),
(4, 1, '7350012345004', 'Patientskjorta', 'Oppen patientskjorta med tryckknappsstangning. GOTS-certifierad ekologisk bomull.', '/images/patientskjorta.jpg', 'VT-PAT-001', 'HS', '6211429000', 2025, 'SP', 'EUR', 24.95, 'clothing', 'Top', 'VardTex Care', 'Patient gown', 'Adult', 'unisex', 'mid-price', 0.145, TRUE, 'QR', 'care_label', 'neck_label'),
(5, 1, '7350012345005', 'Operationsrock', 'Steril operationsrock med barriarskydd EN 13795. Knytning i ryggen.', '/images/operationsrock.jpg', 'VT-OP-001', 'HS', '6210109000', 2025, 'SP', 'EUR', 45.95, 'clothing', 'Top', 'VardTex Sterile', 'Surgical gown', 'Adult', 'unisex', 'premium', 0.095, TRUE, 'QR', 'hangtag', 'attached');

-- ============================================
-- PRODUKTVARIANTER
-- ============================================

INSERT INTO product_variants (id, product_id, sku, size, size_system, color_name, color_code, _is_active) VALUES
(1, 1, 'VT-SCR-TOP-001-CBL-XS', 'XS', 'EU', 'Ceil Blue', 'blue', TRUE),
(2, 1, 'VT-SCR-TOP-001-CBL-S', 'S', 'EU', 'Ceil Blue', 'blue', TRUE),
(3, 1, 'VT-SCR-TOP-001-CBL-M', 'M', 'EU', 'Ceil Blue', 'blue', TRUE),
(4, 1, 'VT-SCR-TOP-001-CBL-L', 'L', 'EU', 'Ceil Blue', 'blue', TRUE),
(5, 1, 'VT-SCR-TOP-001-CBL-XL', 'XL', 'EU', 'Ceil Blue', 'blue', TRUE),
(6, 1, 'VT-SCR-TOP-001-NVY-S', 'S', 'EU', 'Navy', 'navy', TRUE),
(7, 1, 'VT-SCR-TOP-001-NVY-M', 'M', 'EU', 'Navy', 'navy', TRUE),
(8, 1, 'VT-SCR-TOP-001-NVY-L', 'L', 'EU', 'Navy', 'navy', TRUE),
(9, 1, 'VT-SCR-TOP-001-WIN-M', 'M', 'EU', 'Wine', 'red', TRUE),
(10, 1, 'VT-SCR-TOP-001-WIN-L', 'L', 'EU', 'Wine', 'red', TRUE),
(11, 2, 'VT-SCR-BTM-001-CBL-XS', 'XS', 'EU', 'Ceil Blue', 'blue', TRUE),
(12, 2, 'VT-SCR-BTM-001-CBL-S', 'S', 'EU', 'Ceil Blue', 'blue', TRUE),
(13, 2, 'VT-SCR-BTM-001-CBL-M', 'M', 'EU', 'Ceil Blue', 'blue', TRUE),
(14, 2, 'VT-SCR-BTM-001-CBL-L', 'L', 'EU', 'Ceil Blue', 'blue', TRUE),
(15, 2, 'VT-SCR-BTM-001-NVY-M', 'M', 'EU', 'Navy', 'navy', TRUE),
(16, 2, 'VT-SCR-BTM-001-NVY-L', 'L', 'EU', 'Navy', 'navy', TRUE),
(17, 3, 'VT-LAB-001-WHT-S', 'S', 'EU', 'White', 'white', TRUE),
(18, 3, 'VT-LAB-001-WHT-M', 'M', 'EU', 'White', 'white', TRUE),
(19, 3, 'VT-LAB-001-WHT-L', 'L', 'EU', 'White', 'white', TRUE),
(20, 3, 'VT-LAB-001-WHT-XL', 'XL', 'EU', 'White', 'white', TRUE),
(21, 4, 'VT-PAT-001-WHT-S', 'S', 'EU', 'White', 'white', TRUE),
(22, 4, 'VT-PAT-001-WHT-M', 'M', 'EU', 'White', 'white', TRUE),
(23, 4, 'VT-PAT-001-WHT-L', 'L', 'EU', 'White', 'white', TRUE),
(24, 4, 'VT-PAT-001-LBL-M', 'M', 'EU', 'Light Blue', 'blue', TRUE),
(25, 5, 'VT-OP-001-GRN-M', 'M', 'EU', 'Surgical Green', 'green', TRUE),
(26, 5, 'VT-OP-001-GRN-L', 'L', 'EU', 'Surgical Green', 'green', TRUE),
(27, 5, 'VT-OP-001-GRN-XL', 'XL', 'EU', 'Surgical Green', 'green', TRUE);
