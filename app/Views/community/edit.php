<?php

use App\Core\Session;

$title = 'Edit Community Post';
$bodyClass = 'community-edit-page';
$user = Session::get('user');
$post = $post ?? null;
$pageStyles[] = '/ani-mart/public/assets/css/pages/community-edit.css?v=' .
    filemtime(__DIR__ . '/../../../public/assets/css/pages/community-edit.css');

ob_start();
?>

<main class="container app-page-spacing community-edit-shell">
    <section class="hero community-edit-hero" aria-labelledby="community-edit-title">
        <div>
            <span class="hero-badge">Community post</span>
            <h1 class="hero-title" id="community-edit-title">Edit your community post</h1>
            <p class="hero-subtitle">Refine the post content and comment settings. Existing images remain attached.</p>
        </div>
        <a href="/ani-mart/public/community/show?id=<?= (int) $post->id ?>" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i> Back to Post</a>
    </section>

    <div class="card community-edit-card">
        <div class="community-edit-heading">
            <div>
                <span class="text-success fw-bold small text-uppercase">Edit Post</span>
                <h2 class="fw-bold mb-1">Update the post details</h2>
                <p class="text-muted mb-0">Edit the post content and visibility. Your uploaded images will remain attached.</p>
            </div>
        </div>

        <form method="POST" action="/ani-mart/public/community/posts/update">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="post_id" value="<?= (int) $post->id ?>">

            <div class="row g-4">
                <div class="col-lg-8">
                    <label class="form-label fw-semibold" for="communityContent">Content</label>
                    <textarea id="communityContent" name="content" class="form-control" rows="10" required><?= htmlspecialchars($post->content ?? '') ?></textarea>
                </div>
                <div class="col-lg-4">
                    <div class="community-edit-settings h-100">
                        <h5 class="fw-bold mb-3">Post Settings</h5>
                        <div class="mb-3">
                            <label class="form-label" for="visibility">Visibility</label>
                            <select id="visibility" class="form-select" name="visibility" disabled>
                                <option value="public" selected>Public</option>
                            </select>
                        </div>
                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" id="allowComments" name="allow_comments" <?= !empty($post->allow_comments) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="allowComments">Allow comments</label>
                        </div>
                        <button type="submit" class="btn btn-success rounded-pill w-100 mt-auto">
                            <i class="bi bi-check2 me-1"></i>Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
