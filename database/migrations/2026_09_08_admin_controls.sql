-- Reversible administrator controls for catalog categories and user accounts.

ALTER TABLE categories ADD COLUMN IF NOT EXISTS description TEXT NULL;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS display_order INT NOT NULL DEFAULT 0;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS account_status ENUM('active','suspended') NOT NULL DEFAULT 'active' AFTER role,
    ADD COLUMN IF NOT EXISTS suspension_reason VARCHAR(500) NULL AFTER account_status,
    ADD COLUMN IF NOT EXISTS suspended_at TIMESTAMP NULL AFTER suspension_reason,
    ADD COLUMN IF NOT EXISTS suspended_by INT UNSIGNED NULL AFTER suspended_at;

CREATE INDEX IF NOT EXISTS idx_categories_active_order
    ON categories (is_active, display_order, name);

CREATE INDEX IF NOT EXISTS idx_users_account_status
    ON users (account_status);

UPDATE users u
INNER JOIN seller_profiles sp ON sp.user_id = u.id
SET u.account_status = 'suspended',
    u.suspension_reason = COALESCE(u.suspension_reason, 'Seller account was previously suspended.'),
    u.suspended_at = COALESCE(u.suspended_at, sp.suspended_at, NOW())
WHERE sp.status = 'suspended' OR sp.is_suspended = 1;
