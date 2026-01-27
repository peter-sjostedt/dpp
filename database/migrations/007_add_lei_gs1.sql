-- Migration: Add LEI and GS1 Company Prefix to brands and suppliers
-- These are globally unique identifiers for legal entities
-- LEI: Legal Entity Identifier (ISO 17442) - exactly 20 alphanumeric characters
-- GS1: Company Prefix - 6-12 digits

-- Add columns to brands if they don't exist
ALTER TABLE brands
ADD COLUMN IF NOT EXISTS lei CHAR(20) UNIQUE NULL COMMENT 'Legal Entity Identifier (ISO 17442)',
ADD COLUMN IF NOT EXISTS gs1_company_prefix VARCHAR(12) UNIQUE NULL COMMENT 'GS1 Company Prefix (6-12 digits)';

-- Add columns to suppliers if they don't exist
ALTER TABLE suppliers
ADD COLUMN IF NOT EXISTS lei CHAR(20) UNIQUE NULL COMMENT 'Legal Entity Identifier (ISO 17442)',
ADD COLUMN IF NOT EXISTS gs1_company_prefix VARCHAR(12) UNIQUE NULL COMMENT 'GS1 Company Prefix (6-12 digits)';

-- Update existing test data with realistic LEI and GS1 values
UPDATE brands SET
    lei = '549300VARDTEX00001SE',
    gs1_company_prefix = '7350012345'
WHERE brand_name = 'VårdTex' AND lei IS NULL;

UPDATE brands SET
    lei = '549300MEDIWEAR0002SE',
    gs1_company_prefix = '7350098765'
WHERE brand_name = 'MediWear' AND lei IS NULL;

UPDATE brands SET
    lei = '549300HEALTHTEX03FI',
    gs1_company_prefix = '6430012345'
WHERE brand_name = 'HealthTex' AND lei IS NULL;

UPDATE brands SET
    lei = '549300CLEANCARE04NO',
    gs1_company_prefix = '7090012345'
WHERE brand_name = 'CleanCare Textiles' AND lei IS NULL;

UPDATE suppliers SET
    lei = '549300PORTOTEXTIL01',
    gs1_company_prefix = '5609876543'
WHERE supplier_name LIKE '%Porto%' AND lei IS NULL;

UPDATE suppliers SET
    lei = '549300BALTICTEX02LT',
    gs1_company_prefix = '4771234567'
WHERE supplier_name LIKE '%Baltic%' AND lei IS NULL;

UPDATE suppliers SET
    lei = '549300TUNISIATEX03T',
    gs1_company_prefix = '6191234567'
WHERE supplier_name LIKE '%Tunisia%' AND lei IS NULL;

UPDATE suppliers SET
    lei = '549300AEGEANFAB04TR',
    gs1_company_prefix = '8691234567'
WHERE supplier_name LIKE '%Aegean%' AND lei IS NULL;

UPDATE suppliers SET
    lei = '549300SHANGHAITX05C',
    gs1_company_prefix = '6901234567'
WHERE supplier_name LIKE '%Shanghai%' AND lei IS NULL;
