-- ============================================
-- DPP Products, Variants, Purchase Orders, Batches, Items
-- Factory Materials with compositions
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS batch_materials;
DROP TABLE IF EXISTS batches;
DROP TABLE IF EXISTS purchase_orders;
DROP TABLE IF EXISTS product_variants;
DROP TABLE IF EXISTS product_components;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS factory_material_certifications;
DROP TABLE IF EXISTS factory_material_compositions;
DROP TABLE IF EXISTS factory_material_supply_chain;
DROP TABLE IF EXISTS factory_materials;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- FACTORY MATERIALS
-- ============================================

CREATE TABLE factory_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    material_name VARCHAR(255) NOT NULL,
    material_type VARCHAR(50) COMMENT 'Textile, Leather, Rubber',
    description TEXT,
    _is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    INDEX idx_factory_materials_supplier (supplier_id)
);

CREATE TABLE factory_material_compositions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factory_material_id INT NOT NULL,
    content_name VARCHAR(50) COMMENT '350,20 - Cotton, Polyester',
    content_value DECIMAL(5,2) COMMENT '350,21 - Percentage',
    content_source VARCHAR(50) COMMENT '350,22 - Fiber source',
    recycled BOOLEAN COMMENT '351,00',
    recycled_percentage DECIMAL(5,2) COMMENT '351,10',
    recycled_input_source VARCHAR(50) COMMENT '351,20 - Post-consumer etc',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (factory_material_id) REFERENCES factory_materials(id) ON DELETE CASCADE,
    INDEX idx_compositions_material (factory_material_id)
);

CREATE TABLE factory_material_certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factory_material_id INT NOT NULL,
    certification VARCHAR(100) COMMENT '501,00 - GOTS, GRS, Oeko-Tex',
    certification_id VARCHAR(100),
    valid_until DATE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (factory_material_id) REFERENCES factory_materials(id) ON DELETE CASCADE,
    INDEX idx_certs_material (factory_material_id)
);

CREATE TABLE factory_material_supply_chain (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factory_material_id INT NOT NULL,
    sequence INT NOT NULL DEFAULT 0,
    process_step VARCHAR(100) COMMENT 'spinning, weaving, dyeing, finishing',
    country VARCHAR(2) COMMENT 'ISO country code',
    facility_name VARCHAR(255),
    facility_identifier VARCHAR(100),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (factory_material_id) REFERENCES factory_materials(id) ON DELETE CASCADE,
    INDEX idx_supply_chain_material (factory_material_id)
);

-- ============================================
-- PRODUCTS
-- ============================================

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,

    -- Product ID (300-serien)
    gtin_type VARCHAR(50) COMMENT '300,00 - GTIN, SKU, Style Number',
    gtin VARCHAR(100) COMMENT '300,10 - GTIN värde',
    product_name VARCHAR(255) NOT NULL COMMENT '301,00',
    description TEXT COMMENT '302,00',
    photo_url VARCHAR(500) COMMENT '303,00',
    article_number VARCHAR(100) COMMENT '304,00 - Style + color',
    commodity_code_system VARCHAR(50) COMMENT '306,00',
    commodity_code_number VARCHAR(20) COMMENT '306,10 - HS code',
    year_of_sale INT COMMENT '307,00',
    season_of_sale VARCHAR(10) COMMENT '307,10 - SP/SU',
    price_currency VARCHAR(3) COMMENT '308,00',
    msrp DECIMAL(10,2) COMMENT '308,10',
    resale_price DECIMAL(10,2) COMMENT '308,20',

    -- Kategorisering (312-317)
    category VARCHAR(50) COMMENT '312,00 - clothing/accessories/footwear',
    product_group VARCHAR(50) COMMENT '312,10 - Top/Bottom etc',
    type_line_concept VARCHAR(50) COMMENT '313,00 - Active Wear, Sleep Wear',
    type_item VARCHAR(50) COMMENT '314,00 - Jacket, Pants, Blouse',
    age_group VARCHAR(50) COMMENT '315,00',
    gender VARCHAR(20) COMMENT '316,00',
    market_segment VARCHAR(50) COMMENT '317,00 - mass-market/mid-price/premium/luxury',

    -- Egenskaper
    water_properties VARCHAR(100) COMMENT '318,00',
    net_weight DECIMAL(10,4) COMMENT '319,00 - kg',
    weight_unit VARCHAR(10) DEFAULT 'kg' COMMENT '319,10',

    -- Digital Identifier (370-serien)
    data_carrier_type VARCHAR(50) COMMENT '370,00 - RFID, NFC, QR',
    data_carrier_material VARCHAR(50) COMMENT '370,10',
    data_carrier_location VARCHAR(100) COMMENT '370,20',

    _is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
    INDEX idx_products_brand (brand_id)
);

-- ============================================
-- PRODUCT COMPONENTS (Material composition)
-- ============================================

CREATE TABLE product_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,

    -- Material Information (350-serien)
    component VARCHAR(50) COMMENT '350,00 - Body fabric, trim, lining',
    material VARCHAR(50) COMMENT '350,10 - Textile, Leather, Rubber',
    content_name VARCHAR(50) COMMENT '350,20 - Cotton, Polyester',
    content_value DECIMAL(5,2) COMMENT '350,21 - Percentage',
    content_source VARCHAR(50) COMMENT '350,22 - Fiber source',
    material_trademarks VARCHAR(255) COMMENT '350,23 - Circulose, Tencel',
    content_name_other VARCHAR(100) COMMENT '350,30',
    trim_type VARCHAR(50) COMMENT '350,40 - Zipper, button',
    component_weight DECIMAL(10,4) COMMENT '350,50',

    -- Recycled (351)
    recycled BOOLEAN COMMENT '351,00',
    recycled_percentage DECIMAL(5,2) COMMENT '351,10',
    recycled_input_source VARCHAR(50) COMMENT '351,20 - Post-consumer etc',

    -- Leather (352)
    leather_species VARCHAR(50) COMMENT '352,00 - Bovine, Fish, Goat',
    leather_grade VARCHAR(50) COMMENT '352,10 - Full Grain etc',
    leather_species_other VARCHAR(100) COMMENT '352,11 - Om ej i lista',
    leather_pattern VARCHAR(50) COMMENT '352,20 - Embossing etc',
    leather_thickness DECIMAL(5,2) COMMENT '352,30 - Tjocklek i mm',
    leather_max DECIMAL(5,2) COMMENT '352,40 - Max tjocklek',
    leather_min DECIMAL(5,2) COMMENT '352,50 - Min tjocklek',

    -- Thread, ink, dye, finishes (353-357)
    sewing_thread_content VARCHAR(50) COMMENT '353,00',
    print_ink_type VARCHAR(50) COMMENT '354,00',
    dye_class VARCHAR(50) COMMENT '355,00',
    dye_class_standard VARCHAR(50) COMMENT '355,10',
    finishes VARCHAR(255) COMMENT '356,00 - Anti-microbial etc',
    pattern VARCHAR(50) COMMENT '357,00 - Heathered, Chevron',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_components_product (product_id)
);

-- ============================================
-- PRODUCT VARIANTS (Size/Color combinations)
-- ============================================

CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,

    item_number VARCHAR(100) COMMENT '304,10 - Style + color + size',
    size VARCHAR(50) COMMENT '309,00',
    size_country_code VARCHAR(2) COMMENT '309,10',
    color_brand VARCHAR(100) COMMENT '310,00 - Brand color name',
    color_general VARCHAR(50) COMMENT '311,00 - Standard color',
    gtin VARCHAR(14) COMMENT 'GTIN for this variant',

    _is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_variants_product (product_id),
    INDEX idx_variants_gtin (gtin)
);

-- ============================================
-- PURCHASE ORDERS (Brand beställer från Supplier)
-- ============================================

CREATE TABLE purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,
    supplier_id INT NOT NULL,
    product_id INT NOT NULL,

    po_number VARCHAR(100) NOT NULL COMMENT '305,00 - Inköpsordernummer',
    quantity INT COMMENT 'Beställd kvantitet',
    requested_delivery_date DATE,
    _status VARCHAR(50) DEFAULT 'draft' COMMENT 'draft/sent/accepted/fulfilled/cancelled',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (brand_id) REFERENCES brands(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_po_brand (brand_id),
    INDEX idx_po_supplier (supplier_id),
    INDEX idx_po_product (product_id)
);

-- ============================================
-- BATCHES (Produktionsomgångar, skapas av Supplier)
-- En batch = en produktionsomgång med specifika material-inputs.
-- Flera batchar kan tillhöra samma PO om fabriken
-- byter tygrulle/leverans under produktion.
-- ============================================

CREATE TABLE batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT NOT NULL,

    batch_number VARCHAR(100) NOT NULL,
    production_date DATE,
    quantity INT,
    _status VARCHAR(50) DEFAULT 'in_production' COMMENT 'in_production/completed',

    -- Facility override (NULL = använd supplier defaults från PO → supplier)
    facility_name VARCHAR(255) COMMENT 'Override: 200,00 - Supplier Name',
    facility_location TEXT COMMENT 'Override: 201,00 - Supplier Location',
    facility_registry VARCHAR(50) COMMENT 'Override: 202,00 - Facility Registry',
    facility_identifier VARCHAR(100) COMMENT 'Override: 202,10 - Facility Identifier',
    country_of_origin_confection VARCHAR(2) COMMENT 'Override: 204,00',
    country_of_origin_dyeing VARCHAR(2) COMMENT 'Override: 205,00',
    country_of_origin_weaving VARCHAR(2) COMMENT 'Override: 206,00',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
    INDEX idx_batches_po (purchase_order_id)
);

-- ============================================
-- BATCH MATERIALS (Links batches to factory materials)
-- Skapas av Supplier - vilka tygleveranser som faktiskt användes
-- ============================================

CREATE TABLE batch_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    factory_material_id INT NOT NULL,
    component VARCHAR(50) COMMENT 'Body fabric, trim, lining',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    FOREIGN KEY (factory_material_id) REFERENCES factory_materials(id),
    INDEX idx_batch_materials_batch (batch_id)
);

-- ============================================
-- ITEMS (Individual serialized products)
-- Skapas av Supplier vid produktion (RFID/SGTIN)
-- ============================================

CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    product_variant_id INT,

    -- Unique identifiers (300,20)
    unique_product_id VARCHAR(100) COMMENT '300,20',
    tid VARCHAR(100) COMMENT 'RFID Tag ID',
    sgtin VARCHAR(100) COMMENT 'Serialized GTIN',
    serial_number VARCHAR(100),

    _status VARCHAR(50) DEFAULT 'produced' COMMENT 'produced/shipped/sold/returned/recycled',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id),
    INDEX idx_items_batch (batch_id),
    INDEX idx_items_sgtin (sgtin),
    INDEX idx_items_tid (tid)
);