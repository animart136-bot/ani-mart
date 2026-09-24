<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use PDOException;

class AnalyticsService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->connect();
    }

    /*
    |--------------------------------------------------------------------------
    | Total Sales
    |--------------------------------------------------------------------------
    */
    public function totalSales()
    {
        try {

            $stmt = $this->db->query(
                "
                SELECT
                    SUM(total_amount) AS total
                FROM orders
                WHERE status != 'cancelled'
                "
            );

            $result = $stmt->fetch();

            return $result->total ?? 0;

        } catch (PDOException $e) {

            error_log($e->getMessage());

            return 0;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Total Orders
    |--------------------------------------------------------------------------
    */
    public function totalOrders()
    {
        try {

            $stmt = $this->db->query(
                "
                SELECT
                    COUNT(*) AS total
                FROM orders
                "
            );

            $result = $stmt->fetch();

            return $result->total ?? 0;

        } catch (PDOException $e) {

            error_log($e->getMessage());

            return 0;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Total Farmers
    |--------------------------------------------------------------------------
    */
    public function totalFarmers()
    {
        try {

            $stmt = $this->db->query(
                "
                SELECT
                    COUNT(*) AS total
                FROM users
                WHERE role = 'farmer'
                "
            );

            $result = $stmt->fetch();

            return $result->total ?? 0;

        } catch (PDOException $e) {

            error_log($e->getMessage());

            return 0;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Total Customers
    |--------------------------------------------------------------------------
    */
    public function totalCustomers()
    {
        try {

            $stmt = $this->db->query(
                "
                SELECT
                    COUNT(*) AS total
                FROM users
                WHERE role = 'customer'
                "
            );

            $result = $stmt->fetch();

            return $result->total ?? 0;

        } catch (PDOException $e) {

            error_log($e->getMessage());

            return 0;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Total Products
    |--------------------------------------------------------------------------
    */
    public function totalProducts()
    {
        try {

            $stmt = $this->db->query(
                "
                SELECT
                    COUNT(*) AS total
                FROM products
                "
            );

            $result = $stmt->fetch();

            return $result->total ?? 0;

        } catch (PDOException $e) {

            error_log($e->getMessage());

            return 0;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Pending Orders
    |--------------------------------------------------------------------------
    */
    public function pendingOrders()
    {
        try {

            $stmt = $this->db->query(
                "
                SELECT
                    COUNT(*) AS total
                FROM orders
                WHERE status = 'pending'
                "
            );

            $result = $stmt->fetch();

            return $result->total ?? 0;

        } catch (PDOException $e) {

            error_log($e->getMessage());

            return 0;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Completed Orders
    |--------------------------------------------------------------------------
    */
    public function completedOrders()
    {
        try {

            $stmt = $this->db->query(
                "
                SELECT
                    COUNT(*) AS total
                FROM orders
                WHERE status = 'completed'
                "
            );

            $result = $stmt->fetch();

            return $result->total ?? 0;

        } catch (PDOException $e) {

            error_log($e->getMessage());

            return 0;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Monthly Sales
    |--------------------------------------------------------------------------
    */
    public function monthlySales()
    {
        try {

            $stmt = $this->db->query(
                "
                SELECT

                    MONTH(created_at) AS month,

                    SUM(total_amount) AS total

                FROM orders

                WHERE status != 'cancelled'

                GROUP BY MONTH(created_at)

                ORDER BY MONTH(created_at)
                "
            );

            return $stmt->fetchAll();

        } catch (PDOException $e) {

            error_log($e->getMessage());

            return [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Recent Orders
    |--------------------------------------------------------------------------
    */
    public function recentOrders()
    {
        try {

            $stmt = $this->db->query(
                "
                SELECT

                    orders.*,

                    users.first_name,
                    users.last_name

                FROM orders

                JOIN users
                ON users.id = orders.user_id

                ORDER BY orders.created_at DESC

                LIMIT 10
                "
            );

            return $stmt->fetchAll();

        } catch (PDOException $e) {

            error_log($e->getMessage());

            return [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Low Stock Products
    |--------------------------------------------------------------------------
    */
    public function lowStockProducts()
    {
        try {

            $stmt = $this->db->query(
                "
                SELECT *
                FROM products
                WHERE stock <= 5
                ORDER BY stock ASC
                "
            );

            return $stmt->fetchAll();

        } catch (PDOException $e) {

            error_log($e->getMessage());

            return [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Top Selling Products
    |--------------------------------------------------------------------------
    */
    public function topSellingProducts()
    {
        try {

            $stmt = $this->db->query(
                "
                SELECT

                    products.name,

                    SUM(order_items.quantity)
                    AS total_sold

                FROM order_items

                JOIN products
                ON products.id = order_items.product_id

                GROUP BY products.id

                ORDER BY total_sold DESC

                LIMIT 5
                "
            );

            return $stmt->fetchAll();

        } catch (PDOException $e) {

            error_log($e->getMessage());

            return [];
        }
    }
}