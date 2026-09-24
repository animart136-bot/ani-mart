-- Add deep linking metadata to notifications
ALTER TABLE notifications
    ADD COLUMN IF NOT EXISTS target_type VARCHAR(50) NULL AFTER url,
    ADD COLUMN IF NOT EXISTS target_id INT NULL AFTER target_type,
    ADD COLUMN IF NOT EXISTS action_url VARCHAR(255) NULL AFTER target_id;

CREATE INDEX IF NOT EXISTS idx_notifications_target_type ON notifications(target_type);
CREATE INDEX IF NOT EXISTS idx_notifications_target_id ON notifications(target_id);
CREATE INDEX IF NOT EXISTS idx_notifications_action_url ON notifications(action_url);
