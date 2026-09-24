<?php

namespace App\Models;

use App\Core\Database;

class Order
{
    public static function count()
    {
        $db = Database::getInstance()
            ->connect();

        return $db->query("

            SELECT COUNT(*) total

            FROM orders

        ")->fetch()->total ?? 0;
    }

    public static function revenue()
    {
        $db = Database::getInstance()
            ->connect();

        return $db->query("

            SELECT
                COALESCE(
                    SUM(total_amount),
                    0
                ) total

            FROM orders

        ")->fetch()->total ?? 0;
    }

    public static function latest($limit = 5)
    {
        $db = Database::getInstance()
            ->connect();

        $stmt = $db->prepare("

            SELECT *

            FROM orders

            ORDER BY id DESC

            LIMIT ?

        ");

        $stmt->bindValue(
            1,
            (int)$limit,
            \PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function all()
    {
        $db =
            Database::getInstance()
            ->connect();

        return $db->query("

        SELECT

            orders.*,

            users.first_name,
            users.last_name,

            seller_profiles.shop_name

        FROM orders

        LEFT JOIN users
            ON users.id = orders.user_id

        LEFT JOIN order_items
            ON order_items.order_id = orders.id

        LEFT JOIN seller_profiles
            ON seller_profiles.id =
            order_items.seller_profile_id

        ORDER BY orders.id DESC

    ")->fetchAll();
    }
}
