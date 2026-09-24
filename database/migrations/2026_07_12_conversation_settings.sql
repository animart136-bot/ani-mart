-- Conversation Settings for per-chat preferences

CREATE TABLE IF NOT EXISTS conversation_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    notifications_muted TINYINT(1) DEFAULT 0,
    pinned TINYINT(1) DEFAULT 0,
    hide_message_preview TINYINT(1) DEFAULT 0,
    auto_download_images TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_conversation_id (conversation_id),
    INDEX idx_conversation_id (conversation_id),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
);
