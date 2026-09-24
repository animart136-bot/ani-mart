<?php

use App\Core\Session;

$title = 'Ani-Mart AgriConnect';
$bodyClass = 'community-feed-page';
$user = Session::get('user');
$categories = $categories ?? [];
$posts = $posts ?? [];
$featuredPosts = $featuredPosts ?? [];
$trendingPosts = $trendingPosts ?? [];
$pageStyles[] = '/ani-mart/public/assets/css/pages/community.css?v=' . filemtime(__DIR__ . '/../../../public/assets/css/pages/community.css');

ob_start();
?>

<div class="community-feed container app-page-spacing">

    <div class="hero mb-5">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="hero-badge">Community Hub</span>
                <h1 class="hero-title mt-3">Ani-Mart AgriConnect</h1>
                <p class="hero-subtitle mt-3">Connect with verified farmers, community leaders, and local agriculture announcements.</p>
            </div>
            <div class="col-lg-5 text-lg-end mt-4 mt-lg-0">
                <div class="d-inline-flex gap-2 flex-wrap justify-content-lg-end">
                    <?php if ($user): ?>
                        <a href="/ani-mart/public/community/create" class="btn btn-success rounded-pill">Create Post</a>
                        <a href="/ani-mart/public/community/saved" class="btn btn-outline-success rounded-pill">Saved Posts</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <section class="card border-0 shadow-sm rounded-4 p-4 mb-4 community-trending" aria-labelledby="community-trending-title">
        <h5 class="fw-bold mb-3" id="community-trending-title">Trending Today</h5>
        <?php if (empty($trendingPosts)): ?>
            <p class="text-muted mb-0">No trending activity yet.</p>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($trendingPosts as $trending): ?>
                    <div class="col-md-6 col-xl-4">
                        <a href="/ani-mart/public/community/show?id=<?= (int) $trending->id ?>" class="community-trending-item d-block h-100 rounded-3 p-3 text-decoration-none">
                            <h6 class="mb-1 text-body"><?= htmlspecialchars($trending->title) ?></h6>
                            <p class="text-muted small mb-0"><?= (int) $trending->activity_score ?> activity</p>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <div class="row gy-4">

        <div class="col-12">

            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <form class="row g-3" method="GET" action="/ani-mart/public/community">
                    <div class="col-md-10">
                        <input type="search" class="form-control" name="q" placeholder="Search posts and authors..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-success" type="submit">Go</button>
                    </div>
                </form>
            </div>

            <?php if (empty($posts)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                    <h2 class="h5">No community posts found</h2>
                    <p class="text-muted mb-0">Try another category, keyword, or come back later.</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <?php require __DIR__ . '/partials/post-card.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

    </div>

</div>

<script>
const communityFeedCsrfToken = <?= json_encode(csrf_token()) ?>;
const communityFeedShopName = <?= json_encode($activeShop->shop_name ?? '') ?>;

document.querySelectorAll('.community-like-btn').forEach(button => {
    button.addEventListener('click', async event => {
        event.preventDefault();
        button.disabled = true;

        const formData = new FormData();
        formData.append('_token', communityFeedCsrfToken);
        formData.append('post_id', button.dataset.postId);

        try {
            const response = await fetch('/ani-mart/public/community/likes/toggle', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Accept': 'application/json' },
                body: formData
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.reason || 'Like action failed');
            }

            button.textContent = data.liked ? 'Unlike' : 'Like';
            button.classList.toggle('btn-success', data.liked);
            button.classList.toggle('btn-outline-success', !data.liked);

            const count = document.querySelector(
                `.community-post-like-count[data-post-id="${button.dataset.postId}"] span`
            );
            if (count) {
                count.textContent = data.count;
            }
        } catch (error) {
            console.error('Community like failed', error);
            alert('Unable to like this post. Please refresh and try again.');
        } finally {
            button.disabled = false;
        }
    });
});

document.querySelectorAll('.community-save-btn').forEach(button => {
    button.addEventListener('click', event => {
        event.preventDefault();
        const originalText = button.textContent;
        button.disabled = true;

        const formData = new FormData();
        formData.append('_token', communityFeedCsrfToken);
        formData.append('post_id', button.dataset.postId);

        fetch('/ani-mart/public/community/saves/toggle', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Accept': 'application/json' },
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error('Save action failed');
                }

                button.textContent = data.saved ? 'Saved' : 'Save';
                button.classList.toggle('btn-secondary', data.saved);
                button.classList.toggle('btn-outline-secondary', !data.saved);
            })
            .catch(error => {
                console.error('Community save failed', error);
                button.textContent = originalText.trim();
                alert('Unable to save this post. Please refresh and try again.');
            })
            .finally(() => {
                button.disabled = false;
            });
    });
});

document.querySelectorAll('.community-share-btn').forEach(button => {
    button.addEventListener('click', async event => {
        event.preventDefault();
        button.disabled = true;

        const formData = new FormData();
        formData.append('_token', communityFeedCsrfToken);
        formData.append('post_id', button.dataset.postId);

        try {
            const response = await fetch('/ani-mart/public/community/shares/create', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Accept': 'application/json' },
                body: formData
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.reason || 'Share action failed');
            }

            window.location.href = '/ani-mart/public/community/show?id=' + data.shared_post_id;
        } catch (error) {
            console.error('Community share failed', error);
            alert('Unable to share this post. Please refresh and try again.');
            button.disabled = false;
        }
    });
});

document.querySelectorAll('.community-feed-comment-like').forEach(button => {
    button.addEventListener('click', async () => {
        button.disabled = true;
        const formData = new FormData();
        formData.append('_token', communityFeedCsrfToken);
        formData.append('comment_id', button.dataset.commentId);

        try {
            const response = await fetch('/ani-mart/public/community/comments/likes/toggle', {
                method: 'POST', credentials: 'include', headers: { 'Accept': 'application/json' }, body: formData
            });
            const data = await response.json();
            if (!data.success) throw new Error('Like action failed');

            button.classList.toggle('is-liked', data.liked);
            button.querySelector('i').className = `bi ${data.liked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up'}`;
            button.querySelector('span').textContent = data.liked ? 'Liked' : 'Like';
            button.closest('.community-feed-comment-actions').querySelector('.community-feed-comment-like-count').textContent = data.count;
        } catch (error) {
            console.error('Comment like failed', error);
            alert('Unable to update this comment. Please try again.');
        } finally {
            button.disabled = false;
        }
    });
});

document.querySelectorAll('.community-feed-comment-reply').forEach(button => {
    button.addEventListener('click', () => {
        const comment = button.closest('.community-feed-comment');
        const existing = comment.querySelector('.community-inline-reply-form');
        document.querySelectorAll('.community-inline-reply-form').forEach(form => form.remove());
        if (existing) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/ani-mart/public/community/comments/store';
        form.className = 'community-inline-reply-form mt-2';
        form.innerHTML = `
            <input type="hidden" name="_token" value="${communityFeedCsrfToken}">
            <input type="hidden" name="post_id" value="${comment.dataset.postId}">
            <input type="hidden" name="parent_id" value="${button.dataset.commentId}">
            ${communityFeedShopName ? `<select class="form-select form-select-sm mb-2" name="comment_as"><option value="customer">Reply as myself</option><option value="shop">Reply as shop</option></select>` : ''}
            <div class="d-flex gap-2">
                <input class="form-control form-control-sm rounded-pill" name="comment" minlength="3" maxlength="1000" placeholder="Write a reply..." required>
                <button class="btn btn-sm btn-success rounded-pill px-3" type="submit">Reply</button>
                <button class="btn btn-sm btn-outline-secondary rounded-pill community-reply-cancel" type="button">Cancel</button>
            </div>`;
        comment.appendChild(form);
        form.querySelector('.community-reply-cancel').addEventListener('click', () => form.remove());
        form.querySelector('input[name="comment"]').focus();
    });
});
</script>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
