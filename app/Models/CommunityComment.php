<?php

namespace App\Models;

use App\Core\Model;

class CommunityComment extends Model
{
    public function create(array $data)
    {
        $stmt = $this->query(
            "
            INSERT INTO community_comments (
                post_id,
                user_id,
                author_type,
                seller_profile_id,
                parent_id,
                comment
            ) VALUES (?, ?, ?, ?, ?, ?)
            ",
            [
                $data['post_id'],
                $data['user_id'],
                $data['author_type'] ?? 'customer',
                $data['seller_profile_id'] ?? null,
                $data['parent_id'] ?? null,
                $data['comment']
            ]
        );

        return $this->db->lastInsertId();
    }

    public function update($id, $comment)
    {
        return $this->query(
            "
            UPDATE community_comments
            SET comment = ?, updated_at = NOW()
            WHERE id = ?
            ",
            [$comment, $id]
        );
    }

    public function delete($id)
    {
        return $this->query(
            "DELETE FROM community_comments WHERE id = ? OR parent_id = ?",
            [$id, $id]
        );
    }

    public function find($id)
    {
        return $this->fetchOne(
            "SELECT * FROM community_comments WHERE id = ? LIMIT 1",
            [$id]
        );
    }

    public function commentsByPost($postId)
    {
        return $this->fetchAll(
            "
            SELECT c.*, u.first_name, u.last_name, u.role,
                sp.shop_name, sp.farm_logo AS shop_logo,
                COALESCE(sp.verification_badge, '') AS verification_badge,
                EXISTS(SELECT 1 FROM community_comment_likes WHERE comment_id = c.id AND user_id = ?) AS is_liked,
                (SELECT COUNT(*) FROM community_comment_likes WHERE comment_id = c.id) AS like_count
            FROM community_comments c
            INNER JOIN users u ON u.id = c.user_id
            LEFT JOIN seller_profiles sp ON sp.id = c.seller_profile_id AND sp.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
            ",
            [$this->currentUserId(), $postId]
        );
    }

    private function currentUserId()
    {
        return $_SESSION['user']['id'] ?? 0;
    }
}
