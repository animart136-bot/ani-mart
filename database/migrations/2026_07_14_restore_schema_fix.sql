SET @db = DATABASE();

-- Add first_name and last_name to users if missing.
SELECT COUNT(*) INTO @col_count
FROM information_schema.columns
WHERE table_schema = @db
  AND table_name = 'users'
  AND column_name = 'first_name';

IF @col_count = 0 THEN
    ALTER TABLE users ADD COLUMN first_name VARCHAR(80) NULL AFTER fullname;
END IF;

SELECT COUNT(*) INTO @col_count
FROM information_schema.columns
WHERE table_schema = @db
  AND table_name = 'users'
  AND column_name = 'last_name';

IF @col_count = 0 THEN
    ALTER TABLE users ADD COLUMN last_name VARCHAR(80) NULL AFTER first_name;
END IF;

SELECT COUNT(*) INTO @col_count
FROM information_schema.tables
WHERE table_schema = @db
  AND table_name = 'seller_profiles';

IF @col_count = 0 THEN
    CREATE TABLE seller_profiles (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        shop_name VARCHAR(255) NOT NULL,
        description TEXT NULL,
        slug VARCHAR(160) NULL,
        barangay VARCHAR(255) NULL,
        city VARCHAR(255) NULL,
        latitude VARCHAR(80) NULL,
        longitude VARCHAR(80) NULL,
        trust_score INT UNSIGNED DEFAULT 100,
        transparency_score INT UNSIGNED DEFAULT 100,
        verification_badge ENUM('new','verified','trusted','premium') NOT NULL DEFAULT 'new',
        status ENUM('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
        farming_method VARCHAR(120) NULL,
        years_experience INT UNSIGNED DEFAULT 0,
        response_rate TINYINT UNSIGNED DEFAULT 95,
        average_delivery_time VARCHAR(80) NULL,
        farm_banner VARCHAR(255) NULL,
        farm_logo VARCHAR(255) NULL,
        farm_location VARCHAR(255) NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_seller_profiles_user_id (user_id),
        UNIQUE INDEX idx_seller_profiles_slug (slug),
        CONSTRAINT fk_seller_profiles_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
END IF;

SELECT COUNT(*) INTO @col_count
FROM information_schema.tables
WHERE table_schema = @db
  AND table_name = 'farm_profile_images';

IF @col_count = 0 THEN
    CREATE TABLE farm_profile_images (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        seller_profile_id INT UNSIGNED NOT NULL,
        image_type ENUM('farm','harvest') NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        caption VARCHAR(160) NULL,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_farm_profile_images_seller_type (seller_profile_id, image_type),
        CONSTRAINT fk_farm_profile_images_seller_profile_id FOREIGN KEY (seller_profile_id) REFERENCES seller_profiles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
END IF;

SELECT COUNT(*) INTO @col_count
FROM information_schema.tables
WHERE table_schema = @db
  AND table_name = 'community_categories';

IF @col_count = 0 THEN
    CREATE TABLE community_categories (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL UNIQUE,
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
END IF;
