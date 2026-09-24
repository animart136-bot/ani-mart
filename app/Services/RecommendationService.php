<?php

namespace App\Services;

use App\Core\Database;
use App\Models\FavoriteFarmer;
use App\Models\SellerProfile;
use App\Services\SmartPriceCheckService;

class RecommendationService
{
    public static function recommendProducts($customerId, $limit = 8)
    {
        return self::recommendProductsWithContext($customerId, $limit)['products'];
    }

    public static function recommendProductsWithContext($customerId, $limit = 8): array
    {
        $db = Database::connection();

        (new SmartPriceCheckService())->ensureSchema();

        $followedSellerIds = FavoriteFarmer::followedSellerIds($customerId);
        $favoriteCategoryIds = self::favoriteCategoryIds($customerId);
        $whereParams = [];
        $orderParams = [];
        $where = ['p.is_active = 1', "COALESCE(p.approval_status, 'approved') = 'approved'"];
        $personalizationWhere = [];
        $order = [];

        if (!empty($followedSellerIds)) {
            $placeholders = implode(',', array_fill(0, count($followedSellerIds), '?'));
            $personalizationWhere[] = "p.seller_profile_id IN ({$placeholders})";
            $whereParams = array_merge($whereParams, $followedSellerIds);
            $order[] = "CASE WHEN p.seller_profile_id IN ({$placeholders}) THEN 0 ELSE 1 END";
            $orderParams = array_merge($orderParams, $followedSellerIds);
        }

        if (!empty($favoriteCategoryIds)) {
            $placeholders = implode(',', array_fill(0, count($favoriteCategoryIds), '?'));
            $personalizationWhere[] = "p.category_id IN ({$placeholders})";
            $whereParams = array_merge($whereParams, $favoriteCategoryIds);
            $order[] = "CASE WHEN p.category_id IN ({$placeholders}) THEN 0 ELSE 1 END";
            $orderParams = array_merge($orderParams, $favoriteCategoryIds);
        }

        $order[] = 'p.stock DESC';
        $order[] = 'p.created_at DESC';

        $products = [];

        if (!empty($personalizationWhere)) {
            $sql = self::productSelectSql()
                . ' WHERE ' . implode(' AND ', $where)
                . ' AND (' . implode(' OR ', $personalizationWhere) . ')'
                . ' ORDER BY ' . implode(', ', $order) . ' LIMIT ?';
            $params = array_merge($whereParams, $orderParams, [$limit]);

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll();
        }

        if (count($products) < $limit) {
            $existingIds = array_map(fn ($product) => (int) $product->id, $products);
            $fallback = self::topProducts($db, $limit, $existingIds);
            foreach ($fallback as $product) {
                if (!in_array((int) $product->id, $existingIds, true)) {
                    $products[] = $product;

                    if (count($products) >= $limit) {
                        break;
                    }
                }
            }
        }

        return [
            'products' => $products,
            'personalized' => !empty($followedSellerIds) || !empty($favoriteCategoryIds),
        ];
    }

    public static function recommendFarmers($customerId, $limit = 6)
    {
        $db = Database::connection();

        $followedSellerIds = FavoriteFarmer::followedSellerIds($customerId);
        $hasFollowerTable = self::hasFollowerTable();

        $params = [];

        if ($hasFollowerTable) {
            $orderClauses = [];

            if (!empty($followedSellerIds)) {
                $placeholders = implode(',', array_fill(0, count($followedSellerIds), '?'));
                $orderClauses[] = "FIELD(seller_profiles.id, {$placeholders}) DESC";
                $params = array_merge($params, $followedSellerIds);
            }

            $orderClauses[] = 'COUNT(seller_followers.customer_id) DESC';
            $orderClauses[] = 'seller_profiles.trust_score DESC';
            $orderClauses[] = 'seller_profiles.id DESC';

            $sql = "SELECT seller_profiles.*, users.first_name, users.last_name, COUNT(seller_followers.customer_id) AS follower_count"
                . " FROM seller_profiles"
                . " INNER JOIN users ON users.id = seller_profiles.user_id"
                . " LEFT JOIN seller_followers ON seller_followers.seller_profile_id = seller_profiles.id"
                . " WHERE seller_profiles.status = 'approved'"
                . " GROUP BY seller_profiles.id"
                . " ORDER BY " . implode(', ', $orderClauses)
                . " LIMIT ?";
        } else {
            $sql = "SELECT seller_profiles.*, users.first_name, users.last_name, 0 AS follower_count"
                . " FROM seller_profiles"
                . " INNER JOIN users ON users.id = seller_profiles.user_id"
                . " WHERE seller_profiles.status = 'approved'"
                . " ORDER BY seller_profiles.trust_score DESC, seller_profiles.id DESC"
                . " LIMIT ?";
        }

        $params[] = $limit;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $farmers = $stmt->fetchAll();

        return array_map(
            fn ($farmer) => (object) array_merge(
                (array) $farmer,
                [
                    'is_following' => in_array((int) $farmer->id, $followedSellerIds, true)
                ]
            ),
            $farmers
        );
    }

    private static function hasFollowerTable()
    {
        $db = Database::connection();

        try {
            $stmt = $db->query("SHOW TABLES LIKE 'seller_followers'");
            return (bool) $stmt->fetch();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function recommendCategories($customerId, $limit = 5)
    {
        $db = Database::connection();

        $stmt = $db->prepare("SELECT categories.id, categories.name, COUNT(order_items.id) AS total_orders"
            . " FROM categories"
            . " INNER JOIN products ON products.category_id = categories.id"
            . " INNER JOIN order_items ON order_items.product_id = products.id"
            . " INNER JOIN orders ON orders.id = order_items.order_id"
            . " WHERE orders.user_id = ?"
            . " GROUP BY categories.id"
            . " ORDER BY total_orders DESC"
            . " LIMIT ?");

        $stmt->execute([$customerId, $limit]);
        $categories = $stmt->fetchAll();

        if (count($categories) === 0) {
            $stmt = $db->prepare("SELECT categories.id, categories.name, COUNT(products.id) AS total_products"
                . " FROM categories"
                . " LEFT JOIN products ON products.category_id = categories.id AND products.is_active = 1"
                . " GROUP BY categories.id"
                . " ORDER BY total_products DESC"
                . " LIMIT ?");

            $stmt->execute([$limit]);
            $categories = $stmt->fetchAll();
        }

        return $categories;
    }

    public static function recommendSeasonalProducts($limit = 5)
    {
        $db = Database::connection();

        $stmt = $db->prepare("SELECT p.*, categories.name AS category_name, seller_profiles.shop_name, seller_profiles.slug AS farm_slug,"
            . " COALESCE((SELECT ROUND(AVG(rating),1) FROM reviews WHERE product_id = p.id), 0) AS avg_rating,"
            . " (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) AS review_count"
            . " FROM products p"
            . " LEFT JOIN categories ON categories.id = p.category_id"
            . " LEFT JOIN seller_profiles ON seller_profiles.id = p.seller_profile_id"
            . " WHERE p.is_active = 1 AND p.harvest_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)"
            . " ORDER BY p.harvest_date ASC, p.stock DESC, p.created_at DESC"
            . " LIMIT ?");

        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    private static function favoriteCategoryIds($customerId)
    {
        $db = Database::connection();

        $stmt = $db->prepare("SELECT products.category_id AS category_id, MAX(order_items.id) AS latest_order_item_id"
            . " FROM order_items"
            . " INNER JOIN products ON products.id = order_items.product_id"
            . " INNER JOIN orders ON orders.id = order_items.order_id"
            . " WHERE orders.user_id = ?"
            . " AND products.category_id IS NOT NULL"
            . " GROUP BY products.category_id"
            . " ORDER BY latest_order_item_id DESC"
            . " LIMIT 6");

        $stmt->execute([$customerId]);

        $rows = $stmt->fetchAll();

        return array_map(
            fn ($row) => (int) $row->category_id,
            $rows
        );
    }

    private static function productSelectSql()
    {
        return "SELECT p.*, categories.name AS category_name, seller_profiles.shop_name, seller_profiles.farm_logo, seller_profiles.slug AS farm_slug, seller_profiles.shop_theme,"
            . " seller_profiles.user_id AS seller_user_id,"
            . " seller_profiles.farm_location, seller_profiles.farm_location_encrypted,"
            . " seller_profiles.barangay, seller_profiles.barangay_encrypted, seller_profiles.city, seller_profiles.city_encrypted,"
            . " seller_profiles.trust_score, seller_profiles.transparency_score, seller_profiles.verification_badge,"
            . " COALESCE((SELECT ROUND(AVG(rating),1) FROM reviews WHERE product_id = p.id), 0) AS avg_rating,"
            . " (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) AS review_count"
            . " FROM products p"
            . " LEFT JOIN categories ON categories.id = p.category_id"
            . " LEFT JOIN seller_profiles ON seller_profiles.id = p.seller_profile_id";
    }

    private static function topProducts($db, $limit, array $excludeIds = [])
    {
        $sql = self::productSelectSql()
            . " WHERE p.is_active = 1 AND COALESCE(p.approval_status, 'approved') = 'approved'";

        if (!empty($excludeIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $sql .= " AND p.id NOT IN ({$placeholders})";
        }

        $sql .= ' ORDER BY p.stock DESC, p.created_at DESC LIMIT ?';

        $params = array_merge($excludeIds, [$limit]);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
