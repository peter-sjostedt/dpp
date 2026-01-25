-- ============================================
-- DPP Platform - Del 3: Care + Compliance + Export
-- ============================================

USE petersjo_dpp;

-- ============================================
-- SKÖTSEL & SÄKERHET
-- ============================================

CREATE TABLE care_information (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL UNIQUE,
    care_image_url VARCHAR(500),
    care_text TEXT,
    safety_information TEXT,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ============================================
-- COMPLIANCE & CERTIFIERINGAR
-- ============================================

CREATE TABLE compliance_information (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL UNIQUE,
    contains_svhc BOOLEAN,
    svhc_details TEXT,
    scan4chem_link VARCHAR(500),
    sheds_microfibers BOOLEAN,
    traceability_provider VARCHAR(255),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    certification_name ENUM('Fair_Trade', 'GOTS', 'GRS', 'RCS', 'RWS', 'Oeko_Tex', 'BSCI', 'other') NOT NULL,
    certification_other VARCHAR(100),
    validation_document_url VARCHAR(500),
    valid_until DATE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE chemical_compliance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    compliance_standard ENUM('AFFIRM', 'C2C', 'REACH', 'ZDHC', 'other') NOT NULL,
    validation_document_url VARCHAR(500),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ============================================
-- CIRKULARITET & HÅLLBARHET
-- ============================================

CREATE TABLE circularity_information (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL UNIQUE,
    performance TEXT,
    recyclability TEXT,
    take_back_instructions TEXT,
    recycling_instructions TEXT,
    disassembly_instructions_sorters TEXT,
    disassembly_instructions_user TEXT,
    circular_design_strategy ENUM('material_cyclability', 'mono_cycle', 'disassembly', 'longevity', 'physical_durability'),
    circular_design_strategy_description TEXT,
    repair_instructions TEXT,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE sustainability_information (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL UNIQUE,
    brand_statement TEXT,
    brand_statement_link VARCHAR(500),
    environmental_footprint JSON,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ============================================
-- DPP EXPORT & VALIDERING
-- ============================================

CREATE TABLE dpp_exports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    batch_id INT,
    item_id INT,
    version INT NOT NULL DEFAULT 1,
    granularity_level ENUM('model', 'batch', 'item') NOT NULL,
    status ENUM('draft', 'validated', 'exported', 'published') DEFAULT 'draft',
    export_format VARCHAR(50),
    export_destination VARCHAR(255),
    exported_at TIMESTAMP NULL,
    published_at TIMESTAMP NULL,
    data_snapshot JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (batch_id) REFERENCES batches(id),
    FOREIGN KEY (item_id) REFERENCES items(id)
);

CREATE TABLE validation_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    brand_info_complete BOOLEAN DEFAULT FALSE,
    supply_chain_complete BOOLEAN DEFAULT FALSE,
    product_info_complete BOOLEAN DEFAULT FALSE,
    material_info_complete BOOLEAN DEFAULT FALSE,
    care_info_complete BOOLEAN DEFAULT FALSE,
    compliance_info_complete BOOLEAN DEFAULT FALSE,
    circularity_info_complete BOOLEAN DEFAULT FALSE,
    sustainability_info_complete BOOLEAN DEFAULT FALSE,
    overall_status ENUM('incomplete', 'pending_review', 'validated', 'ready_for_export') DEFAULT 'incomplete',
    last_validated_at TIMESTAMP NULL,
    FOREIGN KEY (product_id) REFERENCES products(id)
);
