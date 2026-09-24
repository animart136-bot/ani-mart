<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class HomeController extends Controller
{
    public function index()
    {
        $db = Database::connection();

        $farmersCount = $db->query("
            SELECT COUNT(*) AS total
            FROM seller_profiles
            INNER JOIN users ON users.id = seller_profiles.user_id
            WHERE seller_profiles.status = 'approved'
                AND COALESCE(users.account_status, 'active') = 'active'
        ")->fetch()->total ?? 0;

        $productsCount = $db->query("
            SELECT COUNT(*) AS total
            FROM products
            INNER JOIN seller_profiles ON seller_profiles.id = products.seller_profile_id
            INNER JOIN users ON users.id = seller_profiles.user_id
            WHERE products.is_active = 1
                AND COALESCE(products.approval_status, 'approved') = 'approved'
                AND seller_profiles.status = 'approved'
                AND COALESCE(users.account_status, 'active') = 'active'
        ")->fetch()->total ?? 0;

        $ordersCount = $db->query("
            SELECT COUNT(*) AS total
            FROM orders
            WHERE status IN ('completed', 'delivered')
        ")->fetch()->total ?? 0;

        $featuredProducts = $db->query("
            SELECT
                products.id,
                products.name,
                products.price,
                products.stock,
                products.image,
                categories.name AS category_name,
                seller_profiles.shop_name,
                seller_profiles.slug AS farm_slug,
                seller_profiles.trust_score,
                COALESCE(
                    (
                        SELECT product_images.image_url
                        FROM product_images
                        WHERE product_images.product_id = products.id
                        ORDER BY product_images.is_cover DESC, product_images.display_order ASC, product_images.id ASC
                        LIMIT 1
                    ),
                    products.image
                ) AS product_image,
                COALESCE(
                    (
                        SELECT MIN(COALESCE(product_variants.retail_price, product_variants.wholesale_price))
                        FROM product_variants
                        WHERE product_variants.product_id = products.id
                            AND product_variants.is_active = 1
                    ),
                    products.price
                ) AS display_price,
                (
                    SELECT product_variants.unit
                    FROM product_variants
                    WHERE product_variants.product_id = products.id
                        AND product_variants.is_active = 1
                        AND product_variants.unit IS NOT NULL
                    ORDER BY product_variants.id ASC
                    LIMIT 1
                ) AS unit,
                COALESCE(
                    (
                        SELECT SUM(product_variants.stock)
                        FROM product_variants
                        WHERE product_variants.product_id = products.id
                            AND product_variants.is_active = 1
                    ),
                    products.stock,
                    0
                ) AS available_stock,
                COALESCE(
                    (
                        SELECT ROUND(AVG(reviews.rating), 1)
                        FROM reviews
                        WHERE reviews.product_id = products.id
                    ),
                    0
                ) AS average_rating,
                (
                    SELECT COUNT(*)
                    FROM reviews
                    WHERE reviews.product_id = products.id
                ) AS review_count
            FROM products
            INNER JOIN seller_profiles ON seller_profiles.id = products.seller_profile_id
            INNER JOIN users ON users.id = seller_profiles.user_id
            LEFT JOIN categories ON categories.id = products.category_id
            WHERE products.is_active = 1
                AND COALESCE(products.approval_status, 'approved') = 'approved'
                AND seller_profiles.status = 'approved'
                AND COALESCE(users.account_status, 'active') = 'active'
            ORDER BY products.created_at DESC, products.id DESC
            LIMIT 4
        ")->fetchAll();

        $popularCategories = $db->query("
            SELECT
                categories.name,
                categories.slug,
                COUNT(products.id) AS product_count
            FROM categories
            INNER JOIN products
                ON products.category_id = categories.id
                AND products.is_active = 1
                AND COALESCE(products.approval_status, 'approved') = 'approved'
            INNER JOIN seller_profiles
                ON seller_profiles.id = products.seller_profile_id
                AND seller_profiles.status = 'approved'
            INNER JOIN users
                ON users.id = seller_profiles.user_id
                AND COALESCE(users.account_status, 'active') = 'active'
            WHERE categories.is_active = 1
            GROUP BY categories.id, categories.name, categories.slug
            ORDER BY product_count DESC, categories.name ASC
            LIMIT 4
        ")->fetchAll();

        return $this->view(
            'home/index',
            [
                'farmersCount' => $farmersCount,
                'productsCount' => $productsCount,
                'ordersCount' => $ordersCount,
                'featuredProducts' => $featuredProducts,
                'popularCategories' => $popularCategories,
                'pageCss' => '/ani-mart/public/assets/css/pages/home.css',
            ]
        );
    }
}
