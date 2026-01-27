-- ============================================
-- DPP Platform - Load Test Data
-- Run after reset_schema.sql
-- ============================================

USE petersjo_dpp;

SOURCE testdata/healthcare_textiles.sql;

SELECT 'Test data loaded!' AS status;
