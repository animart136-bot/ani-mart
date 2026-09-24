-- Ensure seller_profiles has a contact_number column for shop records.
SET @db = DATABASE();

SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE seller_profiles ADD COLUMN contact_number VARCHAR(80) NULL AFTER farm_location',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db
  AND table_name = 'seller_profiles'
  AND column_name = 'contact_number';

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
