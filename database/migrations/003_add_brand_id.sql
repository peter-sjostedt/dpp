-- ============================================
-- Migration 003: Add brand_id to batches and items
-- ============================================

USE petersjo_dpp;

-- ============================================
-- 1. ADD brand_id TO BATCHES
-- ============================================

ALTER TABLE batches
ADD COLUMN brand_id INT NULL AFTER id;

-- Populate brand_id from product chain
UPDATE batches b
JOIN product_variants pv ON b.product_variant_id = pv.id
JOIN products p ON pv.product_id = p.id
SET b.brand_id = p.brand_id;

-- Make NOT NULL and add FK (only if there are records)
-- If batches table is empty or all have brand_id, this will work
ALTER TABLE batches
MODIFY COLUMN brand_id INT NOT NULL,
ADD CONSTRAINT fk_batches_brand FOREIGN KEY (brand_id) REFERENCES brands(id),
ADD INDEX idx_batches_brand_id (brand_id);

-- ============================================
-- 2. ADD brand_id TO ITEMS
-- ============================================

ALTER TABLE items
ADD COLUMN brand_id INT NULL AFTER id;

-- Populate brand_id from batch chain
UPDATE items i
JOIN batches b ON i.batch_id = b.id
SET i.brand_id = b.brand_id;

-- Make NOT NULL and add FK
ALTER TABLE items
MODIFY COLUMN brand_id INT NOT NULL,
ADD CONSTRAINT fk_items_brand FOREIGN KEY (brand_id) REFERENCES brands(id),
ADD INDEX idx_items_brand_id (brand_id);
