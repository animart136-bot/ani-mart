<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\AuthGuard;

class AdminAuditController extends Controller
{
    public function index()
    {
        $user = AuthGuard::admin();

        $db = Database::connection();

        $filters = [
            'event' => trim((string) ($_GET['event'] ?? '')),
            'actor' => trim((string) ($_GET['actor'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'target_type' => trim((string) ($_GET['target_type'] ?? '')),
            'from' => trim((string) ($_GET['from'] ?? '')),
            'to' => trim((string) ($_GET['to'] ?? '')),
        ];

        $sql = "
            SELECT a.*
            FROM audit_logs a
            WHERE 1 = 1
        ";

        $params = [];

        if ($filters['event'] !== '') {
            $sql .= ' AND a.action LIKE ?';
            $params[] = '%' . $filters['event'] . '%';
        }

        if ($filters['actor'] !== '') {
            $sql .= ' AND a.user_id = ?';
            $params[] = (int) $filters['actor'];
        }

        if ($filters['status'] !== '') {
            $sql .= ' AND a.action LIKE ?';
            $params[] = '%' . $filters['status'] . '%';
        }

        if ($filters['target_type'] !== '') {
            $sql .= ' AND a.entity_type = ?';
            $params[] = $filters['target_type'];
        }

        if ($filters['from'] !== '') {
            $sql .= ' AND a.created_at >= ?';
            $params[] = $filters['from'] . ' 00:00:00';
        }

        if ($filters['to'] !== '') {
            $sql .= ' AND a.created_at <= ?';
            $params[] = $filters['to'] . ' 23:59:59';
        }

        $sql .= ' ORDER BY a.created_at DESC LIMIT 200';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
        $logs = array_map(
            fn($log) => hydrate_sensitive_fields($log, 'audit_logs', [
                'old_value', 'new_value', 'description', 'ip_address', 'user_agent',
            ]),
            $logs
        );

        return $this->view('admin/audit/index', [
            'title' => 'Security Audit Log',
            'bodyClass' => 'admin-audit-log-page',
            'logs' => $logs,
            'filters' => $filters,
        ]);
    }
}
