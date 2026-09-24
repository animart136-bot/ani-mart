<?php

namespace App\Models;

use App\Core\Database;

class FavoriteFarmer
{
    public static function follow($customerId, $sellerProfileId)
    {
        if (!self::tableExists()) {
            return false;
        }

        $db = Database::connection();

        $stmt = $db->prepare("INSERT IGNORE INTO seller_followers (customer_id, seller_profile_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([
            $customerId,
            $sellerProfileId
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function unfollow($customerId, $sellerProfileId)
    {
        if (!self::tableExists()) {
            return false;
        }

        $db = Database::connection();

        $stmt = $db->prepare("DELETE FROM seller_followers WHERE customer_id = ? AND seller_profile_id = ?");
        $stmt->execute([
            $customerId,
            $sellerProfileId
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function isFollowing($customerId, $sellerProfileId)
    {
        if (!self::tableExists()) {
            return false;
        }

        $db = Database::connection();

        $stmt = $db->prepare("SELECT 1 FROM seller_followers WHERE customer_id = ? AND seller_profile_id = ? LIMIT 1");
        $stmt->execute([
            $customerId,
            $sellerProfileId
        ]);

        return (bool) $stmt->fetch();
    }

    public static function followedSellerIds($customerId)
    {
        if (!self::tableExists()) {
            return [];
        }

        $db = Database::connection();

        $stmt = $db->prepare("SELECT seller_profile_id FROM seller_followers WHERE customer_id = ? ORDER BY created_at DESC");
        $stmt->execute([$customerId]);

        $rows = $stmt->fetchAll();

        return array_map(
            fn ($row) => (int) $row->seller_profile_id,
            $rows
        );
    }

    public static function followersCount($sellerProfileId)
    {
        if (!self::tableExists()) {
            return 0;
        }

        $db = Database::connection();

        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM seller_followers WHERE seller_profile_id = ?");
        $stmt->execute([$sellerProfileId]);

        return (int) ($stmt->fetch()->total ?? 0);
    }

    public static function followerCustomerIds($sellerProfileId)
    {
        if (!self::tableExists()) {
            return [];
        }

        $db = Database::connection();

        $stmt = $db->prepare("SELECT customer_id FROM seller_followers WHERE seller_profile_id = ?");
        $stmt->execute([$sellerProfileId]);

        $rows = $stmt->fetchAll();

        return array_map(fn ($row) => (int) $row->customer_id, $rows);
    }

    private static function tableExists()
    {
        $db = Database::connection();

        try {
            $db->exec("CREATE TABLE IF NOT EXISTS seller_followers (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                seller_profile_id INT NOT NULL,
                created_at TIMESTAMP NULL,
                UNIQUE INDEX idx_seller_followers_customer_seller (customer_id, seller_profile_id),
                INDEX idx_seller_followers_seller (seller_profile_id),
                INDEX idx_seller_followers_customer (customer_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
