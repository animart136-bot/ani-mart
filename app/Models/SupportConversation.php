<?php

namespace App\Models;

use App\Core\Model;
use App\Services\SensitiveDataCipher;

class SupportConversation extends Model
{
    public function findById($id)
    {
        return hydrate_sensitive_fields($this->fetchOne(
            "SELECT sc.*, 
                    cu.first_name AS customer_first_name,
                    cu.last_name AS customer_last_name,
                    fu.first_name AS farmer_first_name,
                    fu.last_name AS farmer_last_name,
                    au.first_name AS assigned_first_name,
                    au.last_name AS assigned_last_name
            FROM support_conversations sc
            LEFT JOIN users cu ON cu.id = sc.customer_id
            LEFT JOIN users fu ON fu.id = sc.farmer_id
            LEFT JOIN users au ON au.id = sc.assigned_to
            WHERE sc.id = ?
            LIMIT 1",
            [$id]
        ), 'support_conversations', ['subject', 'last_message']);
    }

    public function listForAdmin($type = null, $search = '', $archived = false)
    {
        $conditions = ['sc.is_archived = ?'];
        $params = [$archived ? 1 : 0];

        if ($type) {
            $conditions[] = 'sc.conversation_type = ?';
            $params[] = $type;
        }

        if (!empty($search)) {
            $conditions[] = '(' .
                "COALESCE(sc.subject, '') LIKE ? OR " .
                "COALESCE(sc.last_message, '') LIKE ? OR " .
                "COALESCE(cu.first_name, '') LIKE ? OR " .
                "COALESCE(cu.last_name, '') LIKE ? OR " .
                "COALESCE(fu.first_name, '') LIKE ? OR " .
                "COALESCE(fu.last_name, '') LIKE ?" .
            ')';

            $term = '%' . $search . '%';
            $params = array_merge($params, array_fill(0, 6, $term));
        }

        $sql = "SELECT sc.*, 
                cu.first_name AS customer_first_name,
                cu.last_name AS customer_last_name,
                fu.first_name AS farmer_first_name,
                fu.last_name AS farmer_last_name,
                au.first_name AS assigned_first_name,
                au.last_name AS assigned_last_name,
                IFNULL((SELECT COUNT(*) FROM support_messages sm WHERE sm.conversation_id = sc.id AND sm.is_read = 0), 0) AS unread_count
            FROM support_conversations sc
            LEFT JOIN users cu ON cu.id = sc.customer_id
            LEFT JOIN users fu ON fu.id = sc.farmer_id
            LEFT JOIN users au ON au.id = sc.assigned_to
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY sc.last_message_at DESC, sc.created_at DESC";

        $records = $this->fetchAll($sql, $params);
        return array_map(fn($record) => hydrate_sensitive_fields($record, 'support_conversations', ['subject', 'last_message']), $records);
    }

    public function updateLastMessage($conversationId, $message)
    {
        return $this->query(
            "UPDATE support_conversations SET last_message = ?, last_message_encrypted = ?, last_message_at = NOW(), updated_at = NOW() WHERE id = ?",
            [$message, (new SensitiveDataCipher())->encrypt($message, 'support_conversations.last_message'), $conversationId]
        );
    }

    public function assignAdmin($conversationId, $adminId)
    {
        return $this->query(
            "UPDATE support_conversations SET assigned_to = ?, updated_at = NOW() WHERE id = ?",
            [$adminId, $conversationId]
        );
    }

    public function updateStatus($conversationId, $status)
    {
        return $this->query(
            "UPDATE support_conversations SET status = ?, updated_at = NOW() WHERE id = ?",
            [$status, $conversationId]
        );
    }

    public function summaryCounts()
    {
        $rows = $this->fetchAll(
            "SELECT status, COUNT(*) AS total FROM support_conversations GROUP BY status"
        );

        $summary = [
            'open' => 0,
            'pending' => 0,
            'investigating' => 0,
            'waiting_seller' => 0,
            'waiting_customer' => 0,
            'resolved' => 0,
            'rejected' => 0,
        ];

        foreach ($rows as $row) {
            $summary[$row->status] = (int) $row->total;
        }

        return $summary;
    }
}
