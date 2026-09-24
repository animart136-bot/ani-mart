-- Messaging Settings and Blocked Users

CREATE TABLE IF NOT EXISTS message_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    allow_messages_from ENUM('everyone','customers_only','verified_farmers_only','people_i_follow','nobody') NOT NULL DEFAULT 'everyone',
    message_notifications TINYINT(1) NOT NULL DEFAULT 1,
    message_request_notifications TINYINT(1) NOT NULL DEFAULT 1,
    group_notifications TINYINT(1) NOT NULL DEFAULT 0,
    community_notifications TINYINT(1) NOT NULL DEFAULT 1,
    announcement_notifications TINYINT(1) NOT NULL DEFAULT 1,
    order_chat_notifications TINYINT(1) NOT NULL DEFAULT 1,
    email_notifications TINYINT(1) NOT NULL DEFAULT 0,
    desktop_notifications TINYINT(1) NOT NULL DEFAULT 1,
    read_receipts TINYINT(1) NOT NULL DEFAULT 1,
    typing_indicator TINYINT(1) NOT NULL DEFAULT 1,
    online_status TINYINT(1) NOT NULL DEFAULT 1,
    auto_download_images TINYINT(1) NOT NULL DEFAULT 1,
    message_requests TINYINT(1) NOT NULL DEFAULT 1,
    blocked_users_enabled TINYINT(1) NOT NULL DEFAULT 1,
    theme ENUM('default','light','dark') NOT NULL DEFAULT 'default',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id)
);

CREATE TABLE IF NOT EXISTS blocked_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    blocked_user_id INT NOT NULL,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_blocked_pair (user_id, blocked_user_id),
    INDEX idx_user_id (user_id),
    INDEX idx_blocked_user_id (blocked_user_id)
);

CREATE TABLE IF NOT EXISTS message_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NULL,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    message TEXT NULL,
    image VARCHAR(255) NULL,
    status ENUM('pending','accepted','declined','blocked') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_recipient_id (recipient_id),
    INDEX idx_sender_id (sender_id),
    INDEX idx_status (status)
);
