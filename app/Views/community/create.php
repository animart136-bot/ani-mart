<?php

use App\Core\Session;

$title = 'Create Community Post';
$bodyClass = 'community-create-page';
$user = Session::get('user');

ob_start();
?>

<div class="container app-page-spacing">
    <div class="card border-0 shadow-sm rounded-4 p-4">
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
            <div>
                <span class="text-success fw-bold small text-uppercase">Create Post</span>
                <h1 class="fw-bold mb-1">Share a community update</h1>
                <p class="text-muted mb-0">Publish announcements, tips, events, or harvest updates to Ani-Mart AgriConnect.</p>
            </div>
            <a href="/ani-mart/public/community" class="btn btn-outline-secondary rounded-pill">Back to Feed</a>
        </div>

        <form method="POST" action="/ani-mart/public/community/posts/store" enctype="multipart/form-data">
            <input 
    type="hidden" 
    name="_token" 
    value="<?= htmlspecialchars(csrf_token()) ?>">
        <div class="row g-4">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <label class="form-label" for="communityContent">Content</label>
                        <textarea id="communityContent" name="content" class="form-control" rows="8" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="communityImages">Images</label>
                        <input type="file" id="communityImages" name="images[]" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
                        <small class="form-text text-muted">Upload multiple images, up to 4MB each.</small>
                    </div>
                    <div id="communityImagePreview" class="community-image-preview d-none mb-4" aria-live="polite">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Image preview</strong>
                            <span id="communityImageCount" class="text-muted small"></span>
                        </div>
                        <div class="community-preview-frame">
                            <button type="button" class="community-preview-control community-preview-prev" aria-label="Previous image">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <div id="communityPreviewTrack" class="community-preview-track"></div>
                            <button type="button" class="community-preview-control community-preview-next" aria-label="Next image">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                        <div id="communityPreviewDots" class="community-preview-dots"></div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="allowComments" name="allow_comments" checked>
                        <label class="form-check-label" for="allowComments">Allow comments</label>
                    </div>
                    <?php if ($user && strtolower($user['role']) === 'admin'): ?>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="featured" name="featured">
                            <label class="form-check-label" for="featured">Featured post</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="pinned" name="pinned">
                            <label class="form-check-label" for="pinned">Pin post</label>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-lg-4">
                    <div class="card border-0 bg-light rounded-4 p-4 h-100">
                        <h5 class="fw-bold mb-3">Post Settings</h5>
                        <div class="mb-3">
                            <label class="form-label">Visibility</label>
                            <select class="form-select" name="visibility" disabled>
                                <option value="public">Public</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success rounded-pill w-100 mt-auto">Publish Post</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const input = document.getElementById('communityImages');
    const preview = document.getElementById('communityImagePreview');
    const track = document.getElementById('communityPreviewTrack');
    const dots = document.getElementById('communityPreviewDots');
    const count = document.getElementById('communityImageCount');
    let activeIndex = 0;

    function showSlide(index) {
        const slides = track.querySelectorAll('.community-preview-slide');
        if (!slides.length) return;
        activeIndex = (index + slides.length) % slides.length;
        track.style.transform = `translateX(-${activeIndex * 100}%)`;
        dots.querySelectorAll('button').forEach((dot, dotIndex) => dot.classList.toggle('active', dotIndex === activeIndex));
    }

    input.addEventListener('change', () => {
        track.innerHTML = '';
        dots.innerHTML = '';
        const files = Array.from(input.files || []);
        count.textContent = `${files.length} image${files.length === 1 ? '' : 's'}`;
        preview.classList.toggle('d-none', files.length === 0);

        files.forEach(addPreview);

        activeIndex = 0;
        showSlide(0);
    });

    function addPreview(file, index) {
        const reader = new FileReader();
        reader.onload = event => {
            const slide = document.createElement('div');
            slide.className = 'community-preview-slide';
            slide.innerHTML = `<img src="${event.target.result}" alt="Preview ${index + 1}">`;
            track.appendChild(slide);

            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'community-preview-dot';
            dot.setAttribute('aria-label', `Show image ${index + 1}`);
            dot.addEventListener('click', () => showSlide(index));
            dots.appendChild(dot);
            showSlide(activeIndex);
        };
        reader.readAsDataURL(file);
    }

    document.querySelector('.community-preview-prev').addEventListener('click', () => showSlide(activeIndex - 1));
    document.querySelector('.community-preview-next').addEventListener('click', () => showSlide(activeIndex + 1));
})();
</script>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
