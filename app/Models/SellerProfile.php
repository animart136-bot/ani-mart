<?php

namespace App\Models;

use App\Core\Database;

class SellerProfile
{
    public static function all()
    {
        $db = Database::getInstance()
            ->connect();

        return $db->query("

            SELECT
                seller_profiles.*,
                users.first_name,
                users.last_name,
                users.email

            FROM seller_profiles

            LEFT JOIN users
                ON users.id = seller_profiles.user_id

            ORDER BY seller_profiles.id DESC

        ")->fetchAll();
    }

    public static function count()
    {
        $db = Database::getInstance()
            ->connect();

        return $db->query("

            SELECT COUNT(*) total

            FROM seller_profiles

        ")->fetch()->total ?? 0;
    }

    public static function recent($limit = 5)
{
    $db = Database::getInstance()
        ->connect();

    $stmt = $db->prepare("

        SELECT
            seller_profiles.*,
            users.first_name,
            users.last_name

        FROM seller_profiles

        LEFT JOIN users
            ON users.id = seller_profiles.user_id

        ORDER BY seller_profiles.id DESC

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

    public static function findByUserId($userId)
    {
        $db = Database::getInstance()
            ->connect();

        $stmt = $db->prepare("
            SELECT *
            FROM seller_profiles
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");

        $stmt->execute([
            $userId
        ]);

        return $stmt->fetch();
    }

    public static function findByUserAndId($userId, $id)
    {
        $db = Database::getInstance()
            ->connect();

        $stmt = $db->prepare("
            SELECT *
            FROM seller_profiles
            WHERE user_id = ?
                AND id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $userId,
            $id
        ]);

        return $stmt->fetch();
    }

    public static function findById($id)
    {
        $db = Database::getInstance()
            ->connect();

        $stmt = $db->prepare("
            SELECT *
            FROM seller_profiles
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $id
        ]);

        return $stmt->fetch();
    }

    public static function findPublicBySlug($slug)
    {
        $db = Database::getInstance()
            ->connect();

        $slug =
            trim((string) $slug);

        $where =
            'seller_profiles.slug = ?';

        $params = [
            $slug
        ];

        if (ctype_digit($slug)) {
            $where =
                '(seller_profiles.slug = ? OR seller_profiles.id = ?)';

            $params[] =
                (int) $slug;
        }

        $stmt = $db->prepare("
            SELECT
                seller_profiles.*,
                users.first_name,
                users.last_name,
                users.email,
                users.created_at AS user_created_at
            FROM seller_profiles
            INNER JOIN users
                ON users.id = seller_profiles.user_id
            WHERE {$where}
                AND seller_profiles.status = 'approved'
                AND seller_profiles.is_active = 1
            LIMIT 1
        ");

        $stmt->execute($params);

        return $stmt->fetch();
    }

    public static function gallery($sellerProfileId, $type)
    {
        $db = Database::getInstance()
            ->connect();

        $stmt = $db->prepare("
            SELECT *
            FROM farm_profile_images
            WHERE seller_profile_id = ?
                AND image_type = ?
            ORDER BY sort_order ASC, id DESC
        ");

        $stmt->execute([
            $sellerProfileId,
            $type
        ]);

        return $stmt->fetchAll();
    }

    public static function products($sellerProfileId)
    {
        $db = Database::getInstance()
            ->connect();

        $stmt = $db->prepare("
            SELECT
                products.*,
                categories.name AS category_name,
                COALESCE(
                    (SELECT ROUND(AVG(rating), 1)
                     FROM reviews
                     WHERE product_id = products.id),
                    0
                ) AS avg_rating,
                (SELECT COUNT(*)
                 FROM reviews
                 WHERE product_id = products.id) AS review_count
            FROM products
            LEFT JOIN categories
                ON categories.id = products.category_id
            WHERE products.seller_profile_id = ?
                AND products.is_active = 1
                AND COALESCE(products.approval_status, 'approved') = 'approved'
            ORDER BY products.created_at DESC
        ");

        $stmt->execute([
            $sellerProfileId
        ]);

        return $stmt->fetchAll();
    }

    public static function metrics($sellerProfileId)
    {
        $db = Database::getInstance()
            ->connect();

        $orderStmt = $db->prepare("
            SELECT COUNT(DISTINCT orders.id) AS total_orders
            FROM orders
            INNER JOIN order_items
                ON order_items.order_id = orders.id
            WHERE order_items.seller_profile_id = ?
        ");

        $orderStmt->execute([
            $sellerProfileId
        ]);

        $productStmt = $db->prepare("
            SELECT COUNT(*) AS total_products
            FROM products
            WHERE seller_profile_id = ?
        ");

        $productStmt->execute([
            $sellerProfileId
        ]);

        $ratingStmt = $db->prepare("
            SELECT COALESCE(ROUND(AVG(reviews.rating), 1), 0) AS rating
            FROM reviews
            INNER JOIN products
                ON products.id = reviews.product_id
            WHERE products.seller_profile_id = ?
        ");

        $ratingStmt->execute([
            $sellerProfileId
        ]);

        $profileStmt = $db->prepare("SELECT user_id FROM seller_profiles WHERE id = ? LIMIT 1");
        $profileStmt->execute([$sellerProfileId]);
        $profile = $profileStmt->fetch();
        $sellerUserId = (int) ($profile->user_id ?? 0);

        $responseRate = null;
        if ($sellerUserId > 0) {
            $responseStmt = $db->prepare("
                SELECT
                    COUNT(*) AS customer_messages_received,
                    COALESCE(SUM(
                        CASE WHEN EXISTS (
                            SELECT 1
                            FROM messages seller_message
                            WHERE seller_message.conversation_id = customer_message.conversation_id
                                AND seller_message.sender_id = ?
                                AND (
                                    seller_message.created_at > customer_message.created_at
                                    OR (
                                        seller_message.created_at = customer_message.created_at
                                        AND seller_message.id > customer_message.id
                                    )
                                )
                        ) THEN 1 ELSE 0 END
                    ), 0) AS customer_messages_answered
                FROM messages customer_message
                INNER JOIN conversations
                    ON conversations.id = customer_message.conversation_id
                WHERE conversations.farmer_id = ?
                    AND customer_message.sender_id != ?
            ");
            $responseStmt->execute([$sellerUserId, $sellerUserId, $sellerUserId]);
            $response = $responseStmt->fetch();
            $received = (int) ($response->customer_messages_received ?? 0);
            if ($received > 0) {
                $responseRate = round(((int) ($response->customer_messages_answered ?? 0) / $received) * 100);
            }
        }

        $sellerDeliveryMetaStmt = $db->prepare("SELECT average_delivery_time FROM seller_profiles WHERE id = ? LIMIT 1");
        $sellerDeliveryMetaStmt->execute([$sellerProfileId]);
        $manualAverageDeliveryTime = trim((string) ($sellerDeliveryMetaStmt->fetch()->average_delivery_time ?? ''));

        if (preg_match('/^\d+(?:\.\d+)?$/', $manualAverageDeliveryTime)) {
            $manualAverageDeliveryTime = $manualAverageDeliveryTime . ' ' . ((float) $manualAverageDeliveryTime === 1 ? 'day' : 'days');
        }

        $averageDeliveryTime = $manualAverageDeliveryTime !== '' ? $manualAverageDeliveryTime : null;

        if ($averageDeliveryTime === null) {
            $deliveryStmt = $db->prepare("
                SELECT AVG(TIMESTAMPDIFF(MINUTE, completed_orders.created_at, completed_orders.delivered_at)) AS average_minutes
                FROM (
                    SELECT DISTINCT orders.id, orders.created_at, orders.delivered_at
                    FROM orders
                    INNER JOIN order_items ON order_items.order_id = orders.id
                    WHERE order_items.seller_profile_id = ?
                        AND orders.status IN ('delivered', 'completed')
                        AND orders.delivered_at IS NOT NULL
                ) completed_orders
            ");
            $deliveryStmt->execute([$sellerProfileId]);
            $averageMinutes = $deliveryStmt->fetch()->average_minutes ?? null;

            if ($averageMinutes !== null) {
                $minutes = max(0, (int) round((float) $averageMinutes));
                if ($minutes >= 1440) {
                    $days = round($minutes / 1440, 1);
                    $daysLabel = rtrim(rtrim((string) number_format((float) $days, 1, '.', ''), '0'), '.');
                    $averageDeliveryTime = $daysLabel . ' ' . ($daysLabel === '1' ? 'day' : 'days');
                } elseif ($minutes >= 60) {
                    $hours = round($minutes / 60, 1);
                    $hoursLabel = rtrim(rtrim((string) number_format((float) $hours, 1, '.', ''), '0'), '.');
                    $averageDeliveryTime = $hoursLabel . ' ' . ($hoursLabel === '1' ? 'hour' : 'hours');
                } else {
                    $averageDeliveryTime = $minutes . ' min';
                }
            }
        }

        return [
            'total_orders' => (int) ($orderStmt->fetch()->total_orders ?? 0),
            'total_products' => (int) ($productStmt->fetch()->total_products ?? 0),
            'customer_rating' => (float) ($ratingStmt->fetch()->rating ?? 0),
            'response_rate' => $responseRate,
            'average_delivery_time' => $averageDeliveryTime,
        ];
    }
}
