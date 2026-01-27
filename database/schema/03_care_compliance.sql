-- ============================================
-- DPP Care, Compliance, Circularity, Sustainability, Export
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS dpp_exports;
DROP TABLE IF EXISTS sustainability_info;
DROP TABLE IF EXISTS circularity_info;
DROP TABLE IF EXISTS product_certifications;
DROP TABLE IF EXISTS compliance_info;
DROP TABLE IF EXISTS care_information;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- CARE INFORMATION
-- ============================================

CREATE TABLE care_information (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL UNIQUE,

    care_image_url VARCHAR(500) COMMENT '400,00',
    care_text TEXT COMMENT '401,00',
    safety_information TEXT COMMENT '402,00',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ============================================
-- COMPLIANCE INFORMATION
-- ============================================

CREATE TABLE compliance_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,

    -- Harmful substances (500)
    harmful_substances VARCHAR(10) COMMENT '500,00 - Yes/No',
    harmful_substances_info TEXT COMMENT '500,10',

    -- Certifications (501)
    certifications VARCHAR(255) COMMENT '501,00 - Fair Trade, GOTS, GRS',
    certifications_validation TEXT COMMENT '501,10',

    -- Chemical compliance (502)
    chemical_compliance_standard VARCHAR(100) COMMENT '502,00 - AFFIRM, REACH, ZDHC',
    chemical_compliance_validation TEXT COMMENT '502,10',
    chemical_compliance_link VARCHAR(500) COMMENT '502,20',

    -- Other (503-504)
    microfibers VARCHAR(10) COMMENT '503,00 - Yes/No',
    traceability_provider VARCHAR(255) COMMENT '504,00',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_compliance_product (product_id)
);

-- ============================================
-- PRODUCT CERTIFICATIONS
-- ============================================

CREATE TABLE product_certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,

    certification_name VARCHAR(100) NOT NULL COMMENT '501,00 - GOTS, GRS, Oeko-Tex, Fair Trade',
    certification_other VARCHAR(255) COMMENT 'Custom certification name if Other selected',
    validation_document_url VARCHAR(500) COMMENT 'Link to certificate/validation document',
    valid_until DATE COMMENT 'Certificate expiration date',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_certifications_product (product_id)
);

-- ============================================
-- CIRCULARITY INFORMATION
-- ============================================

CREATE TABLE circularity_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL UNIQUE,

    performance TEXT COMMENT '600,00 - TBC',
    recyclability TEXT COMMENT '601,00',
    take_back_instructions TEXT COMMENT '602,00',
    recycling_instructions TEXT COMMENT '603,00',
    disassembly_instructions_sorters TEXT COMMENT '604,00',
    disassembly_instructions_user TEXT COMMENT '605,00',
    circular_design_strategy VARCHAR(100) COMMENT '606,00',
    circular_design_description TEXT COMMENT '606,10',
    repair_instructions TEXT COMMENT '607,00',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ============================================
-- SUSTAINABILITY INFORMATION
-- ============================================

CREATE TABLE sustainability_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL UNIQUE,

    brand_statement TEXT COMMENT '650,00',
    statement_link VARCHAR(500) COMMENT '650,10',
    environmental_footprint TEXT COMMENT '651,00 - TBC',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ============================================
-- DPP EXPORTS
-- ============================================

CREATE TABLE dpp_exports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    batch_id INT,
    item_id INT,

    export_type VARCHAR(20) NOT NULL COMMENT 'product, batch, item',
    export_format VARCHAR(20) DEFAULT 'json',
    export_data JSON,
    exported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    exported_to VARCHAR(255) COMMENT 'DPP host name',

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE SET NULL,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL,
    INDEX idx_exports_product (product_id)
);
