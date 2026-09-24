-- Allow government_id to be nullable on seller_profiles so seller shop creation can proceed without the legacy government_id field.
SET @db = DATABASE();

SELECT IF(
    COUNT(*) = 0,
    'SELECT 1',
    'ALTER TABLE seller_profiles MODIFY COLUMN government_id VARCHAR(255) NULL'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db
  AND table_name = 'seller_profiles'
  AND column_name = 'government_id';

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
