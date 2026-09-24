-- Multi-shop support for Ani-Mart seller profiles and verification metadata.

ALTER TABLE seller_profiles
    ADD COLUMN IF NOT EXISTS logo VARCHAR(255) NULL AFTER shop_name,
    ADD COLUMN IF NOT EXISTS banner VARCHAR(255) NULL AFTER logo,
    ADD COLUMN IF NOT EXISTS address VARCHAR(255) NULL AFTER description,
    ADD COLUMN IF NOT EXISTS contact_number VARCHAR(80) NULL AFTER address,
    ADD COLUMN IF NOT EXISTS province VARCHAR(255) NULL AFTER city,
    ADD COLUMN IF NOT EXISTS verification_status ENUM('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending' AFTER status,
    ADD COLUMN IF NOT EXISTS badge ENUM('new','verified','trusted','premium') NOT NULL DEFAULT 'new' AFTER verification_badge,
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER badge;

UPDATE seller_profiles
SET verification_status = status
WHERE verification_status IS NULL;

UPDATE seller_profiles
SET badge = verification_badge
WHERE badge IS NULL;
