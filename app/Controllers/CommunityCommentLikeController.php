<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\CommunityComment;
use App\Models\CommunityCommentLike;
use App\Services\NotificationService;

class CommunityCommentLikeController extends Controller
{
    public function toggle()
    {
        csrf_verify();
        
        $user = Session::get('user');
        $commentId = $_POST['comment_id'] ?? 0;

        if (!$user || !$commentId) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'reason' => !$user ? 'unauthenticated' : 'missing_comment_id',
                'cookie_present' => isset($_COOKIE[session_name()])
            ]);
            exit;
        }

        $comment = (new CommunityComment())->find((int) $commentId);
        if (!$comment) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'reason' => 'comment_not_found']);
            exit;
        }

        $commentLike = new CommunityCommentLike();
        $liked = $commentLike->toggle($user['id'], $commentId);
        $count = $commentLike->count($commentId);

        if ($liked && (int) $comment->user_id !== (int) $user['id']) {
            $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Someone';
            $url = "/ani-mart/public/community/show?id={$comment->post_id}#comment{$commentId}";
            NotificationService::community(
                $comment->user_id,
                'Your Comment Was Liked',
                "{$actorName} liked your community comment.",
                'bi-hand-thumbs-up-fill',
                'success',
                $url,
                'comment',
                $commentId
            );
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'liked' => $liked, 'count' => $count]);
        exit;
    }
}
