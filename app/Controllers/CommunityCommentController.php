<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Services\NotificationService;
use App\Services\SellerProfileService;

class CommunityCommentController extends Controller
{
    public function store()
    {
        csrf_verify();

        $user = Session::get('user');

        if (!$user) {
            $this->redirect('/ani-mart/public/login');
        }

        $postId = $_POST['post_id'] ?? 0;
        $parentId = $_POST['parent_id'] ?? null;
        $comment = trim($_POST['comment'] ?? '');
        $commentAs = ($_POST['comment_as'] ?? 'customer') === 'shop' ? 'shop' : 'customer';

        if (!$postId || strlen($comment) < 3) {
            $this->redirect('/ani-mart/public/community/show?id=' . $postId);
        }

        $postModel = new CommunityPost();
        $post = $postModel->find($postId);

        if (!$post || !$post->allow_comments) {
            $this->redirect('/ani-mart/public/community/show?id=' . $postId);
        }

        if ($parentId) {
            $parent = (new CommunityComment())->find((int) $parentId);
            if (!$parent || (int) $parent->post_id !== (int) $postId) {
                $parentId = null;
            }
        }

        $activeShop = null;
        if ($commentAs === 'shop' && strtolower($user['role'] ?? '') === 'farmer') {
            $activeShop = SellerProfileService::activeShop($user['id']);
        }
        if (!$activeShop) {
            $commentAs = 'customer';
        }

        $commentId = (new CommunityComment())->create([
            'post_id' => $postId,
            'user_id' => $user['id'],
            'author_type' => $commentAs,
            'seller_profile_id' => $activeShop->id ?? null,
            'parent_id' => $parentId,
            'comment' => $comment
        ]);

        $actorName = $activeShop->shop_name
            ?? (trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Someone');
        $commentUrl = "/ani-mart/public/community/show?id={$postId}#comment{$commentId}";

        if ((int) $post->user_id !== (int) $user['id']) {
            NotificationService::community(
                $post->user_id,
                'New Community Comment',
                "{$actorName} commented on your post.",
                'bi-chat-left-text',
                'info',
                $commentUrl,
                'comment',
                $commentId
            );
        }

        if (!empty($parent) && (int) $parent->user_id !== (int) $post->user_id && (int) $parent->user_id !== (int) $user['id']) {
            NotificationService::community(
                $parent->user_id,
                'New Reply to Your Comment',
                "{$actorName} replied to your comment.",
                'bi-reply',
                'info',
                $commentUrl,
                'comment',
                $commentId
            );
        }

        $this->redirect('/ani-mart/public/community/show?id=' . $postId);
    }

    public function update()
    {
        csrf_verify();

        $user = Session::get('user');
        $commentId = $_POST['comment_id'] ?? 0;
        $commentText = trim($_POST['comment'] ?? '');

        if (!$user || !$commentId || strlen($commentText) < 3) {
            $this->redirect('/ani-mart/public/community');
        }

        $commentModel = new CommunityComment();
        $comment = $commentModel->find($commentId);

        if (!$comment || $comment->user_id !== $user['id']) {
            $this->redirect('/ani-mart/public/community');
        }

        $commentModel->update($commentId, $commentText);
        $this->redirect('/ani-mart/public/community/show?id=' . $comment->post_id);
    }

    public function delete()
    {
        csrf_verify();
        
        $user = Session::get('user');
        $commentId = $_POST['comment_id'] ?? 0;

        if (!$user || !$commentId) {
            $this->redirect('/ani-mart/public/community');
        }

        $commentModel = new CommunityComment();
        $comment = $commentModel->find($commentId);

        if (!$comment) {
            $this->redirect('/ani-mart/public/community');
        }

        if ($user['role'] !== 'admin' && $comment->user_id !== $user['id']) {
            $this->redirect('/ani-mart/public/community');
        }

        $commentModel->delete($commentId);
        $this->redirect('/ani-mart/public/community/show?id=' . $comment->post_id);
    }
}
