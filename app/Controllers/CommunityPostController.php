<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\CloudinaryService;
use App\Services\NotificationService;
use App\Models\CommunityPost;
use App\Models\CommunityPostImage;

class CommunityPostController extends Controller
{
    public function edit()
    {
        $user = Session::get('user');
        $postId = (int) ($_GET['id'] ?? 0);

        if (!$user) {
            Session::flash('error', 'Please sign in to edit a community post.');
            $this->redirect('/ani-mart/public/login');
        }

        $model = new CommunityPost();
        $post = $model->find($postId);

        if (!$post) {
            Session::flash('error', 'Community post not found.');
            $this->redirect('/ani-mart/public/community');
        }

        $canEdit = $user && $post && (
            $model->authorCanEdit($user['id'], $postId)
            || strtolower((string) ($user['role'] ?? '')) === 'admin'
        );

        if (!$canEdit) {
            Session::flash('error', 'You can only edit your own community posts.');
            $this->redirect('/ani-mart/public/community');
        }

        $this->view('community/edit', [
            'title' => 'Edit Community Post',
            'post' => $post
        ]);
    }

    public function store()
    {
        csrf_verify();

        $user = Session::get('user');

        if (!$user) {
            $this->redirect('/ani-mart/public/login');
        }

        $content = trim($_POST['content'] ?? '');
        $visibility = 'public';
        $allowComments = isset($_POST['allow_comments']) ? 1 : 0;
        $featured = isset($_POST['featured']) ? 1 : 0;
        $pinned = isset($_POST['pinned']) ? 1 : 0;

        $title = trim(strtok($content, "\n"));
        $title = mb_substr($title ?: 'Community Update', 0, 255);
        $db = Database::connection();
        $categoryId = $db
            ->query("SELECT id FROM community_categories ORDER BY id ASC LIMIT 1")
            ->fetchColumn();

        if (!$categoryId) {
            $db->exec("INSERT INTO community_categories (name, description) VALUES ('General', 'General community updates and discussions')");
            $categoryId = $db->lastInsertId();
        }

        if ($content === '') {
            Session::flash('error', 'Please write some content before publishing.');
            $this->redirect('/ani-mart/public/community/create');
        }

        $postModel = new CommunityPost();
        $postModel->create([
            'user_id' => $user['id'],
            'category_id' => $categoryId,
            'title' => $title,
            'content' => $content,
            'visibility' => $visibility,
            'featured' => $featured,
            'pinned' => $pinned,
            'allow_comments' => $allowComments
        ]);

        $postId = Database::connection()->lastInsertId();

        if (!empty($_FILES['images']['name'][0])) {
            $cloudinary = new CloudinaryService();

            foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
                if (!$tmpName) {
                    continue;
                }

                if ($_FILES['images']['size'][$index] > 4 * 1024 * 1024) {
                    continue;
                }

                $result = $cloudinary->uploadPublic([
                    'name' => $_FILES['images']['name'][$index] ?? '',
                    'tmp_name' => $tmpName,
                    'size' => $_FILES['images']['size'][$index] ?? 0,
                    'type' => $_FILES['images']['type'][$index] ?? '',
                    'error' => $_FILES['images']['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                ], 'ani-mart/community', [
                    'entity_type' => 'community_post',
                    'entity_id' => (int) $postId,
                    'owner_user_id' => (int) $user['id'],
                ]);
                if (!empty($result['secure_url'])) {
                    (new CommunityPostImage())->create($postId, $result['secure_url']);
                }
            }
        }

        NotificationService::send(
            $user['id'],
            'Community Post Published',
            'Your post has been published to Ani-Mart AgriConnect.',
            'promotion',
            'bi-chat-left-text',
            'success',
            "/ani-mart/public/community/show?id={$postId}",
            'community_post',
            $postId,
            "/ani-mart/public/community/show?id={$postId}"
        );

        $adminStmt = $db->query("SELECT id FROM users WHERE role = 'admin'");
        foreach ($adminStmt->fetchAll() as $admin) {
            if ((int) $admin->id === (int) $user['id']) {
                continue;
            }

            NotificationService::send(
                $admin->id,
                'New Community Post',
                'A new community post is ready for review.',
                'promotion',
                'bi-chat-left-text',
                'success',
                "/ani-mart/public/community/show?id={$postId}",
                'community_post',
                $postId,
                "/ani-mart/public/community/show?id={$postId}"
            );
        }

        Session::flash('success', 'Community post published successfully.');

        $this->redirect('/ani-mart/public/community/show?id=' . $postId);
    }

    public function delete()
    {
        csrf_verify();
        
        $user = Session::get('user');
        $postId = $_POST['post_id'] ?? 0;

        if (!$user || !$postId) {
            $this->redirect('/ani-mart/public/community');
        }

        $model = new CommunityPost();

        if ($user['role'] !== 'admin' && !$model->authorCanEdit($user['id'], $postId)) {
            $this->redirect('/ani-mart/public/community');
        }

        if (strtolower((string) $user['role']) === 'admin') {
            $cloudinary = new CloudinaryService();
            foreach ($model->images($postId) as $image) {
                $cloudinary->deleteByUrl((string) $image->image_url, true);
            }
            $model->delete($postId);
            Session::flash('success', 'Post and its Cloudinary images were permanently deleted.');
        } else {
            $model->softDelete($postId);
            Session::flash('success', 'Post removed. Its images remain recoverable for 30 days.');
            $cloudinary = new CloudinaryService();
            foreach ($model->images($postId) as $image) {
                $asset = $cloudinary->findByUrl((string) $image->image_url);
                if ($asset) {
                    $cloudinary->scheduleDeletion((string) $asset->public_id, 30);
                }
            }
        }
        $this->redirect('/ani-mart/public/community');
    }

    public function update()
    {
        csrf_verify();

        $user = Session::get('user');
        $postId = (int) ($_POST['post_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        $model = new CommunityPost();
        $post = $model->find($postId);

        $canEdit = $user && $post && (
            $model->authorCanEdit($user['id'], $postId)
            || strtolower((string) ($user['role'] ?? '')) === 'admin'
        );

        if (!$canEdit) {
            Session::flash('error', 'You do not have permission to update this community post.');
            $this->redirect('/ani-mart/public/community');
        }

        if ($content === '') {
            Session::flash('error', 'Please write some content before saving.');
            $this->redirect('/ani-mart/public/community/posts/edit?id=' . $postId);
        }

        $title = mb_substr(trim(strtok($content, "\n")) ?: 'Community Update', 0, 255);
        $model->update($postId, [
            'category_id' => $post->category_id,
            'title' => $title,
            'content' => $content,
            'visibility' => 'public',
            'featured' => $post->featured,
            'pinned' => $post->pinned,
            'allow_comments' => isset($_POST['allow_comments']) ? 1 : 0,
            'original_post_id' => $post->original_post_id
        ]);

        Session::flash('success', 'Community post updated successfully.');
        $this->redirect('/ani-mart/public/community/show?id=' . $postId);
    }
}
