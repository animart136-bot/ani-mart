<?php

namespace App\Models;

use App\Core\Model;
use App\Services\SensitiveDataCipher;

class Notification extends Model
{
    private static $columns = null;

    /*
    |--------------------------------------------------------------------------
    | Create Notification
    |--------------------------------------------------------------------------
    */
    public function create($data)
    {
        $columns = $this->columns();
        $fields = [
            'user_id',
            'title',
            'message',
            'type'
        ];

        if (in_array('message_encrypted', $columns, true)) {
            $fields[] = 'message_encrypted';
        }

        foreach (['icon', 'color', 'url', 'link', 'target_type', 'target_id', 'action_url'] as $optionalField) {
            if (in_array($optionalField, $columns, true)) {
                $fields[] = $optionalField;
            }
        }

        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        $fieldList = implode(",\n                ", $fields);

        $sql = "
            INSERT INTO notifications (
                {$fieldList}
            )
            VALUES ({$placeholders})
        ";

        $values = [];

        foreach ($fields as $field) {
            if ($field === 'message_encrypted') {
                $values[] = (new SensitiveDataCipher())->encrypt($data['message'] ?? null, 'notifications.message');
                continue;
            }

            // Older installations use `link`; newer ones use `url` and
            // `action_url`. Both store the destination supplied by callers.
            $values[] = $field === 'link' ? ($data['url'] ?? null) : ($data[$field] ?? null);
        }

        return $this->query($sql, $values);
    }

    /*
    |--------------------------------------------------------------------------
    | User Notifications
    |--------------------------------------------------------------------------
    */
    public function latest($userId)
    {
        $records = $this->fetchAll(
            "
            SELECT *, UNIX_TIMESTAMP(created_at) * 1000 AS created_at_timestamp
            FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 10
            ",
            [$userId]
        );
        return array_map(fn($record) => hydrate_sensitive_fields($record, 'notifications', ['message']), $records);
    }

    public function timeline($userId, $limit = 60)
    {
        $records = $this->fetchAll(
            "
            SELECT *, UNIX_TIMESTAMP(created_at) * 1000 AS created_at_timestamp
            FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
            ",
            [$userId, (int) $limit]
        );
        return array_map(fn($record) => hydrate_sensitive_fields($record, 'notifications', ['message']), $records);
    }

    /*
    |--------------------------------------------------------------------------
    | Unread Count
    |--------------------------------------------------------------------------
    */
    public function unreadCount($userId)
    {
        $result = $this->fetchOne(
            "
            SELECT COUNT(*) AS total
            FROM notifications
            WHERE user_id = ?
            AND is_read = 0
            ",
            [$userId]
        );

        return $result->total ?? 0;
    }

    public function unreadCountByType($userId, $type)
    {
        $result = $this->fetchOne(
            "
            SELECT COUNT(*) AS total
            FROM notifications
            WHERE user_id = ?
            AND type = ?
            AND is_read = 0
            ",
            [$userId, $type]
        );

        return $result->total ?? 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Mark All Read
    |--------------------------------------------------------------------------
    */
    public function markAllRead($userId)
    {
        return $this->query(
            "
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?
            ",
            [$userId]
        );
    }

    public function findById($id)
    {
        return hydrate_sensitive_fields($this->fetchOne(
            "
            SELECT *
            FROM notifications
            WHERE id = ?
            LIMIT 1
            ",
            [$id]
        ), 'notifications', ['message']);
    }

    public function markAsRead($notificationId, $userId)
    {
        return $this->query(
            "
            UPDATE notifications
            SET is_read = 1
            WHERE id = ?
            AND user_id = ?
            ",
            [$notificationId, $userId]
        );
    }

    private function columns()
    {
        if (self::$columns !== null) {
            return self::$columns;
        }

        $rows = $this->fetchAll("SHOW COLUMNS FROM notifications");

        self::$columns = array_map(
            fn ($row) => $row->Field,
            $rows
        );

        return self::$columns;
    }
}
