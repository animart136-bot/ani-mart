<?php

use App\Core\Session;

$post = $post ?? null;
$user = Session::get('user');
$activeShop = $activeShop ?? null;

if (!$post) {
    return;
}

$canDeletePost = $user && (
    (int) ($user['id'] ?? 0) === (int) ($post->user_id ?? 0)
    || strtolower((string) ($user['role'] ?? '')) === 'admin'
);
$deletePostConfirmation = strtolower((string) ($user['role'] ?? '')) === 'admin'
    ? 'Permanently delete this post and its Cloudinary images? This cannot be undone.'
    : 'Remove this post? It will remain recoverable for 30 days.';
?>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
                <?php if ($post->pinned): ?>
                    <span class="badge bg-dark text-white">Pinned</span>
                <?php endif; ?>
                <?php if ($post->featured): ?>
                    <span class="badge bg-warning text-dark">Featured</span>
                <?php endif; ?>
            </div>
            <small class="text-muted"><?= date('M j, Y', strtotime($post->created_at)) ?></small>
        </div>

        <a href="/ani-mart/public/community/show?id=<?= (int) $post->id ?>" class="text-decoration-none">
            <h4 class="fw-bold mt-3 mb-2"><?= htmlspecialchars($post->title) ?></h4>
        </a>

        <div class="d-flex align-items-center gap-2 mb-3 text-muted small">
            <div class="community-author-avatar rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center overflow-hidden" style="width:34px;height:34px;">
                <?php if (!empty($post->profile_image)): ?>
                    <img src="<?= htmlspecialchars($post->profile_image) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                    <?= htmlspecialchars(strtoupper(substr($post->first_name ?? 'U', 0, 1))) ?>
                <?php endif; ?>
            </div>
            <span><?= htmlspecialchars($post->first_name . ' ' . $post->last_name) ?></span>
            <?php if ($user && (int) ($user['id'] ?? 0) !== (int) ($post->user_id ?? 0)): ?>
                <a class="account-message-link" href="/ani-mart/public/messages/start?user_id=<?= (int) $post->user_id ?>" aria-label="Message <?= htmlspecialchars(trim($post->first_name . ' ' . $post->last_name)) ?>" title="Send message">
                    <i class="bi bi-chat-dots" aria-hidden="true"></i>
                </a>
            <?php endif; ?>
            <?php $authorRole = trim((string) ($post->role ?? '')); ?>
            <?php if ($authorRole !== ''): ?>
                <span>&middot; <?= htmlspecialchars(ucfirst($authorRole)) ?></span>
            <?php endif; ?>
            <?php if (!empty($post->verification_badge)): ?>
                <span class="badge bg-success text-white">Verified</span>
            <?php endif; ?>
        </div>

        <?php if (!empty($post->original_post_id)): ?>
            <a href="/ani-mart/public/community/show?id=<?= (int) $post->original_post_id ?>" class="community-shared-source d-block text-decoration-none rounded-3 p-3 mb-3">
                <small class="text-muted d-block mb-1">
                    <i class="bi bi-share-fill me-1"></i>
                    Shared from <?= htmlspecialchars(trim(($post->original_first_name ?? '') . ' ' . ($post->original_last_name ?? '')) ?: 'a community member') ?>
                </small>
                <strong class="text-body"><?= htmlspecialchars($post->original_title ?? 'Original community post') ?></strong>
            </a>
        <?php endif; ?>

        <?php
            $postContent = (string) $post->content;
            $contentLimit = 240;
            $contentPreview = mb_strlen($postContent) > $contentLimit
                ? mb_substr($postContent, 0, $contentLimit) . '...'
                : $postContent;
        ?>
        <p class="text-muted mb-2"><?= nl2br(htmlspecialchars($contentPreview)) ?></p>
        <?php if (mb_strlen($postContent) > $contentLimit): ?>
            <a href="/ani-mart/public/community/show?id=<?= (int) $post->id ?>" class="btn btn-sm btn-outline-success rounded-pill mb-3">More</a>
        <?php endif; ?>

        <?php $postImages = $post->images ?? (!empty($post->image_url) ? [(object) ['image_url' => $post->image_url]] : []); ?>
        <?php if (!empty($postImages)): ?>
            <div id="communityFeedCarousel<?= (int) $post->id ?>" class="carousel slide community-gallery-carousel mb-3" data-bs-ride="carousel" data-bs-interval="3500" data-bs-pause="hover">
                <div class="carousel-inner rounded-4">
                    <?php foreach ($postImages as $index => $image): ?>
                        <div class="carousel-item<?= $index === 0 ? ' active' : '' ?>">
                            <img src="<?= htmlspecialchars($image->image_url) ?>" class="d-block w-100 rounded-4" alt="Community image <?= $index + 1 ?>" data-image-modal="true">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($postImages) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#communityFeedCarousel<?= (int) $post->id ?>" data-bs-slide="prev" aria-label="Previous image">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#communityFeedCarousel<?= (int) $post->id ?>" data-bs-slide="next" aria-label="Next image">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
            <span class="community-post-like-count" data-post-id="<?= (int) $post->id ?>">
                <i class="bi bi-hand-thumbs-up"></i> <span><?= (int) $post->like_count ?></span>
            </span>
            <span><i class="bi bi-chat-left-text"></i> <?= (int) $post->comment_count ?></span>
            <span><i class="bi bi-share-fill"></i> <?= (int) $post->share_count ?></span>
            <?php if ($user): ?>
                <button class="btn btn-sm <?= !empty($post->is_liked) ? 'btn-success' : 'btn-outline-success' ?> p-2 community-like-btn" data-post-id="<?= (int) $post->id ?>">
                    <?= !empty($post->is_liked) ? 'Unlike' : 'Like' ?>
                </button>
                <button class="btn btn-sm <?= !empty($post->is_saved) ? 'btn-secondary' : 'btn-outline-secondary' ?> p-2 community-save-btn" data-post-id="<?= (int) $post->id ?>">
                    <?= !empty($post->is_saved) ? 'Saved' : 'Save' ?>
                </button>
                <button class="btn btn-sm btn-outline-info p-2 community-share-btn" data-post-id="<?= (int) $post->id ?>">
                    Share
                </button>
            <?php endif; ?>
            <?php if ($canDeletePost): ?>
                <div class="d-flex align-items-center gap-2 ms-md-auto community-post-owner-actions">
                    <a href="/ani-mart/public/community/posts/edit?id=<?= (int) $post->id ?>" class="btn btn-sm btn-outline-success rounded-pill">
                        <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit
                    </a>
                    <form method="POST" action="/ani-mart/public/community/posts/delete" onsubmit="return confirm(<?= htmlspecialchars(json_encode($deletePostConfirmation), ENT_QUOTES, 'UTF-8') ?>);">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input type="hidden" name="post_id" value="<?= (int) $post->id ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" aria-label="Delete community post">
                            <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Delete
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($post->comments)): ?>
            <div class="community-post-comments mt-3 pt-3 border-top">
                <?php foreach ($post->comments as $comment): ?>
                    <?php
                        $feedCommentAsShop = ($comment->author_type ?? 'customer') === 'shop' && !empty($comment->shop_name);
                        $feedCommentAuthor = $feedCommentAsShop
                            ? $comment->shop_name
                            : trim(($comment->first_name ?? '') . ' ' . ($comment->last_name ?? ''));
                    ?>
                    <div class="community-feed-comment small mb-3" id="comment-<?= (int) $comment->id ?>" data-post-id="<?= (int) $post->id ?>">
                        <div>
                            <strong><?= htmlspecialchars($feedCommentAuthor) ?></strong>
                            <?php if ($user && (int) ($user['id'] ?? 0) !== (int) ($comment->user_id ?? 0)): ?>
                                <a class="account-message-link" href="/ani-mart/public/messages/start?user_id=<?= (int) $comment->user_id ?>" aria-label="Message <?= htmlspecialchars($feedCommentAuthor) ?>" title="Send message">
                                    <i class="bi bi-chat-dots" aria-hidden="true"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ($feedCommentAsShop): ?><span class="badge bg-success-subtle text-success">Shop</span><?php endif; ?>
                            <span class="text-muted">· <?= nl2br(htmlspecialchars($comment->comment)) ?></span>
                        </div>
                        <div class="community-feed-comment-actions mt-1">
                            <?php if ($user): ?>
                                <button type="button" class="community-feed-comment-like <?= !empty($comment->is_liked) ? 'is-liked' : '' ?>" data-comment-id="<?= (int) $comment->id ?>">
                                    <i class="bi <?= !empty($comment->is_liked) ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' ?>"></i>
                                    <span><?= !empty($comment->is_liked) ? 'Liked' : 'Like' ?></span>
                                </button>
                            <?php else: ?>
                                <span><i class="bi bi-hand-thumbs-up"></i> Like</span>
                            <?php endif; ?>
                            <span class="community-feed-comment-like-count"><?= (int) ($comment->like_count ?? 0) ?></span>
                            <?php if ($user): ?>
                                <button type="button" class="community-feed-comment-reply" data-comment-id="<?= (int) $comment->id ?>">Reply</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($user && $post->allow_comments): ?>
            <form method="POST" action="/ani-mart/public/community/comments/store" class="mt-3 community-feed-reply-form">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <input type="hidden" name="post_id" value="<?= (int) $post->id ?>">
                <?php if ($activeShop): ?>
                    <select class="form-select form-select-sm mb-2" name="comment_as" aria-label="Comment as">
                        <option value="customer">Comment as myself</option>
                        <option value="shop">Comment as <?= htmlspecialchars($activeShop->shop_name) ?></option>
                    </select>
                <?php endif; ?>
                <div class="d-flex gap-2">
                    <input type="text" name="comment" class="form-control rounded-pill" minlength="3" maxlength="1000" placeholder="Write a comment..." required>
                    <button type="submit" class="btn btn-success rounded-pill px-3" title="Post comment" aria-label="Post comment"><i class="bi bi-send"></i></button>
                </div>
            </form>
        <?php elseif (!$user): ?>
            <a href="/ani-mart/public/login" class="d-inline-block mt-3 small text-success">Log in to comment</a>
        <?php endif; ?>
    </div>
</div>
