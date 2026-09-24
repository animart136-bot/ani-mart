<?php

namespace App\Models;

use App\Core\Model;

class ConversationSetting extends Model
{
    public function getForConversation($conversationId)
    {
        return $this->fetchOne(
            "
            SELECT *
            FROM conversation_settings
            WHERE conversation_id = ?
            LIMIT 1
            ",
            [$conversationId]
        );
    }

    public function createDefaultForConversation($conversationId)
    {
        $sql = "
            INSERT INTO conversation_settings (
                conversation_id,
                notifications_muted,
                pinned,
                hide_message_preview,
                auto_download_images
            ) VALUES (?, 0, 0, 0, 0)
        ";

        $this->query($sql, [$conversationId]);

        return $this->getForConversation($conversationId);
    }

    public function saveForConversation($conversationId, array $data)
    {
        $sql = "
            INSERT INTO conversation_settings (
                conversation_id,
                notifications_muted,
                pinned,
                hide_message_preview,
                auto_download_images,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                notifications_muted = VALUES(notifications_muted),
                pinned = VALUES(pinned),
                hide_message_preview = VALUES(hide_message_preview),
                auto_download_images = VALUES(auto_download_images),
                updated_at = NOW()
        ";

        $params = [
            $conversationId,
            $data['notifications_muted'] ?? 0,
            $data['pinned'] ?? 0,
            $data['hide_message_preview'] ?? 0,
            $data['auto_download_images'] ?? 0,
        ];

        return $this->query($sql, $params);
    }
}
