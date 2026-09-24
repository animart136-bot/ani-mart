<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\CommunityPost;
use App\Models\CommunityShare;
use App\Services\NotificationService;

class CommunityShareController extends Controller
{
    public function create()
    {
        $user = Session::get('user');
        $postId = (int) ($_POST['post_id'] ?? 0);

        if (!$user || !$postId) {
            header('Content-Type: application/json');
            http_response_code(!$user ? 401 : 422);
            echo json_encode([
                'success' => false,
                'reason' => !$user ? 'unauthenticated' : 'missing_post_id',
                'cookie_present' => isset($_COOKIE[session_name()])
            ]);
            exit;
        }

        csrf_verify();

        $postModel = new CommunityPost();
        $post = $postModel->find($postId);

        if (!$post || !$this->canViewPost($user, $post)) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'reason' => 'unauthorized_post']);
            exit;
        }

        $db = Database::connection();

        try {
            $db->beginTransaction();

            // A share is a new feed post that points back to the post being shared.
            // Keep a separate interaction row so feed/trending share totals remain accurate.
            $sharedPostId = (int) $postModel->share($user['id'], $postId);
            (new CommunityShare())->create($user['id'], $postId, $sharedPostId);

            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            error_log('Community share failed: ' . $exception->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'reason' => 'share_failed']);
            exit;
        }

        if ((int) $post->user_id !== (int) $user['id']) {
            try {
                $actorName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Someone';
                $url = "/ani-mart/public/community/show?id={$postId}";
                NotificationService::community(
                    $post->user_id,
                    'Your Post Was Shared',
                    "{$actorName} shared your community post.",
                    'bi-share-fill',
                    'info',
                    $url,
                    'community_post',
                    $postId
                );
            } catch (\Throwable $exception) {
                // The share is already committed; notification failure must not make
                // the UI report that the share itself failed.
                error_log('Community share notification failed: ' . $exception->getMessage());
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'shared_post_id' => $sharedPostId,
            'share_count' => (new CommunityShare())->count($postId)
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
