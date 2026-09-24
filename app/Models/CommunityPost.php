<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class CommunityPost extends Model
{
    public function create(array $data)
    {
        return $this->query(
            "
            INSERT INTO community_posts (
                user_id,
                category_id,
                title,
                content,
                visibility,
                featured,
                pinned,
                allow_comments,
                original_post_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ",
            [
                $data['user_id'],
                $data['category_id'],
                $data['title'],
                $data['content'],
                $data['visibility'] ?? 'public',
                $data['featured'] ?? 0,
                $data['pinned'] ?? 0,
                $data['allow_comments'] ?? 1,
                $data['original_post_id'] ?? null
            ]
        );
    }

    public function update($id, array $data)
    {
        return $this->query(
            "
            UPDATE community_posts
            SET category_id = ?, title = ?, content = ?, visibility = ?, featured = ?, pinned = ?, allow_comments = ?, original_post_id = ?, updated_at = NOW()
            WHERE id = ?
            ",
            [
                $data['category_id'],
                $data['title'],
                $data['content'],
                $data['visibility'] ?? 'public',
                $data['featured'] ?? 0,
                $data['pinned'] ?? 0,
                $data['allow_comments'] ?? 1,
                $data['original_post_id'] ?? null,
                $id
            ]
        );
    }

    public function delete($id)
    {
        $db = Database::connection();

        $db->beginTransaction();

        $this->query("DELETE FROM community_post_images WHERE post_id = ?", [$id]);
        $this->query("DELETE FROM community_likes WHERE post_id = ?", [$id]);
        $this->query("DELETE FROM community_comments WHERE post_id = ?", [$id]);
        $this->query("DELETE FROM community_saved_posts WHERE post_id = ?", [$id]);
        $this->query("DELETE FROM community_shares WHERE post_id = ? OR original_post_id = ?", [$id, $id]);
        $this->query("DELETE FROM community_reports WHERE post_id = ?", [$id]);
        $this->query("DELETE FROM community_posts WHERE id = ?", [$id]);

        return $db->commit();
    }

    public function softDelete($id)
    {
        return $this->query('UPDATE community_posts SET deleted_at = NOW(), updated_at = NOW() WHERE id = ?', [$id]);
    }

    public function find($id)
    {
        return $this->fetchOne(
            "
            SELECT p.*, c.name AS category_name, u.first_name, u.last_name, u.profile_image, u.role,
                COALESCE(sp.verification_badge, '') AS verification_badge,
                COALESCE(sp.shop_name, '') AS shop_name,
                (SELECT COUNT(*) FROM community_likes WHERE post_id = p.id) AS like_count,
                (SELECT COUNT(*) FROM community_comments WHERE post_id = p.id) AS comment_count,
                COALESCE(p.share_count, 0) AS share_count,
                p.original_post_id,
                op.title AS original_title,
                op.content AS original_content,
                ou.first_name AS original_first_name,
                ou.last_name AS original_last_name,
                ou.role AS original_role
            FROM community_posts p
            INNER JOIN community_categories c ON c.id = p.category_id
            INNER JOIN users u ON u.id = p.user_id
            LEFT JOIN seller_profiles sp ON sp.user_id = u.id
            LEFT JOIN community_posts op ON op.id = p.original_post_id
            LEFT JOIN users ou ON ou.id = op.user_id
            WHERE p.id = ? AND p.deleted_at IS NULL
            LIMIT 1
            ",
            [$id]
        );
    }

    public function fetchFeed($userId = null, $search = null, $categoryId = null)
    {
        $query = [
            "SELECT p.*, c.name AS category_name, u.first_name, u.last_name, u.profile_image, u.role,
                COALESCE(sp.verification_badge, '') AS verification_badge,
                COALESCE(sp.shop_name, '') AS shop_name,
                (SELECT COUNT(*) FROM community_likes WHERE post_id = p.id) AS like_count,
                (SELECT COUNT(*) FROM community_comments WHERE post_id = p.id) AS comment_count,
                (SELECT COUNT(*) FROM community_shares WHERE post_id = p.id) AS share_count,
                    COALESCE((SELECT image_url FROM community_post_images WHERE post_id = p.id ORDER BY id ASC LIMIT 1), '') AS image_url,
                EXISTS(SELECT 1 FROM community_saved_posts WHERE post_id = p.id AND user_id = ?) AS is_saved,
                EXISTS(SELECT 1 FROM community_likes WHERE post_id = p.id AND user_id = ?) AS is_liked,
                p.original_post_id,
                op.title AS original_title,
                ou.first_name AS original_first_name,
                ou.last_name AS original_last_name
            FROM community_posts p
            LEFT JOIN community_posts op ON op.id = p.original_post_id
            LEFT JOIN users ou ON ou.id = op.user_id
            INNER JOIN community_categories c ON c.id = p.category_id
            INNER JOIN users u ON u.id = p.user_id
            LEFT JOIN seller_profiles sp ON sp.user_id = u.id
            WHERE p.deleted_at IS NULL"
        ];

        $params = [$userId ?? 0, $userId ?? 0];

        if (!$userId) {
            $query[] = "AND p.visibility = 'public'";
        }

        if ($search) {
            $query[] = "AND (p.title LIKE ? OR p.content LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR c.name LIKE ?)";
            $term = "%{$search}%";
            $params = array_merge($params, [$term, $term, $term, $term, $term]);
        }

        if ($categoryId) {
            $query[] = "AND p.category_id = ?";
            $params[] = $categoryId;
        }

        $query[] = "ORDER BY p.created_at DESC, p.id DESC";
        $query[] = "LIMIT 24";

        $sql = implode(' ', $query);

        return $this->fetchAll($sql, $params);
    }

    /**
     * Public community activity for a seller's storefront.
     *
     * Store pages are publicly accessible, so member-only posts must never be
     * returned here, even when the visitor is signed in.
     */
    public function fetchPublicByAuthor($authorId, $viewerId = null, $limit = 6)
    {
        $limit = max(1, min(24, (int) $limit));

        return $this->fetchAll(
            "
            SELECT p.*, c.name AS category_name, u.first_name, u.last_name, u.profile_image, u.role,
                COALESCE((SELECT sp.verification_badge FROM seller_profiles sp WHERE sp.user_id = u.id ORDER BY sp.id ASC LIMIT 1), '') AS verification_badge,
                COALESCE((SELECT sp.shop_name FROM seller_profiles sp WHERE sp.user_id = u.id ORDER BY sp.id ASC LIMIT 1), '') AS shop_name,
                (SELECT COUNT(*) FROM community_likes WHERE post_id = p.id) AS like_count,
                (SELECT COUNT(*) FROM community_comments WHERE post_id = p.id) AS comment_count,
                (SELECT COUNT(*) FROM community_shares WHERE post_id = p.id) AS share_count,
                COALESCE((SELECT image_url FROM community_post_images WHERE post_id = p.id ORDER BY id ASC LIMIT 1), '') AS image_url,
                EXISTS(SELECT 1 FROM community_saved_posts WHERE post_id = p.id AND user_id = ?) AS is_saved,
                EXISTS(SELECT 1 FROM community_likes WHERE post_id = p.id AND user_id = ?) AS is_liked,
                p.original_post_id,
                op.title AS original_title,
                ou.first_name AS original_first_name,
                ou.last_name AS original_last_name
            FROM community_posts p
            LEFT JOIN community_posts op ON op.id = p.original_post_id
            LEFT JOIN users ou ON ou.id = op.user_id
            INNER JOIN community_categories c ON c.id = p.category_id
            INNER JOIN users u ON u.id = p.user_id
            WHERE p.user_id = ? AND p.visibility = 'public' AND p.deleted_at IS NULL
            ORDER BY p.created_at DESC, p.id DESC
            LIMIT {$limit}
            ",
            [$viewerId ?? 0, $viewerId ?? 0, $authorId]
        );
    }

    public function share($userId, $originalPostId)
    {
        $original = $this->find($originalPostId);

        if (!$original) {
            return false;
        }

        $this->query(
            "
            INSERT INTO community_posts (
                user_id,
                category_id,
                title,
                content,
                visibility,
                featured,
                pinned,
                allow_comments,
                original_post_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ",
            [
                $userId,
                $original->category_id,
                'Shared: ' . $original->title,
                $original->content,
                $original->visibility,
                0,
                0,
                1,
                $original->id
            ]
        );

        // Read the new ID before any later statement can replace the connection's
        // last-insert value.
        $sharedPostId = Database::connection()->lastInsertId();
        $this->incrementShareCount($original->id);

        return $sharedPostId;
    }

    public function incrementShareCount($postId)
    {
        return $this->query(
            "UPDATE community_posts SET share_count = share_count + 1 WHERE id = ?",
            [$postId]
        );
    }

    public function trendingPosts($limit = 5)
    {
        return $this->fetchAll(
            "
            SELECT p.id, p.title, p.content, c.name AS category_name, u.first_name, u.last_name,
                COALESCE(sp.verification_badge, '') AS verification_badge,
                COALESCE((SELECT COUNT(*) FROM community_likes WHERE post_id = p.id), 0)
                    + COALESCE((SELECT COUNT(*) FROM community_comments WHERE post_id = p.id), 0)
                    + COALESCE((SELECT COUNT(*) FROM community_shares WHERE post_id = p.id), 0) AS activity_score
            FROM community_posts p
            INNER JOIN community_categories c ON c.id = p.category_id
            INNER JOIN users u ON u.id = p.user_id
            LEFT JOIN seller_profiles sp ON sp.user_id = u.id
            WHERE p.visibility = 'public' AND p.deleted_at IS NULL
            ORDER BY activity_score DESC, p.updated_at DESC
            LIMIT ?
            ",
            [$limit]
        );
    }

    public function featuredPosts($limit = 3)
    {
        return $this->fetchAll(
            "
            SELECT p.id, p.title, p.content, c.name AS category_name, u.first_name, u.last_name,
                COALESCE(sp.verification_badge, '') AS verification_badge,
                COALESCE((SELECT image_url FROM community_post_images WHERE post_id = p.id ORDER BY id ASC LIMIT 1), '') AS image_url,
                (SELECT COUNT(*) FROM community_comments WHERE post_id = p.id) AS comment_count,
                (SELECT COUNT(*) FROM community_shares WHERE post_id = p.id) AS share_count
            FROM community_posts p
            INNER JOIN community_categories c ON c.id = p.category_id
            INNER JOIN users u ON u.id = p.user_id
            LEFT JOIN seller_profiles sp ON sp.user_id = u.id
            WHERE p.visibility = 'public' AND p.featured = 1 AND p.deleted_at IS NULL
            ORDER BY p.updated_at DESC, p.created_at DESC
            LIMIT ?
            ",
            [$limit]
        );
    }

    public function authorCanEdit($userId, $postId)
    {
        $post = $this->fetchOne(
            "SELECT user_id FROM community_posts WHERE id = ? AND deleted_at IS NULL LIMIT 1",
            [$postId]
        );

        return $post && ((int) $post->user_id === (int) $userId);
    }

    public function saveImage($postId, $imageUrl)
    {
        return $this->query(
            "INSERT INTO community_post_images (post_id, image_url) VALUES (?, ?) ",
            [$postId, $imageUrl]
        );
    }

    public function images($postId)
    {
        return $this->fetchAll(
            "SELECT * FROM community_post_images WHERE post_id = ? ORDER BY id ASC",
            [$postId]
        );
    }
}
