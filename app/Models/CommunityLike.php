<?php

namespace App\Models;

use App\Core\Model;

class CommunityLike extends Model
{
    public function toggle($userId, $postId)
    {
        $existing = $this->fetchOne(
            "SELECT id FROM community_likes WHERE post_id = ? AND user_id = ? LIMIT 1",
            [$postId, $userId]
        );

        if ($existing) {
            $this->query(
                "DELETE FROM community_likes WHERE id = ?",
                [$existing->id]
            );
            return false;
        }

        $this->query(
            "INSERT INTO community_likes (post_id, user_id) VALUES (?, ?)",
            [$postId, $userId]
        );

        return true;
    }

    public function count($postId)
    {
        $result = $this->fetchOne(
            "SELECT COUNT(*) AS total FROM community_likes WHERE post_id = ?",
            [$postId]
        );

        return $result->total ?? 0;
    }
}
