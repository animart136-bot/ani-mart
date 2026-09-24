ALTER TABLE seller_profiles
    ADD COLUMN trust_score INT UNSIGNED NOT NULL DEFAULT 100 AFTER transparency_score;

CREATE TABLE IF NOT EXISTS reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT UNSIGNED NOT NULL,
    target_type VARCHAR(64) NOT NULL,
    target_id INT UNSIGNED NULL,
    reason VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('submitted','pending_review','assigned','investigation','need_more_info','resolved','rejected') NOT NULL DEFAULT 'submitted',
    priority ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    assigned_admin_id INT UNSIGNED NULL,
    resolved_by INT UNSIGNED NULL,
    admin_notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    INDEX idx_reports_reporter_id (reporter_id),
    INDEX idx_reports_target_type (target_type),
    INDEX idx_reports_status (status),
    INDEX idx_reports_priority (priority),
    INDEX idx_reports_assigned_admin_id (assigned_admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_evidence (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id INT UNSIGNED NOT NULL,
    image_url VARCHAR(1024) NOT NULL,
    public_id VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_evidence_report_id (report_id),
    CONSTRAINT fk_report_evidence_report FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS appeals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_appeals_report_id (report_id),
    INDEX idx_appeals_user_id (user_id),
    CONSTRAINT fk_appeals_report FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_actions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id INT UNSIGNED NOT NULL,
    admin_id INT UNSIGNED NOT NULL,
    action ENUM('assign','request_more_info','warn_user','delete_product','delete_comment','delete_post','mute_user','suspend_farmer','suspend_customer','temporary_ban','permanent_ban','reject_report','resolve_report','appeal_accepted','appeal_rejected','close_report') NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_actions_report_id (report_id),
    INDEX idx_report_actions_admin_id (admin_id),
    CONSTRAINT fk_report_actions_report FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
