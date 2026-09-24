<?php

namespace App\Models;

use App\Core\Database;

class FarmerApplication
{
public static function all()
{
    $db = \App\Core\Database::getInstance()
        ->connect();

    $stmt = $db->query("

        SELECT

            farmer_applications.*,

            users.first_name,
            users.last_name,
            users.email

        FROM farmer_applications

        LEFT JOIN users
            ON users.id = farmer_applications.user_id

        ORDER BY farmer_applications.id DESC

    ");

    return $stmt->fetchAll();
}

    public static function countByStatus($status)
    {
        $db = Database::getInstance()
            ->connect();

        $stmt = $db->prepare("

            SELECT COUNT(*) total

            FROM farmer_applications

            WHERE status = ?

        ");

        $stmt->execute([
            $status
        ]);

        return $stmt->fetch()->total ?? 0;
    }

    public static function count()
{
    $db = \App\Core\Database::getInstance()
        ->connect();

    return $db->query("

        SELECT COUNT(*) total

        FROM farmer_applications

    ")->fetch()->total ?? 0;
}
}