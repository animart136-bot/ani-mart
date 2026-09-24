ALTER TABLE users
ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,7) NULL AFTER profile_image,
ADD COLUMN IF NOT EXISTS longitude DECIMAL(10,7) NULL AFTER latitude,
ADD COLUMN IF NOT EXISTS address TEXT NULL AFTER longitude,
ADD COLUMN IF NOT EXISTS barangay VARCHAR(120) NULL AFTER address,
ADD COLUMN IF NOT EXISTS municipality VARCHAR(120) NULL AFTER barangay,
ADD COLUMN IF NOT EXISTS province VARCHAR(120) NULL AFTER municipality;

ALTER TABLE orders
ADD COLUMN IF NOT EXISTS delivery_latitude DECIMAL(10,7) NULL AFTER proof_image,
ADD COLUMN IF NOT EXISTS delivery_longitude DECIMAL(10,7) NULL AFTER delivery_latitude,
ADD COLUMN IF NOT EXISTS delivery_address TEXT NULL AFTER delivery_longitude,
ADD COLUMN IF NOT EXISTS delivery_barangay VARCHAR(120) NULL AFTER delivery_address,
ADD COLUMN IF NOT EXISTS delivery_municipality VARCHAR(120) NULL AFTER delivery_barangay,
ADD COLUMN IF NOT EXISTS delivery_province VARCHAR(120) NULL AFTER delivery_municipality,
ADD COLUMN IF NOT EXISTS delivery_distance DECIMAL(8,2) NULL AFTER delivery_province,
ADD COLUMN IF NOT EXISTS delivery_fee DECIMAL(10,2) NULL AFTER delivery_distance;

ALTER TABLE seller_profiles
ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,7) NULL AFTER farm_location,
ADD COLUMN IF NOT EXISTS longitude DECIMAL(10,7) NULL AFTER latitude;
