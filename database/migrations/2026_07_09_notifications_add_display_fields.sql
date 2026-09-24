-- Upgrade existing notifications tables created before smart display metadata.
ALTER TABLE notifications
    ADD COLUMN IF NOT EXISTS icon VARCHAR(50) DEFAULT 'bi-bell' AFTER message,
    ADD COLUMN IF NOT EXISTS color VARCHAR(20) DEFAULT 'primary' AFTER icon,
    ADD COLUMN IF NOT EXISTS url VARCHAR(255) NULL AFTER color;

