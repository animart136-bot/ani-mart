ALTER TABLE seller_profiles
ADD COLUMN IF NOT EXISTS delivery_fee DECIMAL(10,2) NULL AFTER average_delivery_time;
