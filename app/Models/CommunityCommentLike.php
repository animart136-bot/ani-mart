<?php

namespace App\Models;

use App\Core\Model;

class CommunityCommentLike extends Model
{
    public function toggle($userId, $commentId)
    {
        $existing = $this->fetchOne(
            "SELECT id FROM community_comment_likes WHERE user_id = ? AND comment_id = ? LIMIT 1",
            [$userId, $commentId]
        );

        if ($existing) {
            $this->query(
                "DELETE FROM community_comment_likes WHERE id = ?",
                [$existing->id]
            );
            return false;
        }

        $this->query(
            "INSERT INTO community_comment_likes (user_id, comment_id) VALUES (?, ?)",
            [$userId, $commentId]
        );

        return true;
    }

    public function count($commentId)
    {
        $result = $this->fetchOne(
            "SELECT COUNT(*) AS total FROM community_comment_likes WHERE comment_id = ?",
            [$commentId]
        );

        return $result->total ?? 0;
    }
}
