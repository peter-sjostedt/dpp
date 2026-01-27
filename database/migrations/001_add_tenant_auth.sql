-- ============================================
-- Migration 001: Add API key columns to brands and suppliers
-- ============================================

USE petersjo_dpp;

-- ============================================
-- 1. ADD API KEY COLUMNS TO BRANDS
-- ============================================

ALTER TABLE brands
ADD COLUMN api_key VARCHAR(64) UNIQUE AFTER trader_address,
ADD COLUMN _is_active BOOLEAN DEFAULT TRUE AFTER api_key,
ADD COLUMN _last_used_at TIMESTAMP NULL AFTER _is_active,
ADD COLUMN _expires_at TIMESTAMP NULL AFTER _last_used_at;

-- Generate API keys for existing brands
UPDATE brands SET api_key = CONCAT('brand_', LOWER(HEX(RANDOM_BYTES(24)))) WHERE api_key IS NULL;

-- ============================================
-- 2. ADD API KEY COLUMNS TO SUPPLIERS
-- ============================================

ALTER TABLE suppliers
ADD COLUMN api_key VARCHAR(64) UNIQUE AFTER operator_identifier,
ADD COLUMN _is_active BOOLEAN DEFAULT TRUE AFTER api_key,
ADD COLUMN _last_used_at TIMESTAMP NULL AFTER _is_active,
ADD COLUMN _expires_at TIMESTAMP NULL AFTER _last_used_at;

-- Generate API keys for existing suppliers
UPDATE suppliers SET api_key = CONCAT('supplier_', LOWER(HEX(RANDOM_BYTES(24)))) WHERE api_key IS NULL;

-- Add indices for API key lookups
CREATE INDEX idx_brands_api_key ON brands(api_key);
CREATE INDEX idx_suppliers_api_key ON suppliers(api_key);
