<?php

namespace App\Models;

use App\Core\Model;
use App\Services\SensitiveDataCipher;

class Report extends Model
{
    public function create(array $data)
    {
        $this->query(
            "INSERT INTO reports (
                reporter_id,
                target_type,
                target_id,
                reason,
                description,
                description_encrypted,
                status,
                priority,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'submitted', ?, NOW())",
            [
                $data['reporter_id'],
                $data['target_type'],
                $data['target_id'] ?? null,
                $data['reason'],
                $data['description'],
                (new SensitiveDataCipher())->encrypt($data['description'], 'reports.description'),
                $data['priority'] ?? 'medium'
            ]
        );

        return $this->db->lastInsertId();
    }

    public function find($reportId)
    {
        return $this->hydrateSensitive($this->fetchOne(
            "SELECT r.*,
                reporter.first_name AS reporter_first_name,
                reporter.last_name AS reporter_last_name,
                reporter.email AS reporter_email,
                reporter.role AS reporter_role,
                assigned.first_name AS assigned_admin_first_name,
                assigned.last_name AS assigned_admin_last_name,
                resolver.first_name AS resolver_first_name,
                resolver.last_name AS resolver_last_name,
                CASE
                    WHEN r.target_type = 'product' THEN (SELECT p.name FROM products p WHERE p.id = r.target_id LIMIT 1)
                    WHEN r.target_type = 'seller' THEN (SELECT sp.shop_name FROM seller_profiles sp WHERE sp.id = r.target_id LIMIT 1)
                    WHEN r.target_type = 'customer' THEN (SELECT TRIM(CONCAT(u.first_name, ' ', u.last_name)) FROM users u WHERE u.id = r.target_id LIMIT 1)
                    WHEN r.target_type = 'community_post' THEN (SELECT cp.title FROM community_posts cp WHERE cp.id = r.target_id LIMIT 1)
                    WHEN r.target_type = 'comment' THEN (SELECT LEFT(cc.comment, 160) FROM community_comments cc WHERE cc.id = r.target_id LIMIT 1)
                    ELSE NULL
                END AS target_label
            FROM reports r
            LEFT JOIN users reporter ON reporter.id = r.reporter_id
            LEFT JOIN users assigned ON assigned.id = r.assigned_admin_id
            LEFT JOIN users resolver ON resolver.id = r.resolved_by
            WHERE r.id = ?
            LIMIT 1",
            [$reportId]
        ));
    }

    public function forReporter($userId)
    {
        $records = $this->fetchAll(
            "SELECT * FROM reports WHERE reporter_id = ? ORDER BY created_at DESC",
            [$userId]
        );
        return $this->hydrateMany($records);
    }

    public function adminList(array $filters = [])
    {
        $sql = "SELECT * FROM reports WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (id = ? OR reason LIKE ? OR description LIKE ?)";
            $params[] = $filters['search'];
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND priority = ?";
            $params[] = $filters['priority'];
        }

        if (!empty($filters['target_type'])) {
            $sql .= " AND target_type = ?";
            $params[] = $filters['target_type'];
        }

        $sort = $filters['sort'] ?? 'newest';

        if ($sort === 'oldest') {
            $sql .= ' ORDER BY created_at ASC';
        } else {
            $sql .= ' ORDER BY created_at DESC';
        }

        return $this->hydrateMany($this->fetchAll($sql, $params));
    }

    public function updateStatus($reportId, $status)
    {
        $this->query(
            "UPDATE reports SET status = ?, updated_at = NOW() WHERE id = ?",
            [$status, $reportId]
        );
    }

    public function assignAdmin($reportId, $adminId)
    {
        $this->query(
            "UPDATE reports SET assigned_admin_id = ?, status = 'assigned', updated_at = NOW() WHERE id = ?",
            [$adminId, $reportId]
        );
    }

    public function resolve($reportId, $adminId, $status)
    {
        $this->query(
            "UPDATE reports SET resolved_by = ?, status = ?, resolved_at = NOW(), updated_at = NOW() WHERE id = ?",
            [$adminId, $status, $reportId]
        );
    }

    public function updateAdminNotes($reportId, $notes)
    {
        $this->query(
            "UPDATE reports SET admin_notes = ?, admin_notes_encrypted = ?, updated_at = NOW() WHERE id = ?",
            [$notes, (new SensitiveDataCipher())->encrypt($notes, 'reports.admin_notes'), $reportId]
        );
    }

    public function reportCounts()
    {
        return $this->fetchOne(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'submitted') AS submitted,
                SUM(status = 'pending_review') AS pending_review,
                SUM(status = 'assigned') AS assigned,
                SUM(status = 'investigation') AS investigation,
                SUM(status = 'need_more_info') AS need_more_info,
                SUM(status = 'resolved') AS resolved,
                SUM(status = 'rejected') AS rejected
            FROM reports"
        );
    }

    private function hydrateSensitive($record)
    {
        return hydrate_sensitive_fields($record, 'reports', ['description', 'admin_notes']);
    }

    private function hydrateMany(array $records): array
    {
        return array_map(fn($record) => $this->hydrateSensitive($record), $records);
    }
}
