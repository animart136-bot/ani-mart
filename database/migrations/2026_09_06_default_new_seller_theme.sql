ALTER TABLE seller_profiles
    MODIFY COLUMN shop_theme VARCHAR(40) NOT NULL DEFAULT 'fresh-farm';

UPDATE seller_profiles
SET shop_theme = 'fresh-farm'
WHERE shop_theme IS NULL OR TRIM(shop_theme) = '';
