<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\CommunityPost;
use App\Models\CommunitySave;
use App\Services\NotificationService;

class CommunitySaveController extends Controller
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

        $saveModel = new CommunitySave();
        $saved = $saveModel->toggle($user['id'], $postId);

        if ($saved && (int) $post->user_id !== (int) $user['id']) {
            $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Someone';
            $url = "/ani-mart/public/community/show?id={$postId}";
            NotificationService::community(
                $post->user_id,
                'Your Post Was Saved',
                "{$actorName} saved your community post.",
                'bi-bookmark-fill',
                'primary',
                $url,
                'community_post',
                $postId
            );
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'saved' => $saved
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
