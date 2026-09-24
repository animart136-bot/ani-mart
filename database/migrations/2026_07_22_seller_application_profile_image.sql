-- Retain the image selected during a seller application until approval.
SET @db = DATABASE();

SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE farmer_applications ADD COLUMN seller_profile_image_path VARCHAR(255) NULL AFTER shop_banner_path',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db
  AND table_name = 'farmer_applications'
  AND column_name = 'seller_profile_image_path';

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
