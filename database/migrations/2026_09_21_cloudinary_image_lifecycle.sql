CREATE TABLE IF NOT EXISTS image_assets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    public_id VARCHAR(255) NOT NULL,
    secure_url VARCHAR(1024) NOT NULL,
    delivery_type VARCHAR(32) NOT NULL DEFAULT 'upload',
    resource_type VARCHAR(32) NOT NULL DEFAULT 'image',
    format VARCHAR(20) NULL,
    bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    visibility ENUM('public','sensitive') NOT NULL DEFAULT 'public',
    owner_user_id INT NULL,
    entity_type VARCHAR(80) NULL,
    entity_id BIGINT NULL,
    retain_until TIMESTAMP NULL,
    delete_after TIMESTAMP NULL,
    status ENUM('active','deletion_pending','delete_failed','deleted') NOT NULL DEFAULT 'active',
    delete_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_delete_error VARCHAR(1000) NULL,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_image_assets_public_id (public_id),
    KEY idx_image_assets_entity (entity_type, entity_id),
    KEY idx_image_assets_cleanup (status, delete_after),
    KEY idx_image_assets_owner (owner_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE community_posts
    ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL AFTER updated_at;
