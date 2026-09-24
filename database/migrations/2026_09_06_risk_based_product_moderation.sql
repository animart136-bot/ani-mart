-- Risk-based product moderation and seller-controlled price explanations.
-- Smart Price Check remains advisory; it never sets or caps a seller's price.

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS price_justification TEXT NULL AFTER correction_reason,
    ADD COLUMN IF NOT EXISTS moderation_reason TEXT NULL AFTER price_justification;

ALTER TABLE products
    MODIFY approval_status VARCHAR(40) NOT NULL DEFAULT 'pending_admin_review';

