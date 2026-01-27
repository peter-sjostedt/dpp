-- ============================================
-- DPP Platform - Del 4: Testdata
-- Multi-tenant version (brands/suppliers with API keys)
-- ============================================

USE petersjo_dpp;

-- ============================================
-- BRANDS (with API keys, LEI, GS1)
-- ============================================

INSERT INTO brands (id, brand_name, logo_url, sub_brand, parent_company, trader_name, trader_address, lei, gs1_company_prefix, api_key, _is_active) VALUES
(1, 'VårdTex', '/images/vardtex-logo.png', 'VårdTex Pro', 'VårdTex AB', 'VårdTex AB', 'Medicinaregatan 8, 413 90 Göteborg, Sweden', '549300VARDTEX00001SE', '7350012345', 'brand_vardtex_test_key_000', TRUE);

-- ============================================
-- SUPPLIERS (with API keys, LEI, GS1)
-- ============================================

INSERT INTO suppliers (id, supplier_name, supplier_location, facility_registry, facility_identifier, operator_registry, operator_identifier, lei, gs1_company_prefix, api_key, _is_active) VALUES
(1, 'Têxtil Hospitalar Porto', 'Zona Industrial de Maia, 4470-122 Porto, Portugal', 'GLN', '5609876543210', 'GLN', '5609876543210', '549300PORTOTEXTIL01', '5609876543', 'supplier_porto_test_key_000', TRUE);

-- ============================================
-- BRAND-SUPPLIER RELATIONSHIPS
-- ============================================

INSERT INTO brand_suppliers (brand_id, supplier_id, _is_active) VALUES
(1, 1, TRUE);

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
