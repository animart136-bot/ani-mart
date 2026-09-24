<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\CommunityLike;
use App\Models\CommunityPost;
use App\Services\NotificationService;

class CommunityLikeController extends Controller
{
    public function toggle()
    {
        csrf_verify();
        
        $user = Session::get('user');
        $postId = (int) ($_POST['post_id'] ?? 0);

        if (!$user || !$postId) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'reason' => !$user ? 'unauthenticated' : 'missing_post_id',
                'cookie_present' => isset($_COOKIE[session_name()])
            ]);
            exit;
        }

        $post = (new CommunityPost())->find($postId);

        if (!$post || !$this->canViewPost($user, $post)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'reason' => 'unauthorized_post']);
            exit;
        }

        $likeModel = new CommunityLike();
        $liked = $likeModel->toggle($user['id'], $postId);
        $count = $likeModel->count($postId);

        if ($liked && (int) $post->user_id !== (int) $user['id']) {
            $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Someone';
            $url = "/ani-mart/public/community/show?id={$postId}";
            NotificationService::community(
                $post->user_id,
                'Your Post Was Liked',
                "{$actorName} liked your community post.",
                'bi-hand-thumbs-up-fill',
                'success',
                $url,
                'community_post',
                $postId
            );
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'liked' => $liked,
            'count' => $count
        ]);
        exit;
    }

    private function canViewPost($user, $post)
    {
        if (!$post) {
            return false;
        }

        if ($post->visibility === 'public') {
            return true;
        }

        if ((int) ($user['id'] ?? 0) === (int) ($post->user_id ?? 0)) {
            return true;
        }

        return strtolower($user['role'] ?? '') === 'admin';
    }
}
