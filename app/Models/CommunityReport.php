<?php

namespace App\Models;

use App\Core\Model;

class CommunityReport extends Model
{
    public function create($data)
    {
        return $this->query(
            "
            INSERT INTO community_reports (
                post_id,
                comment_id,
                user_id,
                reason,
                status
            ) VALUES (?, ?, ?, ?, ?)
            ",
            [
                $data['post_id'],
                $data['comment_id'] ?? null,
                $data['user_id'],
                $data['reason'],
                $data['status'] ?? 'pending'
            ]
        );
    }

    public function allPending()
    {
        return $this->fetchAll(
            "SELECT cr.*,
                reporter.first_name AS reporter_first_name,
                reporter.last_name AS reporter_last_name,
                reporter.role AS reporter_role,
                p.title AS post_title,
                p.content AS post_content,
                post_author.first_name AS post_author_first_name,
                post_author.last_name AS post_author_last_name,
                cc.comment AS comment_content,
                comment_author.first_name AS comment_author_first_name,
                comment_author.last_name AS comment_author_last_name
            FROM community_reports cr
            LEFT JOIN users reporter ON reporter.id = cr.user_id
            LEFT JOIN community_posts p ON p.id = cr.post_id
            LEFT JOIN users post_author ON post_author.id = p.user_id
            LEFT JOIN community_comments cc ON cc.id = cr.comment_id
            LEFT JOIN users comment_author ON comment_author.id = cc.user_id
            WHERE cr.status = 'pending'
            ORDER BY cr.created_at DESC"
        );
    }

    public function markReviewed($id)
    {
        return $this->query(
            "UPDATE community_reports SET status = 'reviewed' WHERE id = ?",
            [$id]
        );
    }

    public function markDismissed($id)
    {
        return $this->query(
            "UPDATE community_reports SET status = 'dismissed' WHERE id = ?",
            [$id]
        );
    }
}
