<?php

namespace App\Services;

use App\Core\Database;
use PDOException;

class AdminAnalyticsService
{
    private $db;

    public function __construct()
    {
        $this->db =
            Database::connection();
    }

    public function dashboard()
    {
        return [
            'overview' =>
                $this->marketplaceOverview(),
            'sales' =>
                $this->salesAnalytics(),
            'products' =>
                $this->productAnalytics(),
            'farmers' =>
                $this->farmerAnalytics(),
            'customers' =>
                $this->customerAnalytics(),
            'complaints' =>
                $this->complaintAnalytics(),
            'transparency' =>
                $this->transparencyAnalytics(),
            'health' =>
                $this->marketplaceHealthScore(),
            'charts' =>
                $this->chartData()
        ];
    }

    private function marketplaceOverview()
    {
        return [
            'total_revenue' =>
                $this->value("
                    SELECT COALESCE(SUM(total_amount), 0)
                    FROM orders
                    WHERE LOWER(COALESCE(status, '')) IN ('completed', 'delivered')
                "),
            'total_orders' =>
                $this->value("
                    SELECT COUNT(*)
                    FROM orders
                "),
            'active_farmers' =>
                $this->value("
                    SELECT COUNT(*)
                    FROM seller_profiles
                    WHERE status = 'approved'
                "),
            'active_customers' =>
                $this->value("
                    SELECT COUNT(*)
                    FROM users
                    WHERE role IN ('customer', 'farmer')
                "),
            'active_products' =>
                $this->value("
                    SELECT COUNT(*)
                    FROM products
                    WHERE is_active = 1
                ")
        ];
    }

    private function salesAnalytics()
    {
        return [
            'revenue_today' =>
                $this->value("
                    SELECT COALESCE(SUM(total_amount), 0)
                    FROM orders
                    WHERE LOWER(COALESCE(status, '')) IN ('completed', 'delivered')
                        AND DATE(created_at) = CURDATE()
                "),
            'revenue_week' =>
                $this->value("
                    SELECT COALESCE(SUM(total_amount), 0)
                    FROM orders
                    WHERE LOWER(COALESCE(status, '')) IN ('completed', 'delivered')
                        AND YEARWEEK(created_at, 1) =
                            YEARWEEK(CURDATE(), 1)
                "),
            'revenue_month' =>
                $this->value("
                    SELECT COALESCE(SUM(total_amount), 0)
                    FROM orders
                    WHERE LOWER(COALESCE(status, '')) IN ('completed', 'delivered')
                        AND YEAR(created_at) = YEAR(CURDATE())
                        AND MONTH(created_at) = MONTH(CURDATE())
                "),
            'monthly_revenue_trend' =>
                $this->monthlyRevenueTrend()
        ];
    }

    private function productAnalytics()
    {
        return [
            'top_selling' =>
                $this->rows("
                    SELECT
                        products.name,
                        COALESCE(SUM(
                            CASE WHEN orders.id IS NOT NULL
                                THEN order_items.quantity ELSE 0 END
                        ), 0)
                            AS total_sold,
                        COALESCE(
                            SUM(CASE WHEN orders.id IS NOT NULL
                                THEN COALESCE(
                                    order_items.subtotal,
                                    order_items.price * order_items.quantity
                                )
                                ELSE 0 END),
                            0
                        ) AS revenue
                    FROM products
                    LEFT JOIN order_items
                        ON order_items.product_id = products.id
                    LEFT JOIN orders
                        ON orders.id = order_items.order_id
                        AND LOWER(COALESCE(orders.status, '')) IN ('completed', 'delivered')
                    GROUP BY products.id
                    ORDER BY total_sold DESC
                    LIMIT 10
                "),
            'lowest_selling' =>
                $this->rows("
                    SELECT
                        products.name,
                        COALESCE(SUM(
                            CASE WHEN orders.id IS NOT NULL
                                THEN order_items.quantity ELSE 0 END
                        ), 0)
                            AS total_sold
                    FROM products
                    LEFT JOIN order_items
                        ON order_items.product_id = products.id
                    LEFT JOIN orders
                        ON orders.id = order_items.order_id
                        AND LOWER(COALESCE(orders.status, '')) IN ('completed', 'delivered')
                    WHERE products.is_active = 1
                    GROUP BY products.id
                    ORDER BY total_sold ASC, products.created_at DESC
                    LIMIT 10
                "),
            'out_of_stock' =>
                $this->rows("
                    SELECT name, stock
                    FROM products
                    WHERE is_active = 1
                        AND stock <= 0
                    ORDER BY name ASC
                "),
            'near_out_of_stock' =>
                $this->rows("
                    SELECT name, stock
                    FROM products
                    WHERE is_active = 1
                        AND stock > 0
                        AND stock < 10
                    ORDER BY stock ASC
                ")
        ];
    }

    private function farmerAnalytics()
    {
        return [
            'highest_revenue' =>
                $this->rows("
                    SELECT
                        seller_profiles.shop_name,
                        users.first_name,
                        users.last_name,
                        COALESCE(
                            SUM(CASE WHEN orders.id IS NOT NULL
                                THEN COALESCE(
                                    order_items.subtotal,
                                    order_items.price * order_items.quantity
                                )
                                ELSE 0 END),
                            0
                        ) AS revenue
                    FROM seller_profiles
                    LEFT JOIN users
                        ON users.id = seller_profiles.user_id
                    LEFT JOIN order_items
                        ON order_items.seller_profile_id =
                        seller_profiles.id
                    LEFT JOIN orders
                        ON orders.id = order_items.order_id
                        AND LOWER(COALESCE(orders.status, '')) IN ('completed', 'delivered')
                    GROUP BY seller_profiles.id
                    ORDER BY revenue DESC
                    LIMIT 10
                "),
            'highest_rated' =>
                $this->highestRatedFarmers(),
            'most_complaints' =>
                $this->mostComplainedFarmers(),
            'highest_trust' =>
                $this->rows("
                    SELECT
                        seller_profiles.shop_name,
                        users.first_name,
                        users.last_name,
                        COALESCE(seller_profiles.trust_score, 0)
                            AS trust_score
                    FROM seller_profiles
                    LEFT JOIN users
                        ON users.id = seller_profiles.user_id
                    ORDER BY trust_score DESC
                    LIMIT 10
                "),
            'lowest_trust' =>
                $this->rows("
                    SELECT
                        seller_profiles.shop_name,
                        users.first_name,
                        users.last_name,
                        COALESCE(seller_profiles.trust_score, 0)
                            AS trust_score
                    FROM seller_profiles
                    LEFT JOIN users
                        ON users.id = seller_profiles.user_id
                    ORDER BY trust_score ASC
                    LIMIT 10
                ")
        ];
    }

    private function customerAnalytics()
    {
        return [
            'new_customers_month' =>
                $this->value("
                    SELECT COUNT(*)
                    FROM users
                    WHERE role IN ('customer', 'farmer')
                        AND YEAR(created_at) = YEAR(CURDATE())
                        AND MONTH(created_at) = MONTH(CURDATE())
                "),
            'repeat_customers' =>
                $this->value("
                    SELECT COUNT(*)
                    FROM (
                        SELECT user_id
                        FROM orders
                        WHERE LOWER(COALESCE(status, '')) IN ('completed', 'delivered')
                        GROUP BY user_id
                        HAVING COUNT(id) > 1
                    ) repeat_buyers
                "),
            'most_orders' =>
                $this->rows("
                    SELECT
                        users.first_name,
                        users.last_name,
                        users.email,
                        COUNT(orders.id) AS total_orders
                    FROM users
                    INNER JOIN orders
                        ON orders.user_id = users.id
                    WHERE users.role IN ('customer', 'farmer')
                        AND LOWER(COALESCE(orders.status, '')) IN ('completed', 'delivered')
                    GROUP BY users.id
                    ORDER BY total_orders DESC
                    LIMIT 10
                ")
        ];
    }

    private function complaintAnalytics()
    {
        if ($this->tableExists('complaints')) {
            return [
                'total' =>
                    $this->value("SELECT COUNT(*) FROM complaints"),
                'pending' =>
                    $this->value("
                        SELECT COUNT(*)
                        FROM complaints
                        WHERE status NOT IN ('resolved', 'rejected', 'closed')
                    "),
                'resolved' =>
                    $this->value("
                        SELECT COUNT(*)
                        FROM complaints
                        WHERE status IN ('resolved', 'closed')
                    "),
                'categories' =>
                    $this->rows("
                        SELECT COALESCE(category, 'Uncategorized') AS category,
                            COUNT(*) AS total
                        FROM complaints
                        GROUP BY COALESCE(category, 'Uncategorized')
                        ORDER BY total DESC
                    "),
                'most_reported_farmer' =>
                    $this->first("
                        SELECT seller_profiles.shop_name,
                            users.first_name,
                            users.last_name,
                            COUNT(complaints.id) AS total
                        FROM complaints
                        INNER JOIN seller_profiles
                            ON seller_profiles.id = complaints.seller_profile_id
                        LEFT JOIN users
                            ON users.id = seller_profiles.user_id
                        GROUP BY seller_profiles.id
                        ORDER BY total DESC
                        LIMIT 1
                    ")
            ];
        }

        if (!$this->tableExists('customer_complaints')) {
            return [
                'total' => 0,
                'pending' => 0,
                'resolved' => 0,
                'categories' => [],
                'most_reported_farmer' => null
            ];
        }

        return [
            'total' =>
                $this->value("
                    SELECT COUNT(*)
                    FROM customer_complaints
                "),
            'pending' =>
                $this->value("
                    SELECT COUNT(*)
                    FROM customer_complaints
                    WHERE status IN ('open', 'pending')
                "),
            'resolved' =>
                $this->value("
                    SELECT COUNT(*)
                    FROM customer_complaints
                    WHERE status = 'resolved'
                "),
            'categories' =>
                $this->rows("
                    SELECT
                        COALESCE(reason, 'Uncategorized') AS category,
                        COUNT(*) AS total
                    FROM customer_complaints
                    GROUP BY COALESCE(reason, 'Uncategorized')
                    ORDER BY total DESC
                "),
            'most_reported_farmer' =>
                $this->first("
                    SELECT
                        seller_profiles.shop_name,
                        users.first_name,
                        users.last_name,
                        COUNT(customer_complaints.id) AS total
                    FROM customer_complaints
                    INNER JOIN seller_profiles
                        ON seller_profiles.id =
                        customer_complaints.seller_profile_id
                    LEFT JOIN users
                        ON users.id = seller_profiles.user_id
                    GROUP BY seller_profiles.id
                    ORDER BY total DESC
                    LIMIT 1
                ")
        ];
    }

    private function transparencyAnalytics()
    {
        return [
            'harvest_changes_month' =>
                $this->value("
                    SELECT COUNT(*)
                    FROM product_activity_logs
                    WHERE action = 'Harvest Date Changed'
                        AND YEAR(created_at) = YEAR(CURDATE())
                        AND MONTH(created_at) = MONTH(CURDATE())
                "),
            'average_trust_score' =>
                $this->value("
                    SELECT COALESCE(AVG(trust_score), 0)
                    FROM seller_profiles
                "),
            'suspicious_farmers' =>
                $this->value("
                    SELECT COUNT(*)
                    FROM seller_profiles
                    WHERE COALESCE(risk_score, 0) >= 25
                        OR COALESCE(risk_level, 'low') IN ('medium', 'high')
                "),
            'verification_summary' =>
                $this->verificationSummary()
        ];
    }

    private function marketplaceHealthScore()
    {
        $orders =
            max(1, (int) $this->value("
                SELECT COUNT(*)
                FROM orders
                WHERE LOWER(COALESCE(status, '')) NOT IN ('cancelled', 'canceled')
            "));

        $complaints =
            (int) ($this->complaintAnalytics()['total'] ?? 0);

        $complaintComponent =
            max(0, 100 - (($complaints / $orders) * 100));

        $trustComponent =
            (float) $this->value("
                SELECT COALESCE(AVG(trust_score), 0)
                FROM seller_profiles
            ");

        $deliveryComponent =
            ((int) $this->value("
                SELECT COUNT(*)
                FROM orders
                WHERE LOWER(COALESCE(status, '')) IN ('completed', 'delivered')
            ") / $orders) * 100;

        $farmers =
            max(
                1,
                (int) $this->value("
                    SELECT COUNT(*)
                    FROM seller_profiles
                ")
            );

        $verifiedComponent =
            ((int) $this->value("
                SELECT COUNT(*)
                FROM seller_profiles
                WHERE COALESCE(status, 'approved') = 'approved'
            ") / $farmers) * 100;

        $products =
            max(
                1,
                (int) $this->value("SELECT COUNT(*) FROM products")
            );

        $activeProductComponent =
            ((int) $this->value("
                SELECT COUNT(*)
                FROM products
                WHERE is_active = 1
            ") / $products) * 100;

        $score =
            (int) round(
                (
                    $complaintComponent
                    + $trustComponent
                    + $deliveryComponent
                    + $verifiedComponent
                    + $activeProductComponent
                ) / 5
            );

        return [
            'score' =>
                max(0, min(100, $score)),
            'label' =>
                $this->healthLabel($score)
        ];
    }

    private function chartData()
    {
        $monthly =
            $this->monthlyRevenueTrend();

        return [
            'monthly_revenue' => [
                'labels' =>
                    array_map(fn ($row) => $row->period, $monthly),
                'values' =>
                    array_map(fn ($row) => (float) $row->revenue, $monthly)
            ],
            'orders_per_month' => [
                'labels' =>
                    array_map(fn ($row) => $row->period, $monthly),
                'values' =>
                    array_map(fn ($row) => (int) $row->orders, $monthly)
            ],
            'complaint_categories' =>
                $this->chartFromRows(
                    $this->complaintAnalytics()['categories'] ?? [],
                    'category',
                    'total'
                ),
            'farmer_trust_distribution' =>
                $this->farmerTrustDistribution(),
            'top_selling_categories' =>
                $this->topSellingCategories()
        ];
    }

    private function monthlyRevenueTrend()
    {
        return $this->rows("
            SELECT
                DATE_FORMAT(created_at, '%b %Y') AS period,
                COALESCE(SUM(total_amount), 0) AS revenue,
                COUNT(id) AS orders
            FROM orders
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
                AND LOWER(COALESCE(status, '')) IN ('completed', 'delivered')
            GROUP BY YEAR(created_at), MONTH(created_at)
            ORDER BY YEAR(created_at), MONTH(created_at)
        ");
    }

    private function highestRatedFarmers()
    {
        if (!$this->tableExists('reviews')) {
            return [];
        }

        return $this->rows("
            SELECT
                seller_profiles.shop_name,
                users.first_name,
                users.last_name,
                COALESCE(AVG(reviews.rating), 0) AS rating
            FROM seller_profiles
            LEFT JOIN users
                ON users.id = seller_profiles.user_id
            INNER JOIN products
                ON products.seller_profile_id = seller_profiles.id
            INNER JOIN reviews
                ON reviews.product_id = products.id
            GROUP BY seller_profiles.id
            ORDER BY rating DESC
            LIMIT 10
        ");
    }

    private function mostComplainedFarmers()
    {
        if ($this->tableExists('complaints')) {
            return $this->rows("
                SELECT
                    seller_profiles.shop_name,
                    users.first_name,
                    users.last_name,
                    COUNT(complaints.id) AS complaints
                FROM complaints
                INNER JOIN seller_profiles
                    ON seller_profiles.id = complaints.seller_profile_id
                LEFT JOIN users
                    ON users.id = seller_profiles.user_id
                GROUP BY seller_profiles.id
                ORDER BY complaints DESC
                LIMIT 10
            ");
        }

        if (!$this->tableExists('customer_complaints')) {
            return [];
        }

        return $this->rows("
            SELECT
                seller_profiles.shop_name,
                users.first_name,
                users.last_name,
                COUNT(customer_complaints.id) AS complaints
            FROM customer_complaints
            INNER JOIN seller_profiles
                ON seller_profiles.id =
                customer_complaints.seller_profile_id
            LEFT JOIN users
                ON users.id = seller_profiles.user_id
            GROUP BY seller_profiles.id
            ORDER BY complaints DESC
            LIMIT 10
        ");
    }

    private function verificationSummary()
    {
        $verified =
            $this->value("
                SELECT COUNT(*)
                FROM products
                WHERE is_active = 1
                    AND harvest_date IS NOT NULL
                    AND harvest_photo IS NOT NULL
                    AND harvest_photo != ''
            ");

        $unverified =
            $this->value("
                SELECT COUNT(*)
                FROM products
                WHERE is_active = 1
                    AND (
                        harvest_date IS NULL
                        OR harvest_photo IS NULL
                        OR harvest_photo = ''
                    )
            ");

        return [
            'verified' =>
                (int) $verified,
            'unverified' =>
                (int) $unverified
        ];
    }

    private function farmerTrustDistribution()
    {
        $rows = $this->rows("
            SELECT
                CASE
                    WHEN COALESCE(trust_score, 0) >= 90 THEN '90-100'
                    WHEN COALESCE(trust_score, 0) >= 80 THEN '80-89'
                    WHEN COALESCE(trust_score, 0) >= 60 THEN '60-79'
                    ELSE 'Below 60'
                END AS bucket,
                COUNT(*) AS total
            FROM seller_profiles
            GROUP BY bucket
            ORDER BY bucket DESC
        ");

        return $this->chartFromRows(
            $rows,
            'bucket',
            'total'
        );
    }

    private function topSellingCategories()
    {
        $rows = $this->rows("
            SELECT
                categories.name AS category,
                COALESCE(SUM(
                    CASE WHEN orders.id IS NOT NULL
                        THEN order_items.quantity ELSE 0 END
                ), 0) AS total
            FROM categories
            LEFT JOIN products
                ON products.category_id = categories.id
            LEFT JOIN order_items
                ON order_items.product_id = products.id
            LEFT JOIN orders
                ON orders.id = order_items.order_id
                AND LOWER(COALESCE(orders.status, '')) IN ('completed', 'delivered')
            GROUP BY categories.id
            ORDER BY total DESC
            LIMIT 10
        ");

        return $this->chartFromRows(
            $rows,
            'category',
            'total'
        );
    }

    private function chartFromRows($rows, $labelField, $valueField)
    {
        return [
            'labels' =>
                array_map(
                    fn ($row) => (string) ($row->{$labelField} ?? ''),
                    $rows
                ),
            'values' =>
                array_map(
                    fn ($row) => (float) ($row->{$valueField} ?? 0),
                    $rows
                )
        ];
    }

    private function healthLabel($score)
    {
        if ($score >= 85) {
            return 'Excellent';
        }

        if ($score >= 70) {
            return 'Good';
        }

        if ($score >= 50) {
            return 'Needs Attention';
        }

        return 'Critical';
    }

    private function value($sql)
    {
        try {

            return $this->db->query($sql)
                ->fetchColumn() ?: 0;

        } catch (PDOException $e) {

            return 0;
        }
    }

    private function first($sql)
    {
        try {

            return $this->db->query($sql)
                ->fetch() ?: null;

        } catch (PDOException $e) {

            return null;
        }
    }

    private function rows($sql)
    {
        try {

            return $this->db->query($sql)
                ->fetchAll();

        } catch (PDOException $e) {

            return [];
        }
    }

    private function tableExists($table)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
        ");

        $stmt->execute([$table]);

        return ((int) $stmt->fetchColumn()) > 0;
    }
}
