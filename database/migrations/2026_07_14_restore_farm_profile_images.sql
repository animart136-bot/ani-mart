CREATE TABLE IF NOT EXISTS farm_profile_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_profile_id INT UNSIGNED NOT NULL,
    image_type ENUM('farm','harvest') NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    caption VARCHAR(160) NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_farm_profile_images_seller_type (seller_profile_id, image_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
