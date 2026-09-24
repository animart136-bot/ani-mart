-- Allow notifications for new community comments.
ALTER TABLE notifications
    MODIFY COLUMN type ENUM('order', 'product', 'review', 'harvest', 'trust', 'complaint', 'verification', 'promotion', 'system', 'message', 'comment') NOT NULL DEFAULT 'order';
