<?php

namespace App\Models;

use App\Core\Database;


class Cart
{
    public static function add($userId, $productId)
    {
        $db = Database::getInstance()
            ->connect();

        $check = $db->prepare("

            SELECT *

            FROM cart

            WHERE user_id = ?
            AND product_id = ?

        ");

        $check->execute([
            $userId,
            $productId
        ]);

        $existing = $check->fetch();

        if ($existing) {

            $update = $db->prepare("

                UPDATE cart

                SET quantity = quantity + 1

                WHERE id = ?

            ");

            return $update->execute([
                $existing->id
            ]);
        }

        $insert = $db->prepare("

            INSERT INTO cart (

                user_id,
                product_id,
                quantity

            )

            VALUES (?, ?, 1)

        ");

        return $insert->execute([
            $userId,
            $productId
        ]);
    }

    public static function items($userId)
    {
        $db = Database::getInstance()
            ->connect();

        $stmt = $db->prepare("

        SELECT

            cart.id AS cart_id,
            cart.quantity,

            products.*,

            seller_profiles.shop_name

        FROM cart

        LEFT JOIN products
            ON products.id = cart.product_id

        LEFT JOIN seller_profiles
            ON seller_profiles.id = products.seller_profile_id

        WHERE cart.user_id = ?

        ORDER BY seller_profiles.shop_name

    ");

        $stmt->execute([
            $userId
        ]);

        return $stmt->fetchAll();
    }
    public static function remove($cartId)
    {
        $db = Database::getInstance()
            ->connect();

        $stmt = $db->prepare("

            DELETE FROM cart

            WHERE id = ?

        ");

        return $stmt->execute([
            $cartId
        ]);
    }

    public static function count($userId)
    {
        $db = Database::getInstance()
            ->connect();

        $stmt = $db->prepare("

            SELECT
                COALESCE(
                    SUM(quantity),
                    0
                ) total

            FROM cart

            WHERE user_id = ?

        ");

        $stmt->execute([
            $userId
        ]);

        return $stmt->fetch()->total ?? 0;
    }

    public static function updateQuantity(
        $cartId,
        $quantity
    ) {
        $db = Database::getInstance()
            ->connect();

        $stmt = $db->prepare("

        UPDATE cart

        SET quantity = ?

        WHERE id = ?

    ");

        return $stmt->execute([
            $quantity,
            $cartId
        ]);
    }
}
