-- Add post-delivery experience ratings to customer product reviews.

ALTER TABLE reviews
    ADD COLUMN product_condition_rating TINYINT UNSIGNED NULL AFTER seller_responsiveness_rating,
    ADD COLUMN driver_friendliness_rating TINYINT UNSIGNED NULL AFTER product_condition_rating,
    ADD COLUMN delivery_speed_rating TINYINT UNSIGNED NULL AFTER driver_friendliness_rating;
