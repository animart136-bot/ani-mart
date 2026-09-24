<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\CommunityCategory;
use App\Models\CommunityPost;
use App\Models\CommunityComment;
use App\Models\CommunityLike;
use App\Models\CommunitySave;
use App\Models\CommunityShare;
use App\Models\CommunityReport;
use App\Services\NotificationService;
use App\Services\SellerProfileService;

class CommunityController extends Controller
{
    public function index()
    {
        $user = Session::get('user');
        $search = $_GET['q'] ?? null;
        $categoryId = $_GET['category'] ?? null;
        $activeShop = $user && strtolower($user['role'] ?? '') === 'farmer'
            ? SellerProfileService::activeShop($user['id'])
            : null;

        $postModel = new CommunityPost();
        $categoryModel = new CommunityCategory();
        $posts = $postModel->fetchFeed($user['id'] ?? null, $search, $categoryId);

        foreach ($posts as $post) {
            $post->images = $postModel->images($post->id);
            $post->comments = (new CommunityComment())->commentsByPost($post->id);
        }

        $this->view('community/index', [
            'title' => 'Ani-Mart AgriConnect',
            'bodyClass' => 'community-feed-page',
            'categories' => $categoryModel->all(),
            'posts' => $posts,
            'featuredPosts' => $postModel->featuredPosts(3),
            'trendingPosts' => $postModel->trendingPosts(3),
            'savedPosts' => $user ? (new CommunitySave())->allForUser($user['id']) : [],
            'activeShop' => $activeShop,
            'pageCss' => '/ani-mart/public/assets/css/pages/community.css'
        ]);
    }

    public function show()
    {
        $postId = $_GET['id'] ?? 0;
        $user = Session::get('user');
        $activeShop = $user && strtolower($user['role'] ?? '') === 'farmer'
            ? SellerProfileService::activeShop($user['id'])
            : null;

        $post = (new CommunityPost())->find($postId);
        $comments = (new CommunityComment())->commentsByPost($postId);
        $images = (new CommunityPost())->images($postId);

        if (!$post) {
            $this->redirect('/ani-mart/public/community');
        }

        $canViewPost = $post->visibility === 'public'
            || ($user && ($user['id'] == $post->user_id || strtolower($user['role'] ?? '') === 'admin'));

        if (!$canViewPost) {
            $this->redirect('/ani-mart/public/community');
        }

        $canEdit = $user && (
            (int) $user['id'] === (int) $post->user_id
            || strtolower((string) ($user['role'] ?? '')) === 'admin'
        );

        $this->view('community/show', [
            'title' => htmlspecialchars($post->title) . ' | Ani-Mart AgriConnect',
            'post' => $post,
            'images' => $images,
            'comments' => $comments,
            'currentUser' => $user,
            'canEdit' => $canEdit,
            'activeShop' => $activeShop,
            'pageCss' => '/ani-mart/public/assets/css/pages/community.css'
        ]);
    }

    public function saved()
    {
        $user = Session::get('user');

        if (!$user) {
            $this->redirect('/ani-mart/public/login');
        }

        $savedPosts = (new CommunitySave())->allForUser($user['id']);

        $this->view('community/saved', [
            'title' => 'Saved Posts | Ani-Mart AgriConnect',
            'savedPosts' => $savedPosts,
            'pageCss' => '/ani-mart/public/assets/css/pages/community.css'
        ]);
    }

    public function create()
    {
        $user = Session::get('user');

        if (!$user) {
            $this->redirect('/ani-mart/public/login');
        }

        $categoryModel = new CommunityCategory();

        $this->view('community/create', [
            'title' => 'Create Community Post',
            'categories' => $categoryModel->all(),
            'pageCss' => '/ani-mart/public/assets/css/pages/community.css'
        ]);
    }
}
