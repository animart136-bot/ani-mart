<?php

namespace App\Services;

use App\Core\Database;

class TransparencyAlertService
{
    private const ALERT_CONDITION = "
        products.is_active = 1
        AND (
            products.harvest_date IS NULL
            OR products.harvest_photo IS NULL
            OR products.harvest_photo = ''
            OR DATEDIFF(CURDATE(), products.harvest_date) > 14
        )
    ";

    private $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function count(): int
    {
        $stmt = $this->db->query(
            'SELECT COUNT(*) FROM products WHERE ' . self::ALERT_CONDITION
        );

        return (int) $stmt->fetchColumn();
    }

    public function all(?int $sellerProfileId = null): array
    {
        $sql = "
            SELECT
                products.id,
                products.name,
                products.harvest_date,
                products.harvest_photo,
                seller_profiles.shop_name,
                CASE
                    WHEN products.harvest_date IS NULL THEN 'Missing harvest date'
                    WHEN products.harvest_photo IS NULL OR products.harvest_photo = '' THEN 'Missing harvest photo'
                    WHEN DATEDIFF(CURDATE(), products.harvest_date) > 14 THEN 'Harvest date older than 14 days'
                    ELSE 'Review product record'
                END AS alert_reason
            FROM products
            LEFT JOIN seller_profiles
                ON seller_profiles.id = products.seller_profile_id
            WHERE " . self::ALERT_CONDITION;

        $params = [];
        if ($sellerProfileId !== null && $sellerProfileId > 0) {
            $sql .= ' AND products.seller_profile_id = ?';
            $params[] = $sellerProfileId;
        }

        $sql .= ' ORDER BY products.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
