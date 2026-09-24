-- Create farmer_applications table for seller application submissions.
-- This migration is safe to run multiple times and will not overwrite an existing table.
CREATE TABLE IF NOT EXISTS farmer_applications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    farm_name VARCHAR(255) NOT NULL,
    farm_description TEXT NULL,
    address VARCHAR(255) NULL,
    contact_number VARCHAR(80) NULL,
    shop_banner_path VARCHAR(255) NULL,
    seller_profile_image_path VARCHAR(255) NULL,
    status ENUM('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
    rsbsa_card_path TEXT NULL,
    ai_status ENUM('pending','verified','review','rejected') NOT NULL DEFAULT 'pending',
    confidence_score DECIMAL(5,2) NULL,
    verification_reason TEXT NULL,
    verified_at TIMESTAMP NULL,
    extracted_name VARCHAR(255) NULL,
    extracted_rsbsa_number VARCHAR(60) NULL,
    extracted_card_type VARCHAR(120) NULL,
    extracted_agency VARCHAR(160) NULL,
    ai_analysis JSON NULL,
    submitted_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_farmer_applications_user_id (user_id),
    INDEX idx_farmer_applications_status (status),
    INDEX idx_farmer_applications_ai_status (ai_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
