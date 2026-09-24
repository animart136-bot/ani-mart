<?php

namespace App\Models;

use App\Core\Model;

class BroadcastMessage extends Model
{
    private ?array $tableColumns = null;

    public function listAll()
    {
        return $this->fetchAll(
            "SELECT * FROM broadcast_messages ORDER BY created_at DESC"
        );
    }

    public function create($data)
    {
        $columns = $this->columns();
        if (!in_array('author_id', $columns, true)) {
            $broadcastType = match ($data['message_type']) {
                'weather_advisory' => 'weather',
                'agricultural_tip' => 'agricultural_tips',
                default => $data['message_type'],
            };
            $sql = "INSERT INTO broadcast_messages (title, body, audience, audience_data, broadcast_type, status, scheduled_at, sent_at, created_by, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $this->query($sql, [
                $data['title'], $data['body'], $data['audience'], $data['recipient_ids'] ?? null,
                $broadcastType, !empty($data['is_published']) ? 'sent' : 'draft', null,
                $data['sent_at'] ?? null, $data['author_id'],
            ]);
            return $this->db->lastInsertId();
        }

        $sql = "INSERT INTO broadcast_messages (
            author_id,
            audience,
            recipient_ids,
            message_type,
            title,
            body,
            is_published,
            sent_at,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $params = [
            $data['author_id'],
            $data['audience'],
            $data['recipient_ids'] ?? null,
            $data['message_type'],
            $data['title'],
            $data['body'],
            $data['is_published'] ? 1 : 0,
            $data['sent_at'] ?? null,
        ];

        $this->query($sql, $params);

        return $this->db->lastInsertId();
    }

    private function columns(): array
    {
        if ($this->tableColumns !== null) return $this->tableColumns;
        $rows = $this->fetchAll('SHOW COLUMNS FROM broadcast_messages');
        return $this->tableColumns = array_map(static fn($row) => (string) $row->Field, $rows);
    }
}
