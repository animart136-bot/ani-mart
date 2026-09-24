<?php

namespace App\Models;

use App\Core\Database;

class Product
{
    public static function marketplace()
    {
        $db = Database::getInstance()
            ->connect();

        (new \App\Services\SmartPriceCheckService())->ensureSchema();
        (new \App\Services\AccountControlService())->ensureSchema();

        $stmt = $db->query("

            SELECT

                products.*,

                categories.name AS category_name,

                users.id AS seller_user_id,
                users.first_name,
                users.last_name,

                COALESCE(
                    ROUND(
                        AVG(reviews.rating),
                        1
                    ),
                    0
                ) AS avg_rating,

                COUNT(reviews.id)
                AS review_count

            FROM products

            LEFT JOIN categories
                ON categories.id = products.category_id

            LEFT JOIN seller_profiles
                ON seller_profiles.id = products.seller_profile_id

            LEFT JOIN users
                ON users.id = seller_profiles.user_id

            LEFT JOIN reviews
                ON reviews.product_id = products.id

            WHERE products.is_active = 1
                AND COALESCE(products.approval_status, 'approved') = 'approved'
                AND COALESCE(users.account_status, 'active') = 'active'

            GROUP BY products.id

            ORDER BY products.created_at DESC

        ");

        return $stmt->fetchAll();
    }

    public static function categories()
    {
        $db = Database::getInstance()
            ->connect();

        (new \App\Services\CategoryAdminService())->ensureSchema();

        return $db->query("

            SELECT *

            FROM categories

            WHERE is_active = 1

            ORDER BY name ASC

        ")->fetchAll();
    }

    public static function find($id)
{
    $db = \App\Core\Database::getInstance()
        ->connect();

    $stmt = $db->prepare("

        SELECT *

        FROM products

        WHERE id = ?

        LIMIT 1

    ");

    $stmt->execute([
        $id
    ]);

    return $stmt->fetch();
}
}
