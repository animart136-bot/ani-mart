<?php
$activeShop = $activeShop ?? null;
?>
<script>
(() => {
    const csrfToken = <?= json_encode(csrf_token()) ?>;
    const shopName = <?= json_encode($activeShop->shop_name ?? '') ?>;

    const postAction = async (url, values) => {
        const formData = new FormData();
        formData.append('_token', csrfToken);
        Object.entries(values).forEach(([key, value]) => formData.append(key, value));

        const response = await fetch(url, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Accept': 'application/json' },
            body: formData
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.reason || 'Community action failed');
        }
        return data;
    };

    document.querySelectorAll('.community-like-btn').forEach(button => {
        button.addEventListener('click', async event => {
            event.preventDefault();
            button.disabled = true;
            try {
                const data = await postAction('/ani-mart/public/community/likes/toggle', { post_id: button.dataset.postId });
                button.textContent = data.liked ? 'Unlike' : 'Like';
                button.classList.toggle('btn-success', data.liked);
                button.classList.toggle('btn-outline-success', !data.liked);
                const count = document.querySelector(`.community-post-like-count[data-post-id="${button.dataset.postId}"] span`);
                if (count) count.textContent = data.count;
            } catch (error) {
                console.error('Community like failed', error);
                alert('Unable to like this post. Please refresh and try again.');
            } finally {
                button.disabled = false;
            }
        });
    });

    document.querySelectorAll('.community-save-btn').forEach(button => {
        button.addEventListener('click', async event => {
            event.preventDefault();
            const originalText = button.textContent;
            button.disabled = true;
            try {
                const data = await postAction('/ani-mart/public/community/saves/toggle', { post_id: button.dataset.postId });
                button.textContent = data.saved ? 'Saved' : 'Save';
                button.classList.toggle('btn-secondary', data.saved);
                button.classList.toggle('btn-outline-secondary', !data.saved);
            } catch (error) {
                console.error('Community save failed', error);
                button.textContent = originalText.trim();
                alert('Unable to save this post. Please refresh and try again.');
            } finally {
                button.disabled = false;
            }
        });
    });

    document.querySelectorAll('.community-share-btn').forEach(button => {
        button.addEventListener('click', async event => {
            event.preventDefault();
            button.disabled = true;
            try {
                const data = await postAction('/ani-mart/public/community/shares/create', { post_id: button.dataset.postId });
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
            try {
                const data = await postAction('/ani-mart/public/community/comments/likes/toggle', { comment_id: button.dataset.commentId });
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
                <input type="hidden" name="_token" value="${csrfToken}">
                <input type="hidden" name="post_id" value="${comment.dataset.postId}">
                <input type="hidden" name="parent_id" value="${button.dataset.commentId}">
                ${shopName ? '<select class="form-select form-select-sm mb-2" name="comment_as"><option value="customer">Reply as myself</option><option value="shop">Reply as shop</option></select>' : ''}
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
})();
</script>
