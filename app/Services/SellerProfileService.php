<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Session;

class SellerProfileService
{
    public static function themes(): array
    {
        return [
            'fresh-farm' => ['label' => 'Fresh Farm', 'accent' => '#2F7D32', 'secondary' => '#A8D5A2', 'background' => '#F2F8F4', 'text' => '#183C2A'],
            'harvest-gold' => ['label' => 'Harvest Gold', 'accent' => '#C47A17', 'secondary' => '#F2C866', 'background' => '#FFF8E8', 'text' => '#49360D'],
            'tomato-harvest' => ['label' => 'Tomato Blush', 'accent' => '#D94A3A', 'secondary' => '#F28A7E', 'background' => '#FFF3F1', 'text' => '#4A1D18'],
            'nature-blue' => ['label' => 'Nature Blue', 'accent' => '#2878A8', 'secondary' => '#78B8D4', 'background' => '#F0F8FC', 'text' => '#183746'],
            'mint-garden' => ['label' => 'Mint Garden', 'accent' => '#2A9D8F', 'secondary' => '#83D6C9', 'background' => '#EFFAF8', 'text' => '#174C46'],
            'carrot-orange' => ['label' => 'Carrot Orange', 'accent' => '#E76F27', 'secondary' => '#F5B27A', 'background' => '#FFF5ED', 'text' => '#542A12'],
            'berry-garden' => ['label' => 'Berry Garden', 'accent' => '#7952A8', 'secondary' => '#B89AD8', 'background' => '#F7F2FB', 'text' => '#30203F'],
            'olive-grove' => ['label' => 'Olive Grove', 'accent' => '#687A2E', 'secondary' => '#B8C77A', 'background' => '#F6F8EC', 'text' => '#343D18'],
            'peach-blossom' => ['label' => 'Peach Blossom', 'accent' => '#E58F65', 'secondary' => '#F3C0A5', 'background' => '#FFF6F1', 'text' => '#542E20'],
            'earthen-farm' => ['label' => 'Earthen Farm', 'accent' => '#8A6845', 'secondary' => '#CDB28F', 'background' => '#F8F3EC', 'text' => '#3F3021'],
            'sunny-orange' => ['label' => 'Sunny Orange', 'accent' => '#E47B27', 'secondary' => '#F2B36D', 'background' => '#FFF6EC', 'text' => '#4A2A12'],
            'harvest' => ['label' => 'Harvest', 'accent' => '#a65316', 'secondary' => '#fff0d6', 'background' => '#fff8ef', 'text' => '#49360D'],
            'market-garden' => ['label' => 'Market Garden', 'accent' => '#176b87', 'secondary' => '#e1f2f4', 'background' => '#F0F8FC', 'text' => '#183746'],
            'minimal' => ['label' => 'Minimal', 'accent' => '#364152', 'secondary' => '#eef1f4', 'background' => '#f5f6f8', 'text' => '#26313f'],
        ];
    }

    public static function availableProducts($sellerProfileId)
    {
        $db = Database::getInstance()->connect();
        $stmt = $db->prepare("SELECT id, name, image FROM products WHERE seller_profile_id = ? AND is_active = 1 AND COALESCE(approval_status, 'approved') = 'approved' ORDER BY name ASC");
        $stmt->execute([$sellerProfileId]);
        return $stmt->fetchAll();
    }

    public static function featuredProducts($sellerProfileId)
    {
        $db = Database::getInstance()->connect();
        $stmt = $db->prepare("SELECT p.*, c.name AS category_name, fp.display_order, COALESCE((SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.product_id = p.id), 0) AS avg_rating, (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) AS review_count FROM seller_featured_products fp INNER JOIN products p ON p.id = fp.product_id LEFT JOIN categories c ON c.id = p.category_id WHERE fp.seller_profile_id = ? AND p.seller_profile_id = ? AND p.is_active = 1 AND COALESCE(p.approval_status, 'approved') = 'approved' ORDER BY fp.display_order ASC, fp.id ASC");
        $stmt->execute([$sellerProfileId, $sellerProfileId]);
        return $stmt->fetchAll();
    }

    public static function saveFeaturedProducts($sellerProfileId, array $productIds, int $limit = 6): void
    {
        $db = Database::getInstance()->connect();
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        $productIds = array_slice($productIds, 0, $limit);

        $db->beginTransaction();
        try {
            $delete = $db->prepare('DELETE FROM seller_featured_products WHERE seller_profile_id = ?');
            $delete->execute([$sellerProfileId]);

            $insert = $db->prepare("INSERT INTO seller_featured_products (seller_profile_id, product_id, display_order) SELECT ?, id, ? FROM products WHERE id = ? AND seller_profile_id = ? AND is_active = 1 AND COALESCE(approval_status, 'approved') = 'approved'");
            foreach ($productIds as $order => $productId) {
                $insert->execute([$sellerProfileId, $order, $productId, $sellerProfileId]);
            }
            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public static function analytics(int $sellerProfileId, string $startDate, string $endDate): array
    {
        $db = Database::getInstance()->connect();
        $completedStatuses = "('completed', 'delivered')";
        $from = $startDate . ' 00:00:00';
        $to = $endDate . ' 23:59:59';

        $financialRecords = self::financialRecords($sellerProfileId, $startDate, $endDate);
        $summary = (object) [
            'total_orders' => 0,
            'all_orders' => count($financialRecords),
            'gross_sales' => 0.0,
            'total_revenue' => 0.0,
            'cancelled_orders' => 0,
            'cancelled_order_value' => 0.0,
            'returned_orders' => 0,
            'refunded_orders' => 0,
            'refunded_amount' => 0.0,
            'delivery_fees' => 0.0,
            'net_earnings' => 0.0,
            'products_purchased' => 0,
            'unique_customers' => 0,
        ];
        $completedCustomers = [];
        foreach ($financialRecords as $record) {
            $summary->gross_sales += (float) $record->gross_sales;
            $summary->cancelled_order_value += (float) $record->cancelled_value;
            $summary->refunded_amount += (float) $record->refunded_amount;
            $summary->delivery_fees += (float) $record->delivery_fees;
            $summary->net_earnings += (float) $record->net_earnings;
            if (in_array((string) $record->status, ['delivered', 'completed'], true)) {
                $summary->total_orders++;
                $summary->products_purchased += (int) $record->units;
                $completedCustomers[(int) $record->customer_id] = true;
            }
            if ((string) $record->status === 'cancelled') $summary->cancelled_orders++;
            if (!empty($record->return_request_id)) $summary->returned_orders++;
            if ((float) $record->refunded_amount > 0) $summary->refunded_orders++;
        }
        $summary->total_revenue = $summary->gross_sales;
        $summary->unique_customers = count($completedCustomers);

        $trend = $db->prepare("SELECT DATE(o.created_at) AS sale_date, COALESCE(SUM(oi.price * oi.quantity), 0) AS revenue, COALESCE(SUM(oi.quantity), 0) AS units, COUNT(DISTINCT o.id) AS orders FROM orders o INNER JOIN order_items oi ON oi.order_id = o.id WHERE oi.seller_profile_id = ? AND o.status IN {$completedStatuses} AND o.created_at BETWEEN ? AND ? GROUP BY DATE(o.created_at) ORDER BY sale_date ASC");
        $trend->execute([$sellerProfileId, $from, $to]);

        $topProducts = $db->prepare("SELECT p.name, p.stock, COALESCE(sales.units, 0) AS units, COALESCE(sales.revenue, 0) AS revenue FROM products p LEFT JOIN (SELECT oi.product_id, SUM(oi.quantity) AS units, SUM(oi.price * oi.quantity) AS revenue FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id WHERE oi.seller_profile_id = ? AND o.status IN {$completedStatuses} AND o.created_at BETWEEN ? AND ? GROUP BY oi.product_id) sales ON sales.product_id = p.id WHERE p.seller_profile_id = ? AND p.is_active = 1 ORDER BY units DESC, revenue DESC, p.name ASC LIMIT 8");
        $topProducts->execute([$sellerProfileId, $from, $to, $sellerProfileId]);

        $rating = $db->prepare("SELECT COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating, COUNT(r.id) AS review_count FROM reviews r INNER JOIN products p ON p.id = r.product_id WHERE p.seller_profile_id = ? AND r.created_at BETWEEN ? AND ?");
        $rating->execute([$sellerProfileId, $from, $to]);

        $customers = $db->prepare("SELECT COUNT(DISTINCT o.user_id) AS unique_customers, COUNT(DISTINCT CASE WHEN customer_orders.order_count >= 2 THEN o.user_id END) AS returning_customers FROM orders o INNER JOIN order_items oi ON oi.order_id = o.id LEFT JOIN (SELECT o2.user_id, COUNT(DISTINCT o2.id) AS order_count FROM orders o2 INNER JOIN order_items oi2 ON oi2.order_id = o2.id WHERE oi2.seller_profile_id = ? AND o2.status IN {$completedStatuses} AND o2.created_at BETWEEN ? AND ? GROUP BY o2.user_id) customer_orders ON customer_orders.user_id = o.user_id WHERE oi.seller_profile_id = ? AND o.status IN {$completedStatuses} AND o.created_at BETWEEN ? AND ?");
        $customers->execute([$sellerProfileId, $from, $to, $sellerProfileId, $from, $to]);

        $complaints = $db->prepare("SELECT status, COUNT(*) AS total FROM complaints WHERE seller_profile_id = ? AND created_at BETWEEN ? AND ? GROUP BY status ORDER BY total DESC");
        $complaints->execute([$sellerProfileId, $from, $to]);

        return [
            'summary' => $summary,
            'rating' => $rating->fetch() ?: (object) [],
            'trend' => $trend->fetchAll(),
            'top_products' => $topProducts->fetchAll(),
            'customers' => $customers->fetch() ?: (object) [],
            'complaints' => $complaints->fetchAll(),
            'financial_records' => $financialRecords,
        ];
    }

    public static function financialRecords(int $sellerProfileId, string $startDate, string $endDate): array
    {
        (new OrderWorkflowSchemaService())->ensure();
        $db = Database::getInstance()->connect();
        $stmt = $db->prepare("SELECT
                o.id,
                o.order_number,
                o.created_at,
                o.status,
                o.payment_method,
                o.user_id AS customer_id,
                CONCAT(u.first_name, ' ', u.last_name) AS customer_name,
                SUM(oi.quantity) AS units,
                SUM(oi.price * oi.quantity) AS product_value,
                CASE WHEN o.status IN ('delivered','completed') THEN SUM(oi.price * oi.quantity) ELSE 0 END AS gross_sales,
                CASE WHEN o.status = 'cancelled' THEN SUM(oi.price * oi.quantity) + COALESCE(MAX(o.delivery_fee), 0) ELSE 0 END AS cancelled_value,
                CASE WHEN o.status IN ('delivered','completed') THEN COALESCE(MAX(o.delivery_fee), 0) ELSE 0 END AS delivery_fees,
                COALESCE(MAX(rr.refunded_amount), 0) AS refunded_amount,
                MAX(rr.id) AS return_request_id,
                MAX(rr.status) AS return_status
            FROM orders o
            INNER JOIN order_items oi ON oi.order_id = o.id
            LEFT JOIN users u ON u.id = o.user_id
            LEFT JOIN return_requests rr ON rr.order_id = o.id AND rr.seller_profile_id = oi.seller_profile_id
            WHERE oi.seller_profile_id = ? AND o.created_at BETWEEN ? AND ?
            GROUP BY o.id, o.order_number, o.created_at, o.status, o.payment_method, o.user_id, u.first_name, u.last_name
            ORDER BY o.created_at DESC, o.id DESC");
        $stmt->execute([$sellerProfileId, $startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $records = $stmt->fetchAll();
        foreach ($records as $record) {
            $record->net_earnings = self::calculateNetEarnings(
                (float) $record->gross_sales,
                (float) $record->delivery_fees,
                (float) $record->refunded_amount
            );
        }
        return $records;
    }

    public static function calculateNetEarnings(float $grossSales, float $deliveryFees, float $refundedAmount): float
    {
        return round($grossSales + $deliveryFees - $refundedAmount, 2);
    }

    public static function activeShopId()
    {
        return Session::get('active_shop_id');
    }

    public static function setActiveShopId($shopId)
    {
        if ($shopId === null || $shopId === '') {
            Session::remove('active_shop_id');
            return;
        }

        Session::set('active_shop_id', (int) $shopId);
    }

    public static function userShops($userId)
    {
        $db = Database::getInstance()->connect();

        $stmt = $db->prepare("SELECT * FROM seller_profiles WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);

        $shops = $stmt->fetchAll();

        return array_map([self::class, 'hydrateProfile'], $shops);
    }

    public static function activeShop($userId = null)
    {
        if ($userId === null) {
            $user = Session::get('user');
            $userId = $user['id'] ?? null;
        }

        if (!$userId) {
            return null;
        }

        $activeShopId = self::activeShopId();

        if ($activeShopId) {
            $shop = self::findByUserAndId($userId, $activeShopId);
            if ($shop) {
                return self::hydrateProfile($shop);
            }
        }

        $defaultShop = self::defaultShop($userId);

        if ($defaultShop) {
            self::setActiveShopId($defaultShop->id);
            return self::hydrateProfile($defaultShop);
        }

        return null;
    }

    public static function defaultShop($userId)
    {
        $db = Database::getInstance()->connect();

        $stmt = $db->prepare("SELECT * FROM seller_profiles WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$userId]);

        $shop = $stmt->fetch();

        return $shop ? self::hydrateProfile($shop) : null;
    }

    public static function findByUserAndId($userId, $shopId)
    {
        $db = Database::getInstance()->connect();

        $stmt = $db->prepare("SELECT * FROM seller_profiles WHERE user_id = ? AND id = ? LIMIT 1");
        $stmt->execute([$userId, $shopId]);

        $shop = $stmt->fetch();

        return $shop ? self::hydrateProfile($shop) : null;
    }

    public static function isOwnedByUser($shopId, $userId)
    {
        if (!$shopId || !$userId) {
            return false;
        }

        return (bool) self::findByUserAndId($userId, $shopId);
    }

    private static function hydrateProfile($shop)
    {
        if (!$shop) {
            return null;
        }

        $shop->farm_location = sensitive_value(
            $shop,
            'farm_location',
            'seller_profiles.farm_location',
            $shop->farm_location ?? null
        );
        $shop->contact_number = sensitive_value(
            $shop,
            'contact_number',
            'seller_profiles.contact_number',
            $shop->contact_number ?? null
        );
        $shop = hydrate_sensitive_fields($shop, 'seller_profiles', [
            'latitude', 'longitude', 'barangay', 'city', 'government_id',
        ]);
        $shop->logo = $shop->logo ?? $shop->farm_logo ?? null;
        $shop->banner = $shop->banner ?? $shop->farm_banner ?? null;
        $shop->address = $shop->address ?? $shop->farm_location ?? null;
        $shop->verification_status = $shop->verification_status ?? $shop->status ?? 'pending';
        $shop->badge = $shop->badge ?? $shop->verification_badge ?? 'new';
        $shop->is_active = isset($shop->is_active) ? (bool) $shop->is_active : true;

        return $shop;
    }
}
