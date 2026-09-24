ALTER TABLE complaint_actions
    MODIFY COLUMN action ENUM(
        'approved',
        'rejected',
        'request_more_evidence',
        'warn_farmer',
        'suspend_product',
        'suspend_farmer',
        'restore_trust',
        'close',
        'reopened'
    ) NOT NULL;
