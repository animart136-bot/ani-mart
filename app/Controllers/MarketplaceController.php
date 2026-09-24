<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\CustomerStatisticsService;
use App\Services\RecommendationService;
use App\Services\SmartPriceCheckService;
use App\Services\CategoryAdminService;
use App\Services\AccountControlService;
use App\Services\SchemaInspector;
use App\Services\SlugService;

class MarketplaceController extends Controller
{
    public function index()
    {
        try {

            $db = Database::connection();

            (new SmartPriceCheckService())->ensureSchema();
            (new CategoryAdminService())->ensureSchema();
            (new AccountControlService())->ensureSchema();
            $this->ensureSellerProfileSlugColumn();

            /*
            |--------------------------------------------------------------------------
            | Categories
            |--------------------------------------------------------------------------
            */

            $categoryStmt = $db->query("

                SELECT *

                FROM categories

                WHERE is_active = 1

                ORDER BY name ASC

            ");

            $categories = $categoryStmt->fetchAll();

            /*
            |--------------------------------------------------------------------------
            | Products
            |--------------------------------------------------------------------------
            */

            $productStmt = $db->query("

SELECT

    products.*,

    categories.name AS category_name,

    seller_profiles.shop_name,
    seller_profiles.farm_logo,
    seller_profiles.slug AS farm_slug,
    seller_profiles.shop_theme,
    seller_profiles.farm_location,
    seller_profiles.farm_location_encrypted,
    seller_profiles.barangay,
    seller_profiles.barangay_encrypted,
    seller_profiles.city,
    seller_profiles.city_encrypted,
    seller_profiles.trust_score,
    seller_profiles.transparency_score,
    seller_profiles.verification_badge,
    seller_profiles.ai_verified_farmer,
    seller_profiles.rsbsa_verified,

    COALESCE(
        (
            SELECT ROUND(
                (COUNT(*) * 100.0) / NULLIF(
                    (
                        SELECT COUNT(DISTINCT completed_items.order_id)
                        FROM order_items AS completed_items
                        INNER JOIN orders AS completed_orders
                            ON completed_orders.id = completed_items.order_id
                        WHERE completed_items.seller_profile_id = seller_profiles.id
                            AND completed_orders.status IN ('completed', 'delivered')
                    ),
                    0
                ),
                1
            )
            FROM complaints
            WHERE complaints.seller_profile_id = seller_profiles.id
        ),
        0
    ) AS complaint_rate,

    users.id AS seller_user_id,
    users.first_name,
    users.last_name,

COALESCE(

(
    SELECT ROUND(AVG(rating),1)
    FROM reviews
    WHERE product_id = products.id

),0) AS avg_rating,

    (
        SELECT COUNT(*)
        FROM reviews
        WHERE product_id = products.id
    ) AS review_count

FROM products

INNER JOIN seller_profiles
    ON seller_profiles.id = products.seller_profile_id

INNER JOIN users
    ON users.id = seller_profiles.user_id

LEFT JOIN categories
    ON categories.id = products.category_id

WHERE products.is_active = 1
    AND COALESCE(products.approval_status, 'approved') = 'approved'
    AND COALESCE(users.account_status, 'active') = 'active'

ORDER BY products.created_at DESC;

            ");

            $products = array_map(
                [$this, 'hydrateProductSellerLocation'],
                $productStmt->fetchAll()
            );

            $topFarmers = [
                'weekly' => $this->topFarmersForPeriod(7),
                'monthly' => $this->topFarmersForPeriod(30),
                'yearly' => $this->topFarmersForPeriod(365)
            ];
            $topFarmerIds = [];
            foreach ($topFarmers as $periodFarmers) {
                foreach ($periodFarmers as $farmer) {
                    $topFarmerIds[] = (int) $farmer->id;
                }
            }
            $topCustomersBySeller = CustomerStatisticsService::topCustomersForSellers($topFarmerIds, 3);

            $user = Session::get('user');
            $recommendationResult = RecommendationService::recommendProductsWithContext($user['id'] ?? 0, 8);
            $hasPersonalizedRecommendations = $recommendationResult['personalized'];
            $recommendedProducts = $hasPersonalizedRecommendations
                ? $recommendationResult['products']
                : [];
            $recommendedProducts = array_map(
                [$this, 'hydrateProductSellerLocation'],
                $recommendedProducts
            );

            $productImagesByProduct = [];
            $productReviewsByProduct = [];
            $productVariantsByProduct = [];
            $combinedProducts = array_merge($products, $recommendedProducts ?: []);

            if (!empty($combinedProducts)) {
                $productIds = array_unique(array_map(function ($product) {
                    return $product->id;
                }, $combinedProducts));

                $placeholders = implode(",", array_fill(0, count($productIds), "?"));
                $imagesStmt = $db->prepare("SELECT * FROM product_images WHERE product_id IN ($placeholders) ORDER BY display_order ASC, id ASC");
                $imagesStmt->execute($productIds);

                foreach ($imagesStmt->fetchAll() as $image) {
                    $productImagesByProduct[$image->product_id][] = $image;
                }

                $variantsStmt = $db->prepare("
                    SELECT id, product_id, variant_name, weight, unit,
                           retail_price, wholesale_price, minimum_wholesale_quantity, stock
                    FROM product_variants
                    WHERE product_id IN ($placeholders) AND is_active = 1
                    ORDER BY product_id, id
                ");
                $variantsStmt->execute($productIds);
                foreach ($variantsStmt->fetchAll() as $variant) {
                    $productVariantsByProduct[(int) $variant->product_id][] = $variant;
                }

                $reviewsStmt = $db->prepare("
                    SELECT reviews.*, users.first_name, users.last_name
                    FROM reviews
                    INNER JOIN users ON users.id = reviews.user_id
                    WHERE reviews.product_id IN ($placeholders)
                    ORDER BY reviews.created_at DESC, reviews.id DESC
                ");
                $reviewsStmt->execute($productIds);

                foreach ($reviewsStmt->fetchAll() as $review) {
                    $productReviewsByProduct[(int) $review->product_id][] = $review;
                }
            }

            return $this->view(
                'marketplace/index',
                [
                    'products'   => $products,
                    'categories' => $categories,
                    'topFarmers' => $topFarmers,
                    'topCustomersBySeller' => $topCustomersBySeller,
                    'recommendedProducts' => $recommendedProducts,
                    'hasPersonalizedRecommendations' => $hasPersonalizedRecommendations,
                    'productImagesByProduct' => $productImagesByProduct,
                    'productReviewsByProduct' => $productReviewsByProduct,
                    'productVariantsByProduct' => $productVariantsByProduct,
                    'pageCss' => '/ani-mart/public/assets/css/pages/marketplace.css',
                ]
            );
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    private function hydrateProductSellerLocation($product)
    {
        $product->farm_location = sensitive_value(
            $product,
            'farm_location',
            'seller_profiles.farm_location',
            $product->farm_location ?? ''
        );

        return hydrate_sensitive_fields($product, 'seller_profiles', [
            'barangay',
            'city',
        ]);
    }

    private function topFarmersForPeriod($days)
    {
        $db =
            Database::connection();

        $days =
            max(1, (int) $days);

        $sql = "
                SELECT
                    seller_profiles.id,
                    seller_profiles.shop_name,
                    seller_profiles.slug,
                    seller_profiles.barangay,
                    seller_profiles.city,
                    seller_profiles.farm_logo,
                    seller_profiles.trust_score,
                    seller_profiles.verification_badge,
                    users.first_name,
                    users.last_name,
                    COUNT(DISTINCT orders.id) AS total_orders,
                    SUM(order_items.quantity) AS total_items,
                    SUM(order_items.subtotal) AS total_sales,
                    (
                        SELECT COUNT(*)
                        FROM products
                        WHERE products.seller_profile_id = seller_profiles.id
                            AND products.is_active = 1
                    ) AS active_products,
                    COALESCE(
                        (
                            SELECT ROUND(AVG(reviews.rating), 1)
                            FROM reviews
                            INNER JOIN products
                                ON products.id = reviews.product_id
                            WHERE products.seller_profile_id = seller_profiles.id
                        ),
                        0
                    ) AS avg_rating
                FROM seller_profiles
                INNER JOIN users
                    ON users.id = seller_profiles.user_id
                INNER JOIN order_items
                    ON order_items.seller_profile_id = seller_profiles.id
                INNER JOIN orders
                    ON orders.id = order_items.order_id
                    AND orders.status IN ('completed', 'delivered')
                    AND COALESCE(orders.delivered_at, orders.created_at) >= DATE_SUB(NOW(), INTERVAL ? DAY)
                WHERE seller_profiles.status = 'approved'
                GROUP BY
                    seller_profiles.id,
                    seller_profiles.shop_name,
                    seller_profiles.slug,
                    seller_profiles.barangay,
                    seller_profiles.city,
                    seller_profiles.farm_logo,
                    seller_profiles.trust_score,
                    seller_profiles.verification_badge,
                    users.first_name,
                    users.last_name
                ORDER BY
                    total_sales DESC,
                    total_orders DESC,
                    total_items DESC,
                    avg_rating DESC,
                    seller_profiles.id DESC
                LIMIT 3
            ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(1, $days, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function stockSnapshot()
    {
        $rows = Database::connection()->query("
            SELECT
                p.id,
                CASE
                    WHEN COUNT(pv.id) = 1 THEN MAX(pv.stock)
                    ELSE p.stock
                END AS stock
            FROM products p
            LEFT JOIN product_variants pv
                ON pv.product_id = p.id AND pv.is_active = 1
            WHERE p.is_active = 1
                AND COALESCE(p.approval_status, 'approved') = 'approved'
            GROUP BY p.id, p.stock
        ")->fetchAll();

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo json_encode([
            'success' => true,
            'updated_at' => date(DATE_ATOM),
            'products' => array_map(static fn($row) => [
                'id' => (int) $row->id,
                'stock' => max(0, (int) $row->stock),
            ], $rows),
        ]);
        exit;
    }

    private function ensureSellerProfileSlugColumn()
    {
        SchemaInspector::requireColumns('seller_profiles', ['slug']);
        $db = Database::connection();

        $profiles =
            $db->query("
                SELECT id, shop_name
                FROM seller_profiles
                WHERE slug IS NULL
                    OR slug = ''
            ")->fetchAll();

        foreach ($profiles as $profile) {
            $slug = SlugService::uniqueSeller(
                $profile->shop_name ?? ('farm-' . $profile->id),
                (int) $profile->id,
                'farm-' . (int) $profile->id,
                $db
            );

            $stmt =
                $db->prepare("
                    UPDATE seller_profiles
                    SET slug = ?
                    WHERE id = ?
                ");

            $stmt->execute([
                $slug,
                $profile->id
            ]);
        }
    }
}
