-- ============================================
-- Migration 005: Drop companies and users tables
-- EXECUTE ONLY AFTER FULL VERIFICATION
-- ============================================

USE petersjo_dpp;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop users table first (depends on companies)
DROP TABLE IF EXISTS users;

-- Drop companies table
DROP TABLE IF EXISTS companies;

SET FOREIGN_KEY_CHECKS = 1;

-- Verify cleanup
SELECT 'Migration 005 complete - companies and users tables dropped' AS status;
