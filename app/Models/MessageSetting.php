<?php

namespace App\Models;

use App\Core\Model;

class MessageSetting extends Model
{
    public function getForUser($userId)
    {
        return $this->fetchOne(
            "SELECT * FROM message_settings WHERE user_id = ? LIMIT 1",
            [$userId]
        );
    }

    public function createDefaultForUser($userId)
    {
        $sql = "
            INSERT IGNORE INTO message_settings (
                user_id,
                allow_messages_from,
                message_notifications,
                message_request_notifications,
                group_notifications,
                community_notifications,
                announcement_notifications,
                order_chat_notifications,
                email_notifications,
                desktop_notifications,
                read_receipts,
                typing_indicator,
                online_status,
                auto_download_images,
                message_requests,
                blocked_users_enabled,
                theme
            ) VALUES (?, 'everyone', 1, 0, 0, 1, 1, 1, 0, 1, 1, 1, 1, 1, 0, 0, 'default')
        ";

        $this->query($sql, [$userId]);

        return $this->getForUser($userId);
    }

    public function saveForUser($userId, $data)
    {
        $sql = "
            INSERT INTO message_settings (
                user_id,
                allow_messages_from,
                message_notifications,
                message_request_notifications,
                group_notifications,
                community_notifications,
                announcement_notifications,
                order_chat_notifications,
                email_notifications,
                desktop_notifications,
                read_receipts,
                typing_indicator,
                online_status,
                auto_download_images,
                message_requests,
                blocked_users_enabled,
                theme
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                allow_messages_from = VALUES(allow_messages_from),
                message_notifications = VALUES(message_notifications),
                message_request_notifications = VALUES(message_request_notifications),
                group_notifications = VALUES(group_notifications),
                community_notifications = VALUES(community_notifications),
                announcement_notifications = VALUES(announcement_notifications),
                order_chat_notifications = VALUES(order_chat_notifications),
                email_notifications = VALUES(email_notifications),
                desktop_notifications = VALUES(desktop_notifications),
                read_receipts = VALUES(read_receipts),
                typing_indicator = VALUES(typing_indicator),
                online_status = VALUES(online_status),
                auto_download_images = VALUES(auto_download_images),
                message_requests = VALUES(message_requests),
                blocked_users_enabled = VALUES(blocked_users_enabled),
                theme = VALUES(theme),
                updated_at = CURRENT_TIMESTAMP
        ";

        $params = [
            $userId,
            $data['allow_messages_from'],
            $data['message_notifications'],
            $data['message_request_notifications'],
            $data['group_notifications'],
            $data['community_notifications'],
            $data['announcement_notifications'],
            $data['order_chat_notifications'],
            $data['email_notifications'],
            $data['desktop_notifications'],
            $data['read_receipts'],
            $data['typing_indicator'],
            $data['online_status'],
            $data['auto_download_images'],
            $data['message_requests'],
            $data['blocked_users_enabled'],
            $data['theme']
        ];

        $this->query($sql, $params);

        return $this->getForUser($userId);
    }
}
