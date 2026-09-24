-- Remove retired ID upload columns from existing farmer application tables.
SET @db = DATABASE();

SELECT IF(COUNT(*) = 0, 'SELECT 1', 'ALTER TABLE farmer_applications DROP COLUMN government_id_path') INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'government_id_path';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0, 'SELECT 1', 'ALTER TABLE farmer_applications DROP COLUMN government_id_paths') INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'government_id_paths';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0, 'SELECT 1', 'ALTER TABLE farmer_applications DROP COLUMN selfie_holding_id_path') INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'selfie_holding_id_path';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
