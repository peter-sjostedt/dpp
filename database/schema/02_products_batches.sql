-- ============================================
-- DPP Platform - Del 2: Products + Batches
-- ============================================

USE petersjo_dpp;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,
    gtin VARCHAR(14),
    product_name VARCHAR(255) NOT NULL,
    description TEXT,
    photo_url VARCHAR(500),
    article_number VARCHAR(100),
    commodity_code_system VARCHAR(50),
    commodity_code_number VARCHAR(50),
    year_of_sale YEAR,
    season_of_sale ENUM('SP', 'SU', 'AW', 'FW'),
    price_currency CHAR(3) DEFAULT 'EUR',
    msrp DECIMAL(10,2),
    resale_price DECIMAL(10,2),
    category ENUM('clothing', 'accessories', 'footwear', 'other'),
    product_group VARCHAR(50),
    line VARCHAR(100),
    garment_type VARCHAR(100),
    age_group VARCHAR(50),
    gender ENUM('male', 'female', 'unisex'),
    market_segment ENUM('mass-market', 'mid-price', 'premium', 'luxury'),
    water_properties ENUM('none', 'waterproof', 'water_repellent', 'water_resistant'),
    weight_kg DECIMAL(8,4),
    _is_active BOOLEAN DEFAULT TRUE,
    data_carrier_type ENUM('RFID', 'NFC', 'QR', 'barcode'),
    data_carrier_material VARCHAR(100),
    data_carrier_location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id),
    UNIQUE KEY unique_product (brand_id, gtin),
    INDEX idx_products_brand (brand_id),
    INDEX idx_products_gtin (gtin),
    INDEX idx_products_active (_is_active)
);

CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    sku VARCHAR(100) NOT NULL,
    size VARCHAR(20),
    size_system VARCHAR(10),
    color_name VARCHAR(100),
    color_code ENUM('black', 'white', 'grey', 'navy', 'blue', 'red', 'green', 'yellow', 'orange', 'pink', 'purple', 'brown', 'beige', 'multicolour', 'print', 'other'),
    _is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    UNIQUE KEY unique_variant (product_id, sku),
    INDEX idx_variants_product (product_id),
    INDEX idx_variants_sku (sku)
);

CREATE TABLE batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_variant_id INT NOT NULL,
    batch_number VARCHAR(100) NOT NULL,
    po_number VARCHAR(100),
    production_date DATE,
    quantity INT,
    _status ENUM('planned', 'in_production', 'completed', 'shipped') DEFAULT 'planned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id),
    UNIQUE KEY unique_batch (product_variant_id, batch_number),
    INDEX idx_batches_variant (product_variant_id)
);

CREATE TABLE batch_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    factory_material_id INT NOT NULL,
    component_type ENUM('body_fabric', 'lining', 'trim', 'padding', 'other') NOT NULL,
    quantity_meters DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    FOREIGN KEY (factory_material_id) REFERENCES factory_materials(id) ON DELETE RESTRICT,
    INDEX idx_batch_materials_batch (batch_id),
    INDEX idx_batch_materials_material (factory_material_id)
);

CREATE TABLE batch_suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    supplier_id INT NOT NULL,
    production_stage ENUM('confection', 'dyeing_printing', 'weaving_knitting', 'spinning', 'other') NOT NULL,
    country_of_origin CHAR(2),
    FOREIGN KEY (batch_id) REFERENCES batches(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    product_variant_id INT NOT NULL,
    tid VARCHAR(50),
    sgtin VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(id),
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id),
    INDEX idx_items_batch (batch_id),
    INDEX idx_items_variant (product_variant_id),
    INDEX idx_items_tid (tid),
    INDEX idx_items_sgtin (sgtin)
);

-- ============================================
-- LEGACY TABLES (för bakåtkompatibilitet)
-- ============================================

CREATE TABLE product_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    component_type ENUM('body_fabric', 'lining', 'trim', 'padding', 'other') NOT NULL,
    component_weight DECIMAL(8,4),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE component_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_id INT NOT NULL,
    material_type ENUM('textile', 'leather', 'rubber', 'metal', 'plastic', 'other') NOT NULL,
    fiber_type VARCHAR(100),
    percentage DECIMAL(5,2),
    fiber_source VARCHAR(100),
    material_trademarks VARCHAR(255),
    is_recycled BOOLEAN DEFAULT FALSE,
    recycled_percentage DECIMAL(5,2),
    recycled_input_source ENUM('pre_consumer', 'post_consumer', 'post_consumer_packaging', 'other'),
    FOREIGN KEY (component_id) REFERENCES product_components(id)
);
