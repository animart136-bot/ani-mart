<?php

namespace App\Models;

use App\Core\Database;

class SelectedCartItem
{
    public static function save(
        $userId,
        $cartIds
    ) {

        $db =
            Database::connection();

        $delete =
            $db->prepare("

                DELETE FROM selected_cart_items

                WHERE user_id = ?

            ");

        $delete->execute([
            $userId
        ]);

        $insert =
            $db->prepare("

                INSERT INTO selected_cart_items
                (
                    user_id,
                    cart_id
                )

                VALUES (?, ?)

            ");

        foreach($cartIds as $cartId){

            $insert->execute([
                $userId,
                $cartId
            ]);
        }
    }

    public static function items(
        $userId
    ){

        $db =
            Database::connection();

        $stmt =
            $db->prepare("

                SELECT

                    cart.id AS cart_id,
                    cart.quantity,

                    products.*,

                    seller_profiles.shop_name

                FROM selected_cart_items

                INNER JOIN cart

                    ON cart.id =
                    selected_cart_items.cart_id

                INNER JOIN products

                    ON products.id =
                    cart.product_id

                LEFT JOIN seller_profiles

                    ON seller_profiles.id =
                    products.seller_profile_id

                WHERE
                    selected_cart_items.user_id = ?

            ");

        $stmt->execute([
            $userId
        ]);

        return $stmt->fetchAll();
    }

    public static function clear(
        $userId
    ){

        $db =
            Database::connection();

        $stmt =
            $db->prepare("

                DELETE FROM selected_cart_items

                WHERE user_id = ?

            ");

        return $stmt->execute([
            $userId
        ]);
    }
}