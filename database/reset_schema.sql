-- ============================================
-- DPP Platform - Reset Schema
-- Development only - drops and recreates all tables
-- ============================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS petersjo_dpp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE petersjo_dpp;

-- ============================================
-- CLEANUP: Drop legacy/orphan tables
-- Tables that existed in earlier versions but are no longer used
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- Old table names (replaced by *_info variants)
DROP TABLE IF EXISTS compliance_information;
DROP TABLE IF EXISTS circularity_information;
DROP TABLE IF EXISTS sustainability_information;

-- Tables that never existed in schema but were referenced in old controllers
DROP TABLE IF EXISTS certifications;
DROP TABLE IF EXISTS chemical_compliance;
DROP TABLE IF EXISTS component_materials;

-- Old multi-tenant tables (replaced by brand/supplier tenancy)
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS companies;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Load schema files in order
-- ============================================

SOURCE schema/01_foundation.sql;
SOURCE schema/02_products_batches.sql;
SOURCE schema/03_care_compliance.sql;

SELECT 'Schema reset complete!' AS status;
