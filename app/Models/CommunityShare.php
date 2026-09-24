<?php

namespace App\Models;

use App\Core\Model;

class CommunityShare extends Model
{
    public function create($userId, $postId, $originalPostId = null)
    {
        return $this->query(
            "INSERT INTO community_shares (user_id, post_id, original_post_id) VALUES (?, ?, ?)",
            [$userId, $postId, $originalPostId]
        );
    }

    public function count($postId)
    {
        $result = $this->fetchOne(
            "SELECT COUNT(*) AS total FROM community_shares WHERE post_id = ?",
            [$postId]
        );

        return $result->total ?? 0;
    }
}
