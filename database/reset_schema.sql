-- ============================================
-- DPP Platform - Reset Schema
-- Development only - drops and recreates all tables
-- ============================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS petersjo_dpp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE petersjo_dpp;

-- Load schema files in order
SOURCE schema/01_foundation.sql;
SOURCE schema/02_products_batches.sql;
SOURCE schema/03_care_compliance.sql;

SELECT 'Schema reset complete!' AS status;
