<?php

use App\Core\Session;
use App\Services\BadgeCalculationService;

$title = $title ?? 'Community Post';
$bodyClass = 'community-post-page';
$user = Session::get('user');
$post = $post ?? null;
$comments = $comments ?? [];
$images = $images ?? [];
$canEdit = $canEdit ?? false;
$activeShop = $activeShop ?? null;

$pageStyles[] = '/ani-mart/public/assets/css/pages/community-post.css?v=' . filemtime(__DIR__ . '/../../../public/assets/css/pages/community-post.css');

ob_start();

$commentTree = [];
foreach ($comments as $comment) {
    $parentId = $comment->parent_id ?? 0;
    $commentTree[$parentId][] = $comment;
}

$renderComment = function ($comment) use (&$renderComment, $commentTree, $user) {
    ob_start();
    $isShopComment = ($comment->author_type ?? 'customer') === 'shop' && !empty($comment->shop_name);
    $authorName = $isShopComment
        ? $comment->shop_name
        : trim(($comment->first_name ?? '') . ' ' . ($comment->last_name ?? ''));
    ?>
    <div id="comment<?= (int) $comment->id ?>" class="community-comment mb-3 <?= $comment->parent_id ? 'community-comment-reply' : '' ?>">
        <div class="d-flex align-items-start gap-3">
            <div class="comment-avatar rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                <?= htmlspecialchars(strtoupper(substr($authorName ?: 'U', 0, 1))) ?>
            </div>
            <div class="w-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <strong><?= htmlspecialchars($authorName) ?></strong>
                        <?php if ($user && (int) ($user['id'] ?? 0) !== (int) ($comment->user_id ?? 0)): ?>
                            <a class="account-message-link ms-1" href="/ani-mart/public/messages/start?user_id=<?= (int) $comment->user_id ?>" aria-label="Message <?= htmlspecialchars($authorName) ?>" title="Send message">
                                <i class="bi bi-chat-dots" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>
                        <span class="text-muted small">· <?= $isShopComment ? 'Shop' : 'Customer' ?></span>
                        <?php if (!empty($comment->verification_badge)): ?>
                            <span class="badge bg-success text-white rounded-pill ms-2">Verified</span>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted"><?= date('M j, Y g:i A', strtotime($comment->created_at)) ?></small>
                </div>
                <p class="mb-2"><?= nl2br(htmlspecialchars($comment->comment)) ?></p>
                <div class="d-flex gap-3 align-items-center small text-muted">
                    <button class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 community-comment-like-btn" data-comment-id="<?= (int) $comment->id ?>">
                        <i class="bi bi-hand-thumbs-up"></i>
                        <?= (int) ($comment->like_count ?? 0) ?>
                    </button>
                    <?php if ($user): ?>
                        <button class="btn btn-sm btn-outline-secondary community-reply-btn" data-parent-id="<?= (int) $comment->id ?>">Reply</button>
                    <?php endif; ?>
                    <?php if ($user && ($user['role'] === 'admin' || $user['id'] == $comment->user_id)): ?>
                        <form class="d-inline" method="POST" action="/ani-mart/public/community/comments/delete">
                            <input 
    type="hidden" 
    name="_token" 
    value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input type="hidden" name="comment_id" value="<?= (int) $comment->id ?>">
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if (!empty($commentTree[$comment->id])): ?>
            <?php foreach ($commentTree[$comment->id] as $reply): ?>
                <?= $renderComment($reply) ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
};
?>

<div class="container app-page-spacing">
    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <div class="d-flex justify-content-between flex-wrap gap-3 mb-3">
                    <div>
                        <?php if ($post->pinned): ?>
                            <span class="badge bg-dark text-white">Pinned</span>
                        <?php endif; ?>
                        <?php if ($post->featured): ?>
                            <span class="badge bg-warning text-dark">Featured</span>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted">Posted <?= date('M j, Y g:i A', strtotime($post->created_at)) ?></small>
                </div>
                <h1 class="fw-bold mb-3"><?= htmlspecialchars($post->title) ?></h1>
                <div class="community-post-author-row mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center overflow-hidden" style="width:48px;height:48px;">
                            <?php if (!empty($post->profile_image)): ?>
                                <img src="<?= htmlspecialchars($post->profile_image) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <?= htmlspecialchars(strtoupper(substr($post->first_name ?? 'U', 0, 1))) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold"><?= htmlspecialchars($post->first_name . ' ' . $post->last_name) ?></span>
                                <?php if ($user && (int) ($user['id'] ?? 0) !== (int) ($post->user_id ?? 0)): ?>
                                    <a class="account-message-link" href="/ani-mart/public/messages/start?user_id=<?= (int) $post->user_id ?>" aria-label="Message <?= htmlspecialchars(trim($post->first_name . ' ' . $post->last_name)) ?>" title="Send message">
                                        <i class="bi bi-chat-dots" aria-hidden="true"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($post->verification_badge)): ?>
                                    <span class="badge bg-success text-white">Verified</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><?= htmlspecialchars(ucfirst($post->role)) ?></small>
                        </div>
                    </div>
                    <?php if ($canEdit): ?>
                        <?php $deletePostConfirmation = strtolower((string) ($user['role'] ?? '')) === 'admin'
                            ? 'Permanently delete this post and its Cloudinary images? This cannot be undone.'
                            : 'Remove this post? It will remain recoverable for 30 days.'; ?>
                        <div class="community-post-management-actions">
                            <a href="/ani-mart/public/community/posts/edit?id=<?= (int) $post->id ?>" class="btn btn-sm btn-outline-success rounded-pill">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                            <form method="POST" action="/ani-mart/public/community/posts/delete" onsubmit="return confirm(<?= htmlspecialchars(json_encode($deletePostConfirmation), ENT_QUOTES, 'UTF-8') ?>);">
                                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                <input type="hidden" name="post_id" value="<?= (int) $post->id ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">
                                    <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Delete
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($post->original_post_id): ?>
                    <div class="alert alert-success rounded-4">
                        <small class="text-muted">Shared post from <?= htmlspecialchars($post->original_first_name . ' ' . $post->original_last_name) ?>.</small>
                        <div class="fw-semibold"><?= htmlspecialchars($post->original_title) ?></div>
                    </div>
                <?php endif; ?>
                <p class="text-muted mb-4"><?= nl2br(htmlspecialchars($post->content)) ?></p>
                <?php if (!empty($images)): ?>
                    <div id="communityPostImagesCarousel<?= $post->id ?>" class="carousel slide community-gallery-carousel mb-4" data-bs-ride="carousel" data-bs-interval="2000" data-bs-pause="hover">
                        <div class="carousel-inner">
                            <?php foreach ($images as $index => $image): ?>
                                <div class="carousel-item<?= $index === 0 ? ' active' : '' ?>">
                                    <img src="<?= htmlspecialchars($image->image_url) ?>" class="d-block w-100 rounded-4" alt="Community image" data-image-modal="true">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($images) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#communityPostImagesCarousel<?= $post->id ?>" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#communityPostImagesCarousel<?= $post->id ?>" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <span class="badge bg-success text-white"><i class="bi bi-hand-thumbs-up me-1"></i> <?= (int) ($post->like_count ?? 0) ?> Likes</span>
                    <span class="badge bg-secondary text-white"><i class="bi bi-chat-left-text me-1"></i> <?= (int) ($post->comment_count ?? 0) ?> Comments</span>
                    <span class="badge bg-info text-white"><i class="bi bi-share-fill me-1"></i> <?= (int) $post->share_count ?> Shares</span>
                    <?php if ($user): ?>
                        <button class="btn btn-sm btn-outline-success community-like-btn" data-post-id="<?= (int) $post->id ?>">Like</button>
                        <button class="btn btn-sm btn-outline-secondary community-save-btn" data-post-id="<?= (int) $post->id ?>">Save</button>
                        <button class="btn btn-sm btn-outline-info community-share-btn" data-post-id="<?= (int) $post->id ?>">Share</button>
                        <?php if ($post->allow_comments): ?>
                            <button class="btn btn-sm btn-success community-direct-reply-btn" type="button">Reply to Post</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="/ani-mart/public/login" class="btn btn-sm btn-success">Login to interact</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-3">Discussion</h5>
                <?php if ($user): ?>
                    <?php if ($post->allow_comments): ?>
                        <form method="POST" action="/ani-mart/public/community/comments/store" class="mb-4" id="communityPostReplyForm">
                            <input 
    type="hidden" 
    name="_token" 
    value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input type="hidden" name="post_id" value="<?= (int) $post->id ?>">
                            <?php if ($activeShop): ?>
                                <label class="form-label small fw-semibold" for="commentAs">Reply as</label>
                                <select class="form-select mb-3" id="commentAs" name="comment_as">
                                    <option value="customer">Myself (customer)</option>
                                    <option value="shop"><?= htmlspecialchars($activeShop->shop_name) ?> (shop)</option>
                                </select>
                            <?php endif; ?>
                            <textarea name="comment" class="form-control mb-3" rows="4" minlength="3" maxlength="1000" placeholder="Reply directly to this post..." required></textarea>
                            <button type="submit" class="btn btn-success rounded-pill">Post Reply</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">Comments are closed for this post.</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-secondary">Please <a href="/ani-mart/public/login">login</a> to comment, like, or save posts.</div>
                <?php endif; ?>

                <?php if (empty($commentTree[0])): ?>
                    <p class="text-muted mb-0">No comments yet. Be the first to start the conversation.</p>
                <?php else: ?>
                    <?php foreach ($commentTree[0] as $rootComment): ?>
                        <?= $renderComment($rootComment) ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-3">Post Details</h5>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Visibility</span>
                    <span><?= htmlspecialchars(ucfirst($post->visibility)) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Comments</span>
                    <span><?= (int) ($post->comment_count ?? 0) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Shares</span>
                    <span><?= (int) $post->share_count ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const communityCsrfToken = <?= json_encode(csrf_token()) ?>;
const communityShopName = <?= json_encode($activeShop->shop_name ?? null) ?>;

function escapeCommunityHtml(value) {
    return String(value || '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[character]));
}

function submitCommunityPostAction(url, params, onSuccess) {
    return fetch(url, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: new URLSearchParams({ _token: communityCsrfToken, ...params })
    }).then(res => {
        if (!res.ok) {
            throw new Error('Network response was not OK: ' + res.status);
        }
        return res.json();
    }).then(data => {
        if (!data.success) {
            throw new Error(data.reason || 'Community action failed');
        }
        if (typeof onSuccess === 'function') {
            onSuccess(data);
        }
        return data;
    }).catch(error => {
        console.error('Community action failed', error);
        alert('Unable to perform the action. Check your network or login status.');
    });
}

document.querySelectorAll('.community-like-btn').forEach(button => {
    button.addEventListener('click', () => {
        const postId = button.dataset.postId;
        submitCommunityPostAction('/ani-mart/public/community/likes/toggle', { post_id: postId }, data => {
            button.innerText = data.liked ? 'Unlike' : 'Like';

            const likeBadge = document.querySelector('.badge.bg-success.text-white');
            if (likeBadge && typeof data.count !== 'undefined') {
                likeBadge.innerHTML = '<i class="bi bi-hand-thumbs-up me-1"></i> ' + (data.count || 0) + ' Likes';
            }
        });
    });
});

document.querySelectorAll('.community-comment-like-btn').forEach(button => {
    button.addEventListener('click', () => {
        const commentId = button.dataset.commentId;
        submitCommunityPostAction('/ani-mart/public/community/comments/likes/toggle', { comment_id: commentId }, data => {
            button.innerHTML = '<i class="bi bi-hand-thumbs-up"></i> ' + (data.count || 0);
        });
    });
});

if (document.querySelector('.community-save-btn')) {
    document.querySelector('.community-save-btn').addEventListener('click', event => {
        const button = event.currentTarget;
        const postId = button.dataset.postId;
        submitCommunityPostAction('/ani-mart/public/community/saves/toggle', { post_id: postId }, data => {
            if (typeof data.saved !== 'undefined') {
                button.innerText = data.saved ? 'Saved' : 'Save';
            }
        });
    });
}

if (document.querySelector('.community-share-btn')) {
    document.querySelector('.community-share-btn').addEventListener('click', event => {
        const button = event.currentTarget;
        const postId = button.dataset.postId;
        button.disabled = true;
        submitCommunityPostAction('/ani-mart/public/community/shares/create', { post_id: postId }, data => {
            window.location.href = '/ani-mart/public/community/show?id=' + data.shared_post_id;
        }).finally(() => {
            button.disabled = false;
        });
    });
}

function createReplyForm(postId, parentId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/ani-mart/public/community/comments/store';
    form.className = 'community-reply-form mb-3';

    form.innerHTML = `
        <input type="hidden" name="_token" value="${communityCsrfToken}">
        <input type="hidden" name="post_id" value="${postId}">
        <input type="hidden" name="parent_id" value="${parentId}">
        ${communityShopName ? `
            <select class="form-select form-select-sm mb-2" name="comment_as" aria-label="Reply as">
                <option value="customer">Myself (customer)</option>
                <option value="shop">${escapeCommunityHtml(communityShopName)} (shop)</option>
            </select>` : ''}
        <textarea name="comment" class="form-control mb-2" rows="3" placeholder="Write your reply..." required></textarea>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success btn-sm rounded-pill">Reply</button>
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill reply-cancel-btn">Cancel</button>
        </div>
    `;

    form.querySelector('.reply-cancel-btn').addEventListener('click', () => {
        form.remove();
    });

    return form;
}

document.querySelectorAll('.community-reply-btn').forEach(button => {
    button.addEventListener('click', () => {
        const parentId = button.dataset.parentId;
        const postId = button.closest('.container').querySelector('.community-like-btn').dataset.postId;
        const commentBox = button.closest('.community-comment');

        document.querySelectorAll('.community-reply-form').forEach(form => form.remove());

        const replyForm = createReplyForm(postId, parentId);
        commentBox.appendChild(replyForm);
        replyForm.querySelector('textarea').focus();
    });
});

document.querySelector('.community-direct-reply-btn')?.addEventListener('click', () => {
    const form = document.getElementById('communityPostReplyForm');
    form?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    window.setTimeout(() => form?.querySelector('textarea')?.focus(), 350);
});

function highlightDeepLinkTarget() {
    const hash = window.location.hash;
    if (!hash) return;

    const target = document.querySelector(hash);
    if (!target) return;

    target.classList.add('highlighted-target');
    target.scrollIntoView({ behavior: 'smooth', block: 'center' });

    window.setTimeout(() => {
        target.classList.remove('highlighted-target');
    }, 4000);
}

window.addEventListener('load', highlightDeepLinkTarget);
</script>



<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
