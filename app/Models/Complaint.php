<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use App\Services\SensitiveDataCipher;

class Complaint extends Model
{
    public function create(array $data)
    {
        return $this->query(
            "
            INSERT INTO complaints (
                order_id,
                customer_id,
                seller_profile_id,
                category,
                description,
                description_encrypted,
                priority
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ",
            [
                $data['order_id'],
                $data['customer_id'],
                $data['seller_profile_id'],
                $data['category'],
                $data['description'],
                (new SensitiveDataCipher())->encrypt($data['description'], 'complaints.description'),
                $data['priority'] ?? 'medium'
            ]
        );
    }

    public function find($id)
    {
        return $this->hydrateSensitive($this->fetchOne(
            "
            SELECT c.*, o.order_number, o.status AS order_status, o.total_amount,
                u.first_name AS customer_first_name, u.last_name AS customer_last_name,
                u.email AS customer_email,
                sp.shop_name, sp.user_id AS seller_user_id, sp.trust_score,
                su.first_name AS seller_first_name, su.last_name AS seller_last_name,
                a.first_name AS admin_first_name, a.last_name AS admin_last_name
            FROM complaints c
            INNER JOIN orders o ON o.id = c.order_id
            INNER JOIN users u ON u.id = c.customer_id
            INNER JOIN seller_profiles sp ON sp.id = c.seller_profile_id
            LEFT JOIN users su ON su.id = sp.user_id
            LEFT JOIN users a ON a.id = c.assigned_admin_id
            WHERE c.id = ?
            LIMIT 1
            ",
            [$id]
        ));
    }

    public function findByOrder($orderId)
    {
        return $this->hydrateSensitive($this->fetchOne(
            "
            SELECT *
            FROM complaints
            WHERE order_id = ?
            LIMIT 1
            ",
            [$orderId]
        ));
    }

    public function eligibleOrdersForCustomer($customerId)
    {
        $db = Database::connection();

        $stmt = $db->prepare(
            "
            SELECT o.*,
                COALESCE(sp.shop_name, '') AS shop_name,
                COUNT(c.id) AS existing_complaints
            FROM orders o
            LEFT JOIN order_items oi ON oi.order_id = o.id
            LEFT JOIN seller_profiles sp ON sp.id = oi.seller_profile_id
            LEFT JOIN complaints c ON c.order_id = o.id AND c.status NOT IN ('rejected','closed')
            WHERE o.user_id = ?
                AND o.status IN ('delivered','completed')
            GROUP BY o.id
            HAVING existing_complaints = 0
            ORDER BY o.created_at DESC
            "
        );

        $stmt->execute([$customerId]);

        return $stmt->fetchAll();
    }

    public function forCustomer($customerId)
    {
        $records = $this->fetchAll(
            "
            SELECT c.*, o.order_number, sp.shop_name,
                (SELECT COUNT(*) FROM complaint_replies r WHERE r.complaint_id = c.id) AS reply_count
            FROM complaints c
            INNER JOIN orders o ON o.id = c.order_id
            INNER JOIN seller_profiles sp ON sp.id = c.seller_profile_id
            WHERE c.customer_id = ?
            ORDER BY c.updated_at DESC, c.created_at DESC
            ",
            [$customerId]
        );
        return $this->hydrateMany($records);
    }

    public function forSeller($sellerProfileId)
    {
        $records = $this->fetchAll(
            "
            SELECT c.*, o.order_number, o.status AS order_status,
                u.first_name AS customer_first_name, u.last_name AS customer_last_name,
                (SELECT COUNT(*) FROM complaint_replies r WHERE r.complaint_id = c.id) AS reply_count
            FROM complaints c
            INNER JOIN orders o ON o.id = c.order_id
            INNER JOIN users u ON u.id = c.customer_id
            WHERE c.seller_profile_id = ?
            ORDER BY c.updated_at DESC, c.created_at DESC
            ",
            [$sellerProfileId]
        );
        return $this->hydrateMany($records);
    }

    /**
     * A farmer can own more than one shop. Disputes belong to the farmer's
     * account, not only to whichever shop is selected for the current session.
     */
    public function forSellerUser($userId)
    {
        $records = $this->fetchAll(
            "
            SELECT c.*, o.order_number, o.status AS order_status,
                u.first_name AS customer_first_name, u.last_name AS customer_last_name,
                sp.shop_name,
                (SELECT COUNT(*) FROM complaint_replies r WHERE r.complaint_id = c.id) AS reply_count
            FROM complaints c
            INNER JOIN orders o ON o.id = c.order_id
            INNER JOIN users u ON u.id = c.customer_id
            INNER JOIN seller_profiles sp ON sp.id = c.seller_profile_id
            WHERE sp.user_id = ?
            ORDER BY c.updated_at DESC, c.created_at DESC
            ",
            [$userId]
        );
        return $this->hydrateMany($records);
    }

    public function adminList($filters = [])
    {
        $query = [
            "
            SELECT c.*, o.order_number, u.first_name AS customer_first_name, u.last_name AS customer_last_name,
                sp.shop_name, a.first_name AS admin_first_name, a.last_name AS admin_last_name,
                (SELECT COUNT(*) FROM complaint_replies r WHERE r.complaint_id = c.id) AS reply_count
            FROM complaints c
            INNER JOIN orders o ON o.id = c.order_id
            INNER JOIN users u ON u.id = c.customer_id
            INNER JOIN seller_profiles sp ON sp.id = c.seller_profile_id
            LEFT JOIN users a ON a.id = c.assigned_admin_id
            WHERE 1=1
            "
        ];

        $params = [];

        if (!empty($filters['search'])) {
            $query[] = "AND (c.id = ? OR o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR sp.shop_name LIKE ? OR c.category LIKE ?)";
            $term = "%{$filters['search']}%";
            $params = array_merge($params, [$filters['search'], $term, $term, $term, $term, $term]);
        }

        if (!empty($filters['status'])) {
            $query[] = "AND c.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $query[] = "AND c.priority = ?";
            $params[] = $filters['priority'];
        }

        if (!empty($filters['seller_profile_id'])) {
            $query[] = "AND c.seller_profile_id = ?";
            $params[] = $filters['seller_profile_id'];
        }

        $order = match($filters['sort'] ?? 'newest') {
            'oldest' => 'c.created_at ASC',
            'high_priority' => 'FIELD(c.priority, \'high\', \'medium\', \'low\') ASC, c.created_at DESC',
            default => 'c.created_at DESC'
        };

        $query[] = "ORDER BY {$order}";

        return $this->hydrateMany($this->fetchAll(implode(' ', $query), $params));
    }

    public function updateStatus($id, $status)
    {
        return $this->query(
            "
            UPDATE complaints
            SET status = ?, updated_at = NOW()
            WHERE id = ?
            ",
            [$status, $id]
        );
    }

    public function assignAdmin($id, $adminId)
    {
        return $this->query(
            "
            UPDATE complaints
            SET assigned_admin_id = ?, updated_at = NOW()
            WHERE id = ?
            ",
            [$adminId, $id]
        );
    }

    public function addAdminNotes($id, $notes)
    {
        return $this->query(
            "
            UPDATE complaints
            SET admin_notes = ?, admin_notes_encrypted = ?, updated_at = NOW()
            WHERE id = ?
            ",
            [$notes, (new SensitiveDataCipher())->encrypt($notes, 'complaints.admin_notes'), $id]
        );
    }

    public function resolve($id, $resolvedById, $status)
    {
        return $this->query(
            "
            UPDATE complaints
            SET status = ?, resolved_by = ?, resolved_at = NOW(), updated_at = NOW()
            WHERE id = ?
            ",
            [$status, $resolvedById, $id]
        );
    }

    public function reopen($id)
    {
        return $this->query(
            "
            UPDATE complaints
            SET status = 'under_review', resolved_by = NULL, resolved_at = NULL, updated_at = NOW()
            WHERE id = ?
            ",
            [$id]
        );
    }

    public function markClosed($id)
    {
        return $this->query(
            "
            UPDATE complaints
            SET status = 'closed', updated_at = NOW()
            WHERE id = ?
            ",
            [$id]
        );
    }

    public function countActiveByOrder($orderId)
    {
        return $this->fetchOne(
            "
            SELECT COUNT(*) AS total
            FROM complaints
            WHERE order_id = ?
                AND status NOT IN ('rejected','closed')
            ",
            [$orderId]
        )->total ?? 0;
    }

    public function deductTrust($sellerProfileId, $amount)
    {
        return $this->query(
            "
            UPDATE seller_profiles
            SET trust_score = GREATEST(0, trust_score - ?)
            WHERE id = ?
            ",
            [$amount, $sellerProfileId]
        );
    }

    private function hydrateSensitive($record)
    {
        return hydrate_sensitive_fields($record, 'complaints', ['description', 'admin_notes']);
    }

    private function hydrateMany(array $records): array
    {
        return array_map(fn($record) => $this->hydrateSensitive($record), $records);
    }
}
