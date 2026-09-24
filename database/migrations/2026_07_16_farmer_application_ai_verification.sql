-- AI pre-verification data for RSBSA farmer applications.
-- AI provides a recommendation only; an administrator still makes the approval decision.
SET @db = DATABASE();

SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN ai_status ENUM(''pending'', ''verified'', ''review'', ''rejected'') NOT NULL DEFAULT ''pending'' AFTER status', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'ai_status'; PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN confidence_score DECIMAL(5,2) NULL AFTER ai_status', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'confidence_score'; PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN verification_reason TEXT NULL AFTER confidence_score', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'verification_reason'; PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN verified_at TIMESTAMP NULL AFTER verification_reason', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'verified_at'; PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN rsbsa_card_path TEXT NULL AFTER status', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'rsbsa_card_path'; PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN extracted_name VARCHAR(255) NULL AFTER rsbsa_card_path', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'extracted_name'; PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN extracted_rsbsa_number VARCHAR(60) NULL AFTER extracted_name', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'extracted_rsbsa_number'; PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN extracted_card_type VARCHAR(120) NULL AFTER extracted_rsbsa_number', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'extracted_card_type'; PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN extracted_agency VARCHAR(160) NULL AFTER extracted_card_type', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'extracted_agency'; PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN ai_analysis JSON NULL AFTER extracted_agency', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'ai_analysis'; PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0, 'ALTER TABLE seller_profiles ADD COLUMN ai_verified_farmer TINYINT(1) NOT NULL DEFAULT 0 AFTER verification_badge', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'seller_profiles' AND column_name = 'ai_verified_farmer'; PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT IF(COUNT(*) = 0, 'ALTER TABLE seller_profiles ADD COLUMN rsbsa_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER ai_verified_farmer', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'seller_profiles' AND column_name = 'rsbsa_verified'; PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Retains every AI screening attempt without changing the administrator-owned
-- application decision. This intentionally has no destructive cascade.
CREATE TABLE IF NOT EXISTS verification_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    application_id BIGINT UNSIGNED NOT NULL,
    confidence_score DECIMAL(5,2) NULL,
    ai_status ENUM('pending', 'verified', 'review', 'rejected') NOT NULL DEFAULT 'pending',
    reason TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX verification_logs_application_id_index (application_id),
    INDEX verification_logs_status_created_at_index (ai_status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
