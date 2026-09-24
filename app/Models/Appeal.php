<?php

namespace App\Models;

use App\Core\Model;
use App\Services\SensitiveDataCipher;

class Appeal extends Model
{
    public function create(array $data)
    {
        $stmt = $this->query(
            "INSERT INTO appeals (report_id, user_id, reason, reason_encrypted, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())",
            [
                $data['report_id'],
                $data['user_id'],
                $data['reason'],
                (new SensitiveDataCipher())->encrypt($data['reason'], 'appeals.reason')
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function forReport($reportId)
    {
        $records = $this->fetchAll(
            "SELECT a.*, u.first_name, u.last_name
            FROM appeals a
            LEFT JOIN users u ON u.id = a.user_id
            WHERE a.report_id = ?
            ORDER BY a.created_at DESC",
            [$reportId]
        );
        return $this->hydrateMany($records);
    }

    public function byUser($userId)
    {
        $records = $this->fetchAll(
            "SELECT * FROM appeals WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
        return $this->hydrateMany($records);
    }

    public function existsForUserAndReport(int $userId, int $reportId): bool
    {
        return (bool) $this->fetchOne(
            'SELECT id FROM appeals WHERE user_id = ? AND report_id = ? LIMIT 1',
            [$userId, $reportId]
        );
    }

    public function find($appealId)
    {
        return hydrate_sensitive_fields($this->fetchOne(
            "SELECT * FROM appeals WHERE id = ? LIMIT 1",
            [$appealId]
        ), 'appeals', ['reason']);
    }

    public function updateStatus($appealId, $status)
    {
        $this->query(
            "UPDATE appeals SET status = ?, updated_at = NOW() WHERE id = ?",
            [$status, $appealId]
        );
    }

    private function hydrateMany(array $records): array
    {
        return array_map(fn($record) => hydrate_sensitive_fields($record, 'appeals', ['reason']), $records);
    }
}
