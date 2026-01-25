-- ============================================
-- DPP Platform - Del 5: Testdata (fortsättning)
-- ============================================

USE petersjo_dpp;

-- ============================================
-- PRODUKTIONSBATCHER
-- ============================================

INSERT INTO batches (id, product_variant_id, batch_number, po_number, production_date, quantity, _status) VALUES
(1, 2, 'BATCH-2025-VGR-001', 'PO-VGR-2025-0042', '2025-01-08', 500, 'shipped'),
(2, 3, 'BATCH-2025-VGR-002', 'PO-VGR-2025-0042', '2025-01-08', 800, 'shipped'),
(3, 4, 'BATCH-2025-VGR-003', 'PO-VGR-2025-0042', '2025-01-08', 600, 'shipped'),
(4, 5, 'BATCH-2025-VGR-004', 'PO-VGR-2025-0042', '2025-01-08', 300, 'shipped'),
(5, 12, 'BATCH-2025-VGR-005', 'PO-VGR-2025-0042', '2025-01-10', 500, 'shipped'),
(6, 13, 'BATCH-2025-VGR-006', 'PO-VGR-2025-0042', '2025-01-10', 800, 'completed'),
(7, 14, 'BATCH-2025-VGR-007', 'PO-VGR-2025-0042', '2025-01-10', 600, 'completed'),
(8, 18, 'BATCH-2025-KS-001', 'PO-KS-2025-0015', '2025-01-15', 200, 'in_production'),
(9, 19, 'BATCH-2025-KS-002', 'PO-KS-2025-0015', '2025-01-15', 150, 'in_production'),
(10, 22, 'BATCH-2025-SU-001', 'PO-SU-2025-0033', '2025-01-20', 1000, 'planned'),
(11, 26, 'BATCH-2025-OP-001', 'PO-VGR-2025-0055', '2025-01-25', 300, 'planned');

-- ============================================
-- BATCH MATERIALS
-- ============================================

INSERT INTO batch_materials (id, batch_id, factory_material_id, component_type, quantity_meters) VALUES
(1, 1, 1, 'body_fabric', 400.00),
(2, 2, 1, 'body_fabric', 640.00),
(3, 3, 1, 'body_fabric', 480.00),
(4, 4, 1, 'body_fabric', 240.00),
(5, 5, 2, 'body_fabric', 550.00),
(6, 6, 2, 'body_fabric', 880.00),
(7, 7, 2, 'body_fabric', 660.00),
(8, 5, 7, 'trim', 250.00),
(9, 6, 7, 'trim', 400.00),
(10, 7, 7, 'trim', 300.00),
(11, 8, 3, 'body_fabric', 480.00),
(12, 9, 3, 'body_fabric', 360.00),
(13, 10, 4, 'body_fabric', 700.00),
(14, 10, 6, 'trim', 50.00),
(15, 11, 5, 'body_fabric', 360.00);

-- ============================================
-- BATCH SUPPLIERS
-- ============================================

INSERT INTO batch_suppliers (id, batch_id, supplier_id, production_stage, country_of_origin) VALUES
(1, 1, 1, 'confection', 'PT'),
(2, 2, 1, 'confection', 'PT'),
(3, 3, 1, 'confection', 'PT'),
(4, 4, 1, 'confection', 'PT'),
(5, 5, 1, 'confection', 'PT'),
(6, 6, 1, 'confection', 'PT'),
(7, 7, 1, 'confection', 'PT'),
(8, 8, 1, 'confection', 'PT'),
(9, 9, 1, 'confection', 'PT'),
(10, 10, 1, 'confection', 'PT'),
(11, 11, 1, 'confection', 'PT');

-- ============================================
-- ENSKILDA ARTIKLAR (för Fas 2 / RFID)
-- ============================================
-- Items skapas i 06_testdata_items.sql med product_variant_id och rfid_tag_id

-- ============================================
-- SKÖTSELINFORMATION
-- ============================================

INSERT INTO care_information (id, product_id, care_image_url, care_text, safety_information) VALUES
(1, 1, '/images/care/industrial-wash-85.svg', 'Industritvatt 85C. Torktumling tillaten. Kan manglas. Kemtvatt tillaten.', NULL),
(2, 2, '/images/care/industrial-wash-85.svg', 'Industritvatt 85C. Torktumling tillaten. Kan manglas. Kemtvatt tillaten.', NULL),
(3, 3, '/images/care/industrial-wash-75.svg', 'Industritvatt 75C. Torktumling pa medelvarme. Stryk pa medelvarme.', 'Ej avsedd for anvandning i sterila miljoer.'),
(4, 4, '/images/care/gentle-wash-60.svg', 'Tvatta 60C. Skontorkning rekommenderas. Stryk pa lag varme. Ej kemtvatt.', 'Endast for patientbruk. Byt vid synligt slitage.'),
(5, 5, '/images/care/industrial-wash-85.svg', 'Industritvatt 85C. Autoklavering mojlig (134C). Torktumling tillaten.', 'Medicinteknisk produkt Klass I. Kontrollera integritet fore varje anvandning.');

-- ============================================
-- COMPLIANCE INFORMATION
-- ============================================

INSERT INTO compliance_information (id, product_id, contains_svhc, svhc_details, scan4chem_link, sheds_microfibers, traceability_provider) VALUES
(1, 1, FALSE, NULL, 'https://scan4chem.eu/product/7350012345001', TRUE, 'TrusTrace'),
(2, 2, FALSE, NULL, 'https://scan4chem.eu/product/7350012345002', TRUE, 'TrusTrace'),
(3, 3, FALSE, NULL, 'https://scan4chem.eu/product/7350012345003', TRUE, 'TrusTrace'),
(4, 4, FALSE, NULL, 'https://scan4chem.eu/product/7350012345004', FALSE, 'TrusTrace'),
(5, 5, FALSE, NULL, 'https://scan4chem.eu/product/7350012345005', TRUE, 'TrusTrace');

-- ============================================
-- PRODUKTCERTIFIERINGAR
-- ============================================

INSERT INTO certifications (id, product_id, certification_name, certification_other, validation_document_url, valid_until) VALUES
(1, 1, 'Oeko_Tex', 'Standard 100 Class I', '/certs/product/scrubs-tunika-oeko.pdf', '2025-12-31'),
(2, 2, 'Oeko_Tex', 'Standard 100 Class I', '/certs/product/scrubs-byxa-oeko.pdf', '2025-12-31'),
(3, 3, 'Oeko_Tex', 'Standard 100 Class I', '/certs/product/labbrock-oeko.pdf', '2025-12-31'),
(4, 4, 'GOTS', NULL, '/certs/product/patientskjorta-gots.pdf', '2025-12-31'),
(5, 4, 'Oeko_Tex', 'Standard 100 Class I', '/certs/product/patientskjorta-oeko.pdf', '2025-12-31'),
(6, 5, 'other', 'EN 13795 Standard Performance', '/certs/product/oprock-en13795.pdf', '2026-02-28'),
(7, 5, 'other', 'ISO 22610 Bacterial Barrier', '/certs/product/oprock-iso22610.pdf', '2026-02-28');

-- ============================================
-- KEMIKALIECOMPLIANCE
-- ============================================

INSERT INTO chemical_compliance (id, product_id, compliance_standard, validation_document_url) VALUES
(1, 1, 'REACH', '/certs/chem/scrubs-tunika-reach.pdf'),
(2, 2, 'REACH', '/certs/chem/scrubs-byxa-reach.pdf'),
(3, 3, 'REACH', '/certs/chem/labbrock-reach.pdf'),
(4, 3, 'ZDHC', '/certs/chem/labbrock-zdhc.pdf'),
(5, 4, 'REACH', '/certs/chem/patientskjorta-reach.pdf'),
(6, 5, 'REACH', '/certs/chem/oprock-reach.pdf');

-- ============================================
-- CIRKULÄRITET
-- ============================================

INSERT INTO circularity_information (id, product_id, recyclability, take_back_instructions, recycling_instructions, disassembly_instructions_user, circular_design_strategy, repair_instructions) VALUES
(1, 1, 'Mekanisk atervinning mojlig. Polyester/bomull-blend kraver separering.', 'Returnera uttjanta plagg till tvatteriet for insamling.', 'Textil-till-textil atervinning via Renewcell eller mekanisk fiberatervinning.', 'Klipp bort eventuella plastdetaljer fore atervinning.', 'physical_durability', 'Lagning av smahall och sommsprattning utfors av tvatteripartner.'),
(2, 2, 'Mekanisk atervinning mojlig. Polyester/bomull-blend kraver separering.', 'Returnera uttjanta plagg till tvatteriet for insamling.', 'Textil-till-textil atervinning via Renewcell eller mekanisk fiberatervinning.', 'Klipp bort resarband och dragsko fore atervinning.', 'physical_durability', 'Lagning av smahall och resarbyte utfors av tvatteripartner.'),
(3, 3, 'Atervinning av polyester mojlig. Antimikrobiell behandling paverkar ej atervinning.', 'Returnera till insamlingspunkt pa sjukhuset.', 'Mekanisk polyesteratervinning. Kan granuleras till ny fiber.', 'Ta bort knappar fore atervinning om mojligt.', 'longevity', 'Professionell lagning rekommenderas for att bevara antimikrobiell funktion.'),
(4, 4, 'Fullt atervinningsbar ekologisk bomull. Biologiskt nedbrytbar.', 'Returnera till vardavdelning for textilatervinning.', 'Mekanisk bomullsatervinning eller industriell kompostering.', 'Ta bort tryckknapparna (metall) fore atervinning.', 'material_cyclability', 'Enkel lagning kan utforas. Byt vid synligt slitage for patientkomfort.'),
(5, 5, 'Polyesterbarriar kan atervinnas efter sterilisering.', 'Kassera enligt sjukhusets rutiner for medicintekniska produkter.', 'Energiatervinning eller mekanisk atervinning beroende pa anvandningshistorik.', 'Engangsanvandning - folj sjukhusets avfallsrutiner.', 'disassembly', 'Ej reparerbar - kassera vid synlig skada for att garantera barriarfunktion.');

-- ============================================
-- HÅLLBARHET
-- ============================================

INSERT INTO sustainability_information (id, product_id, brand_statement, brand_statement_link, environmental_footprint) VALUES
(1, 1, 'Vara scrubs ar designade for lang livslangd och tal minst 100 industriella tvattcykler.', 'https://vardtex.se/hallbarhet', '{"co2_kg": 8.5, "water_liters": 2400, "energy_mj": 95, "wash_cycles": 100}'),
(2, 2, 'Vara scrubsbyxor kombinerar komfort med hallbarhet.', 'https://vardtex.se/hallbarhet', '{"co2_kg": 9.2, "water_liters": 2800, "energy_mj": 105, "wash_cycles": 100}'),
(3, 3, 'Labbrocken med Sanitized antimikrobiell behandling ger langvarigt skydd.', 'https://vardtex.se/hallbarhet', '{"co2_kg": 12.5, "water_liters": 3200, "energy_mj": 140, "wash_cycles": 75}'),
(4, 4, 'Patientskjortan i GOTS-certifierad ekologisk bomull ar mjuk, trygg och hallbar.', 'https://vardtex.se/hallbarhet', '{"co2_kg": 4.2, "water_liters": 800, "energy_mj": 45, "wash_cycles": 50}'),
(5, 5, 'Var operationsrock uppfyller EN 13795 och ger palitligt barriarskydd.', 'https://vardtex.se/hallbarhet', '{"co2_kg": 6.8, "water_liters": 1500, "energy_mj": 72, "wash_cycles": 25}');

-- ============================================
-- VALIDATION STATUS
-- ============================================

INSERT INTO validation_status (id, product_id, brand_info_complete, supply_chain_complete, product_info_complete, material_info_complete, care_info_complete, compliance_info_complete, circularity_info_complete, sustainability_info_complete, overall_status) VALUES
(1, 1, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, 'ready_for_export'),
(2, 2, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, 'ready_for_export'),
(3, 3, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, 'ready_for_export'),
(4, 4, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, 'validated'),
(5, 5, TRUE, TRUE, TRUE, FALSE, TRUE, TRUE, TRUE, TRUE, 'pending_review');

-- ============================================
-- VERIFIERING
-- ============================================

SELECT 'Schema och testdata klart!' as status;

SELECT
  (SELECT COUNT(*) FROM companies) as companies,
  (SELECT COUNT(*) FROM products) as products,
  (SELECT COUNT(*) FROM product_variants) as variants,
  (SELECT COUNT(*) FROM factory_materials) as materials,
  (SELECT COUNT(*) FROM batches) as batches,
  (SELECT COUNT(*) FROM items) as items;
