ALTER TABLE users
    MODIFY COLUMN account_status ENUM('active','suspended','deleted') NOT NULL DEFAULT 'active',
    ADD COLUMN IF NOT EXISTS deletion_requested_at TIMESTAMP NULL AFTER suspended_by,
    ADD COLUMN IF NOT EXISTS deletion_scheduled_for TIMESTAMP NULL AFTER deletion_requested_at,
    ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL AFTER deletion_scheduled_for;

CREATE INDEX IF NOT EXISTS idx_users_deletion_scheduled
    ON users (account_status, deletion_scheduled_for);
