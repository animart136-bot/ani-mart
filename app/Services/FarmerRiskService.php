<?php

namespace App\Services;

use App\Core\Database;

class FarmerRiskService
{
    public function refreshRiskScores()
    {
        $db =
            Database::connection();

        $rows =
            $this->buildRiskRows();

        if (
            !$this->columnExists(
                'seller_profiles',
                'risk_score'
            )
            ||
            !$this->columnExists(
                'seller_profiles',
                'risk_level'
            )
        ) {
            return $rows;
        }

        $stmt = $db->prepare("

            UPDATE seller_profiles

            SET risk_score = ?,
                risk_level = ?

            WHERE id = ?

        ");

        foreach ($rows as $row) {

            $stmt->execute([
                $row->risk_score,
                $row->risk_level,
                $row->id
            ]);
        }

        return $rows;
    }

    public function summary()
    {
        $rows =
            $this->refreshRiskScores();

        $summary = [
            'high'   => 0,
            'medium' => 0,
            'low'    => 0
        ];

        foreach ($rows as $row) {

            $summary[$row->risk_level]++;
        }

        return $summary;
    }

    public function topSuspicious($limit = 10)
    {
        $rows =
            $this->refreshRiskScores();

        usort($rows, function ($a, $b) {
            return $b->risk_score <=> $a->risk_score;
        });

        return array_slice(
            $rows,
            0,
            $limit
        );
    }

    public function recentAlerts($limit = 5)
    {
        $rows =
            $this->refreshRiskScores();

        $alerts = [];

        foreach ($rows as $row) {

            $farmer =
                $row->shop_name
                ?: trim(
                    ($row->first_name ?? '')
                    . ' ' .
                    ($row->last_name ?? '')
                );

            if ($row->weekly_harvest_changes >= 4) {

                $alerts[] = (object) [
                    'message' =>
                        'Farmer ' . $farmer
                        . ' changed harvest date '
                        . $row->weekly_harvest_changes
                        . ' times this week.',
                    'risk_level' => $row->risk_level
                ];
            }

            if ($row->risk_level === 'high') {

                $alerts[] = (object) [
                    'message' =>
                        'Farmer ' . $farmer
                        . ' reached HIGH risk status.',
                    'risk_level' => 'high'
                ];
            }
        }

        return array_slice(
            $alerts,
            0,
            $limit
        );
    }

    private function buildRiskRows()
    {
        $db =
            Database::connection();

        $complaintSelect =
            "0 AS complaint_count";

        $complaintJoin =
            "";

        if ($this->tableExists('customer_complaints')) {

            $complaintSelect =
                "COALESCE(complaints.complaint_count, 0)
                    AS complaint_count";

            $complaintJoin =
                "

            LEFT JOIN (

                SELECT

                    seller_profile_id,

                    COUNT(id) AS complaint_count

                FROM customer_complaints

                GROUP BY seller_profile_id

            ) complaints
                ON complaints.seller_profile_id =
                seller_profiles.id

                ";
        }

        $stmt = $db->query("

            SELECT

                seller_profiles.*,

                users.first_name,
                users.last_name,
                users.email,

                COALESCE(harvest_logs.harvest_changes, 0)
                    AS harvest_changes,

                COALESCE(harvest_logs.weekly_harvest_changes, 0)
                    AS weekly_harvest_changes,

                COALESCE(cancelled_orders.cancelled_orders, 0)
                    AS cancelled_orders,

                {$complaintSelect}

            FROM seller_profiles

            LEFT JOIN users
                ON users.id =
                seller_profiles.user_id

            LEFT JOIN (

                SELECT

                    products.seller_profile_id,

                    COUNT(product_activity_logs.id)
                        AS harvest_changes,

                    SUM(
                        product_activity_logs.created_at >=
                        DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ) AS weekly_harvest_changes

                FROM product_activity_logs

                INNER JOIN products
                    ON products.id =
                    product_activity_logs.product_id

                WHERE product_activity_logs.action =
                    'Harvest Date Changed'

                GROUP BY products.seller_profile_id

            ) harvest_logs
                ON harvest_logs.seller_profile_id =
                seller_profiles.id

            LEFT JOIN (

                SELECT

                    order_items.seller_profile_id,

                    COUNT(DISTINCT orders.id)
                        AS cancelled_orders

                FROM orders

                INNER JOIN order_items
                    ON order_items.order_id =
                    orders.id

                WHERE orders.status = 'cancelled'

                GROUP BY order_items.seller_profile_id

            ) cancelled_orders
                ON cancelled_orders.seller_profile_id =
                seller_profiles.id

            {$complaintJoin}

        ");

        $rows =
            $stmt->fetchAll();

        foreach ($rows as $row) {

            $row->risk_score =
                $this->calculateRiskScore($row);

            $row->risk_level =
                $this->riskLevel($row->risk_score);
        }

        return $rows;
    }

    private function calculateRiskScore($row)
    {
        $score = 0;

        $trustScore =
            (int) ($row->trust_score ?? 0);

        if ($trustScore < 60) {

            $score += 30;

        } elseif ($trustScore < 80) {

            $score += 20;
        }

        $score +=
            (int) ($row->harvest_changes ?? 0) * 5;

        $cancelledOrders =
            (int) ($row->cancelled_orders ?? 0);

        if ($cancelledOrders > 10) {

            $score += 30;

        } elseif ($cancelledOrders > 5) {

            $score += 15;
        }

        $score +=
            (int) ($row->complaint_count ?? 0) * 10;

        return $score;
    }

    private function riskLevel($score)
    {
        if ($score >= 50) {
            return 'high';
        }

        if ($score >= 25) {
            return 'medium';
        }

        return 'low';
    }

    private function tableExists($table)
    {
        $db =
            Database::connection();

        $stmt = $db->prepare("

            SELECT COUNT(*) AS total

            FROM information_schema.TABLES

            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?

        ");

        $stmt->execute([
            $table
        ]);

        return ((int) ($stmt->fetch()->total ?? 0)) > 0;
    }

    private function columnExists($table, $column)
    {
        $db =
            Database::connection();

        $stmt = $db->prepare("

            SELECT COUNT(*) AS total

            FROM information_schema.COLUMNS

            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?

        ");

        $stmt->execute([
            $table,
            $column
        ]);

        return ((int) ($stmt->fetch()->total ?? 0)) > 0;
    }
}
