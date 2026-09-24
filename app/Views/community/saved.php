<?php

use App\Core\Session;

$title = 'Saved Posts | Ani-Mart AgriConnect';
$bodyClass = 'community-saved-page';
$user = Session::get('user');
$savedPosts = $savedPosts ?? [];
$pageStyles[] = '/ani-mart/public/assets/css/pages/community.css?v=' . filemtime(__DIR__ . '/../../../public/assets/css/pages/community.css');

ob_start();
?>

<div class="container app-page-spacing">
    <section class="hero mb-5" aria-labelledby="saved-posts-title">
        <div class="row align-items-center position-relative">
            <div class="col-lg-8">
                <span class="hero-badge">
                    Saved Posts
                </span>
                <h1 class="hero-title mt-3 mb-3" id="saved-posts-title">Your community bookmarks</h1>
                <p class="hero-subtitle mb-4">
                    Keep useful farming tips, local announcements, and community updates together for easy access.
                </p>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <a href="/ani-mart/public/community" class="btn btn-light text-success fw-semibold rounded-pill px-4">
                        <i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Back to Community Feed
                    </a>
                    <span class="text-white small fw-semibold">
                        <?= count($savedPosts) ?> saved <?= count($savedPosts) === 1 ? 'post' : 'posts' ?>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <?php if (empty($savedPosts)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
            <h2 class="h5">No saved posts yet</h2>
            <p class="text-muted mb-0">Save posts from the feed to quickly find useful announcements and farmer updates.</p>
        </div>
    <?php else: ?>
        <?php foreach ($savedPosts as $post): ?>
            <?php require __DIR__ . '/partials/post-card.php'; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
