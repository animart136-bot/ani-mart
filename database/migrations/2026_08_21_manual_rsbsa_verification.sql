-- Manual RSBSA verification workflow for Ani-Mart seller applications.
-- Apply this migration once. Every ALTER is guarded by information_schema checks.
SET @db = DATABASE();

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE farmer_applications MODIFY status ENUM(''pending'',''incomplete'',''pending_official_verification'',''needs_correction'',''approved'',''rejected'',''suspended'') NOT NULL DEFAULT ''pending''',
    'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'status';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @columns = 'middle_name,name_extension,sex,date_of_birth,rsbsa_number,application_status,rsbsa_verification_status,rsbsa_verified_by,rsbsa_verified_at,rsbsa_verification_notes,declaration_accepted_at';

SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN first_name VARCHAR(120) NULL AFTER user_id', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'first_name';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN last_name VARCHAR(120) NULL AFTER first_name', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'last_name';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN middle_name VARCHAR(120) NULL AFTER first_name', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'middle_name';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN name_extension VARCHAR(40) NULL AFTER last_name', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'name_extension';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN sex ENUM(''male'',''female'',''other'',''prefer_not_to_say'') NULL AFTER name_extension', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'sex';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN date_of_birth DATE NULL AFTER sex', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'date_of_birth';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN rsbsa_number VARCHAR(60) NULL AFTER date_of_birth', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'rsbsa_number';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN application_status VARCHAR(40) NULL AFTER status', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'application_status';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN rsbsa_verification_status VARCHAR(40) NULL AFTER application_status', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'rsbsa_verification_status';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN rsbsa_verified_by INT NULL AFTER rsbsa_verification_status', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'rsbsa_verified_by';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN rsbsa_verified_at TIMESTAMP NULL AFTER rsbsa_verified_by', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'rsbsa_verified_at';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN rsbsa_verification_notes TEXT NULL AFTER rsbsa_verified_at', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'rsbsa_verification_notes';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN declaration_accepted_at TIMESTAMP NULL AFTER rsbsa_verification_notes', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'declaration_accepted_at';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0, 'ALTER TABLE verification_logs ADD COLUMN admin_id INT NULL AFTER application_id', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'verification_logs' AND column_name = 'admin_id';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE verification_logs ADD COLUMN verification_type VARCHAR(40) NOT NULL DEFAULT ''application_review'' AFTER admin_id', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'verification_logs' AND column_name = 'verification_type';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE verification_logs ADD COLUMN verification_status VARCHAR(40) NULL AFTER verification_type', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'verification_logs' AND column_name = 'verification_status';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE verification_logs ADD COLUMN notes TEXT NULL AFTER verification_status', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'verification_logs' AND column_name = 'notes';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE verification_logs ADD COLUMN verified_at TIMESTAMP NULL AFTER notes', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'verification_logs' AND column_name = 'verified_at';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
