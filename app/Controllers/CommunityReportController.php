<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\NotificationService;
use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\CommunityReport;

class CommunityReportController extends Controller
{
    public function store()
    {
        csrf_verify();
        
        $user = Session::get('user');
        $postId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : null;
        $commentId = isset($_POST['comment_id']) ? (int) $_POST['comment_id'] : null;
        $reason = trim((string) ($_POST['reason'] ?? ''));
        $allowedReasons = [
            'Spam',
            'False Information',
            'Offensive Content',
            'Harassment',
            'Other',
        ];

        if (!$user || (!$postId && !$commentId) || !in_array($reason, $allowedReasons, true)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please select a valid report reason.']);
            exit;
        }

        if ($postId) {
            $post = (new CommunityPost())->find($postId);
            if (!$post || !$this->canViewPost($user, $post)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false]);
                exit;
            }
        }

        if ($commentId) {
            $comment = (new CommunityComment())->find($commentId);
            if (!$comment) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false]);
                exit;
            }

            $post = (new CommunityPost())->find($comment->post_id);
            if (!$post || !$this->canViewPost($user, $post)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false]);
                exit;
            }
        }

        $reportModel = new CommunityReport();
        $reportModel->create([
            'post_id' => $postId,
            'comment_id' => $commentId,
            'user_id' => $user['id'],
            'reason' => $reason,
            'status' => 'pending'
        ]);

        NotificationService::send(
            1,
            'Community Report Submitted',
            sprintf('%s submitted a community report.', $user['first_name'] ?? 'A user'),
            'complaint',
            'bi-exclamation-triangle',
            'danger',
            '/ani-mart/public/admin/analytics'
        );

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
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
