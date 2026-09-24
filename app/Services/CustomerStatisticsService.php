<?php

namespace App\Services;

use App\Core\Database;

class CustomerStatisticsService
{
    private const COMPLETED_ORDER_STATUSES = "('completed', 'delivered')";

    public static function topCustomersForSellers(array $sellerIds, int $limit = 3): array
    {
        $sellerIds = array_values(array_unique(array_filter(array_map('intval', $sellerIds))));
        $limit = max(1, min(10, $limit));

        if (empty($sellerIds)) {
            return [];
        }

        $db = Database::connection();
        $placeholders = implode(',', array_fill(0, count($sellerIds), '?'));
        $stmt = $db->prepare("
            SELECT
                oi.seller_profile_id AS seller_id,
                u.id AS customer_id,
                u.first_name,
                LEFT(TRIM(u.last_name), 1) AS last_initial,
                COUNT(DISTINCT o.id) AS completed_orders,
                COALESCE(SUM(oi.quantity), 0) AS products_purchased
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            INNER JOIN users u ON u.id = o.user_id
            WHERE oi.seller_profile_id IN ({$placeholders})
                AND o.status IN " . self::COMPLETED_ORDER_STATUSES . "
                AND LOWER(COALESCE(u.role, 'customer')) = 'customer'
            GROUP BY oi.seller_profile_id, u.id, u.first_name, u.last_name
            ORDER BY oi.seller_profile_id ASC, completed_orders DESC, products_purchased DESC, u.id ASC
        ");
        $stmt->execute($sellerIds);

        $grouped = [];
        foreach ($stmt->fetchAll() as $customer) {
            $sellerId = (int) $customer->seller_id;
            if (!isset($grouped[$sellerId])) {
                $grouped[$sellerId] = [
                    'top_customers' => [],
                    'total_customers' => 0,
                ];
            }

            $grouped[$sellerId]['total_customers']++;
            if (count($grouped[$sellerId]['top_customers']) < $limit) {
                $grouped[$sellerId]['top_customers'][] = $customer;
            }
        }

        return $grouped;
    }

    public static function forSeller(int $sellerProfileId): array
    {
        if ($sellerProfileId <= 0) {
            return self::emptyStats();
        }

        $db = Database::connection();
        $stmt = $db->prepare("
            SELECT
                COUNT(DISTINCT o.user_id) AS unique_customers,
                COALESCE(SUM(oi.quantity), 0) AS products_purchased,
                COUNT(DISTINCT o.id) AS completed_orders,
                COUNT(DISTINCT CASE WHEN customer_orders.order_count >= 2 THEN o.user_id END) AS returning_customers
            FROM orders o
            INNER JOIN order_items oi ON oi.order_id = o.id
            LEFT JOIN (
                SELECT o2.user_id, COUNT(DISTINCT o2.id) AS order_count
                FROM orders o2
                INNER JOIN order_items oi2 ON oi2.order_id = o2.id
                WHERE oi2.seller_profile_id = ?
                    AND o2.status IN " . self::COMPLETED_ORDER_STATUSES . "
                GROUP BY o2.user_id
            ) customer_orders ON customer_orders.user_id = o.user_id
            WHERE oi.seller_profile_id = ?
                AND o.status IN " . self::COMPLETED_ORDER_STATUSES . "
        ");
        $stmt->execute([$sellerProfileId, $sellerProfileId]);
        $stats = $stmt->fetch();

        return [
            'unique_customers' => (int) ($stats->unique_customers ?? 0),
            'products_purchased' => (int) ($stats->products_purchased ?? 0),
            'completed_orders' => (int) ($stats->completed_orders ?? 0),
            'returning_customers' => (int) ($stats->returning_customers ?? 0),
        ];
    }

    private static function emptyStats(): array
    {
        return [
            'unique_customers' => 0,
            'products_purchased' => 0,
            'completed_orders' => 0,
            'returning_customers' => 0,
        ];
    }
}
