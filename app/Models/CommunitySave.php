<?php

namespace App\Models;

use App\Core\Model;

class CommunitySave extends Model
{
    public function toggle($userId, $postId)
    {
        $existing = $this->fetchOne(
            "SELECT id FROM community_saved_posts WHERE user_id = ? AND post_id = ? LIMIT 1",
            [$userId, $postId]
        );

        if ($existing) {
            $this->query(
                "DELETE FROM community_saved_posts WHERE id = ?",
                [$existing->id]
            );
            return false;
        }

        $this->query(
            "INSERT INTO community_saved_posts (user_id, post_id) VALUES (?, ?)",
            [$userId, $postId]
        );

        return true;
    }

    public function allForUser($userId)
    {
        return $this->fetchAll(
            "
            SELECT p.*, c.name AS category_name,
                u.first_name, u.last_name, u.profile_image, u.role,
                COALESCE(sp.verification_badge, '') AS verification_badge,
                COALESCE(sp.shop_name, '') AS shop_name,
                COALESCE((SELECT COUNT(*) FROM community_likes WHERE post_id = p.id), 0) AS like_count,
                COALESCE((SELECT COUNT(*) FROM community_comments WHERE post_id = p.id), 0) AS comment_count,
                COALESCE((SELECT COUNT(*) FROM community_shares WHERE post_id = p.id), 0) AS share_count,
                COALESCE((SELECT image_url FROM community_post_images WHERE post_id = p.id ORDER BY id ASC LIMIT 1), '') AS image_url,
                1 AS is_saved,
                EXISTS(
                    SELECT 1 FROM community_likes
                    WHERE post_id = p.id AND user_id = s.user_id
                ) AS is_liked
            FROM community_saved_posts s
            INNER JOIN community_posts p ON p.id = s.post_id
            INNER JOIN community_categories c ON c.id = p.category_id
            INNER JOIN users u ON u.id = p.user_id
            LEFT JOIN seller_profiles sp ON sp.user_id = u.id
            WHERE s.user_id = ?
            ORDER BY s.created_at DESC
            ",
            [$userId]
        );
    }
}
