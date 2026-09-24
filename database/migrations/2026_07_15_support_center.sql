-- Ani-Mart Support Center

CREATE TABLE IF NOT EXISTS bug_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    category ENUM('Broken page','Error','Login issue','Payment problem','Marketplace bug','Mobile bug','Suggestion') NOT NULL,
    priority ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    browser VARCHAR(255) NULL,
    device VARCHAR(255) NULL,
    screenshot VARCHAR(1024) NULL,
    description TEXT NOT NULL,
    status ENUM('pending','investigating','waiting_user','resolved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bug_reports_user_id (user_id),
    INDEX idx_bug_reports_status (status),
    INDEX idx_bug_reports_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_conversations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_type ENUM('customer_support','farmer_support','complaint','verification','bug_report','community_report') NOT NULL,
    subject VARCHAR(255) NULL,
    source_type ENUM('complaint','bug_report','verification','community_report') NULL,
    source_id INT UNSIGNED NULL,
    customer_id INT UNSIGNED NULL,
    farmer_id INT UNSIGNED NULL,
    priority ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    status ENUM('open','pending','investigating','waiting_seller','waiting_customer','resolved','rejected') NOT NULL DEFAULT 'open',
    assigned_to INT UNSIGNED NULL,
    last_message TEXT NULL,
    last_message_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_support_conversations_type (conversation_type),
    INDEX idx_support_conversations_status (status),
    INDEX idx_support_conversations_priority (priority),
    INDEX idx_support_conversations_assigned_to (assigned_to),
    INDEX idx_support_conversations_customer_id (customer_id),
    INDEX idx_support_conversations_farmer_id (farmer_id),
    INDEX idx_support_conversations_source (source_type, source_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    sender_role ENUM('admin','farmer','customer','system') NOT NULL DEFAULT 'admin',
    message_type ENUM('text','image','attachment','system','status') NOT NULL DEFAULT 'text',
    message TEXT NULL,
    attachment_url VARCHAR(1024) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_support_messages_conversation_id (conversation_id),
    INDEX idx_support_messages_sender_id (sender_id),
    INDEX idx_support_messages_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_participants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role ENUM('admin','farmer','customer') NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_support_participants_conversation_id (conversation_id),
    INDEX idx_support_participants_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED NOT NULL,
    message_id INT UNSIGNED NULL,
    attachment_type ENUM('image','file','screenshot') NOT NULL DEFAULT 'file',
    file_url VARCHAR(1024) NOT NULL,
    filename VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_support_attachments_conversation_id (conversation_id),
    INDEX idx_support_attachments_message_id (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED NOT NULL,
    admin_id INT UNSIGNED NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_support_notes_conversation_id (conversation_id),
    INDEX idx_support_notes_admin_id (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quick_reply_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL,
    body TEXT NOT NULL,
    category ENUM('general','complaint','verification','bug','community','broadcast') NOT NULL DEFAULT 'general',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_quick_reply_templates_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS broadcast_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    audience ENUM('everyone','customers','farmers','admins','specific') NOT NULL DEFAULT 'everyone',
    audience_data TEXT NULL,
    broadcast_type ENUM('announcement','maintenance','emergency','weather','marketplace_news','agricultural_tips','feature_update') NOT NULL DEFAULT 'announcement',
    status ENUM('draft','sent','scheduled') NOT NULL DEFAULT 'draft',
    scheduled_at TIMESTAMP NULL DEFAULT NULL,
    sent_at TIMESTAMP NULL DEFAULT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_broadcast_messages_status (status),
    INDEX idx_broadcast_messages_audience (audience)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conversation_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED NOT NULL,
    admin_id INT UNSIGNED NOT NULL,
    assigned_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conversation_assignments_conversation_id (conversation_id),
    INDEX idx_conversation_assignments_admin_id (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    conversation_id INT UNSIGNED NULL,
    notification_type ENUM('new_conversation','new_message','status_update','assignment','broadcast') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    target_url VARCHAR(1024) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_support_notifications_user_id (user_id),
    INDEX idx_support_notifications_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
