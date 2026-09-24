-- Add the AI-assisted RSBSA document review fields to existing farmer applications.
SET @db = DATABASE();

SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN ai_review_status VARCHAR(40) NOT NULL DEFAULT ''pending'' AFTER ai_analysis', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'ai_review_status';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN ai_review_summary TEXT NULL AFTER ai_review_status', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'ai_review_summary';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN ai_match_score DECIMAL(5,2) NULL AFTER ai_review_summary', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'ai_match_score';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN ai_review_notes TEXT NULL AFTER ai_match_score', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'ai_review_notes';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0, 'ALTER TABLE farmer_applications ADD COLUMN ai_reviewed_at TIMESTAMP NULL AFTER ai_review_notes', 'SELECT 1') INTO @sql FROM information_schema.columns WHERE table_schema = @db AND table_name = 'farmer_applications' AND column_name = 'ai_reviewed_at';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
