ALTER TABLE seller_profiles
    ADD COLUMN is_suspended TINYINT(1) NOT NULL DEFAULT 0 AFTER transparency_score,
    ADD COLUMN suspended_at TIMESTAMP NULL AFTER is_suspended;

CREATE TABLE IF NOT EXISTS complaints (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    seller_profile_id INT UNSIGNED NOT NULL,
    category VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending','under_review','waiting_farmer_response','needs_more_evidence','resolved','rejected','closed') NOT NULL DEFAULT 'pending',
    priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    admin_notes TEXT NULL,
    assigned_admin_id INT UNSIGNED NULL,
    resolved_by INT UNSIGNED NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_complaints_order_id (order_id),
    INDEX idx_complaints_customer_id (customer_id),
    INDEX idx_complaints_seller_profile_id (seller_profile_id),
    INDEX idx_complaints_status (status),
    INDEX idx_complaints_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS complaint_evidence (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    uploader_role ENUM('customer','farmer','admin') NOT NULL,
    image_url VARCHAR(1024) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_complaint_evidence_complaint_id (complaint_id),
    INDEX idx_complaint_evidence_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS complaint_replies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_complaint_replies_complaint_id (complaint_id),
    INDEX idx_complaint_replies_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS complaint_actions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT UNSIGNED NOT NULL,
    admin_id INT UNSIGNED NOT NULL,
    action ENUM('approved','rejected','request_more_evidence','warn_farmer','suspend_product','suspend_farmer','restore_trust','close') NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_complaint_actions_complaint_id (complaint_id),
    INDEX idx_complaint_actions_admin_id (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
