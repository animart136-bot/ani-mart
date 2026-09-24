-- Restore the core product category catalog used by marketplace/product flows.

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO categories (name, description) VALUES
('Vegetables', 'Fresh leafy greens, root crops, and other locally grown vegetables.'),
('Fruits', 'Seasonal fruits harvested from local farms.'),
('Grains', 'Rice, corn, and other staple grains.'),
('Herbs and Spices', 'Fresh herbs, aromatics, and natural spices.'),
('Organic Products', 'Certified or naturally grown farm products.'),
('Livestock and Poultry', 'Farm-raised meat, poultry, and related products.'),
('Dairy and Eggs', 'Milk, cheese, eggs, and other dairy farm products.'),
('Seedlings and Plants', 'Seedlings, starter plants, and farm-ready crops.');
