<?php

$title = 'Create New Shop';

$pageStyles[] = '/ani-mart/public/assets/css/pages/shop-create.css?v=' . filemtime(__DIR__ . '/../../../../public/assets/css/pages/shop-create.css');

ob_start();
?>

<div class="container app-page-spacing shop-create-page">
    <section class="hero shop-create-hero mb-5" aria-labelledby="createShopTitle">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="hero-badge">
                    Seller Centre
                </span>
                <h1 id="createShopTitle" class="hero-title mt-3 mb-2">Create New Shop</h1>
                <p class="hero-subtitle mb-0">
                    Build a seller profile, introduce your farm to local customers, and prepare your products for the marketplace.
                </p>
            </div>

            <div class="col-lg-4 text-lg-end">
                <a href="/ani-mart/public/seller-center/shops" class="btn btn-light rounded-pill px-4 shop-create-hero-action">
                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                    My Shops
                </a>
            </div>
        </div>
    </section>

    <div class="row justify-content-center align-items-start g-4 shop-create-grid">
        <div class="col-lg-7">
            <div class="application-card">
                <div class="mb-4">
                    <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">Seller Centre</span>
                </div>

                <h2 class="fw-bold h3 mb-3">Shop details</h2>

                <p class="text-muted mb-4 shop-create-form-intro">
                    Create your seller profile and start selling directly to local customers.
                </p>

                <form id="create-shop-form" method="POST" action="/ani-mart/public/seller-center/shops/store" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">

                    <div class="mb-4 shop-create-section-heading">
                        <h3 class="h5 fw-bold mb-1">Seller Profile</h3>
                        <p class="text-muted small mb-3">This information will appear on your public seller profile after approval.</p>
                    </div>

                    <div class="mb-4">
                        <label for="logo" class="form-label fw-semibold">Seller Profile Image</label>
                        <input type="file" name="logo" id="logo" class="form-control modern-input" accept="image/jpeg,image/png,image/webp" required>
                        <small class="text-muted">Upload a clear image that represents your shop. It will be shown on your public seller profile after approval.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Shop Name</label>
                        <input type="text" name="shop_name" class="form-control modern-input" placeholder="Ex. Green Valley Produce" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Seller Description</label>
                        <textarea name="description" rows="5" class="form-control modern-input" placeholder="Tell customers about your shop and products..." required></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Shop Banner <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="file" name="banner" class="form-control modern-input" accept="image/jpeg,image/png,image/webp">
                    </div>

                    <div class="mb-4 shop-create-section-heading shop-create-contact-heading">
                        <h3 class="h5 fw-bold mb-1">Contact</h3>
                        <p class="text-muted small mb-3">Provide your shop address and contact number.</p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Address</label>
                                <input type="text" name="address" class="form-control modern-input" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control modern-input" required>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg rounded-pill px-5 shop-create-submit">
                        <i class="bi bi-shop-window me-2" aria-hidden="true"></i>Create Shop
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="preview-card">
                <div class="seller-profile-image-preview" id="seller-profile-image-preview" aria-live="polite">
                    <img id="preview-image" class="img-fluid rounded-5 shadow-sm preview-banner" alt="Seller profile preview">
                    <div class="seller-profile-image-placeholder" id="seller-profile-image-placeholder">
                        <i class="bi bi-image"></i>
                        <span>Your seller profile image</span>
                    </div>
                </div>

                <div class="preview-content">
                    <span class="badge bg-success text-white mb-3">Seller Profile Preview</span>
                    <h3 class="fw-bold"><span id="profile-name-preview">Your Shop Name</span></h3>
                    <p class="text-muted"><span id="profile-description-preview">Your seller description will appear here. Once approved, customers can discover your shop and products in the marketplace.</span></p>
                    <div id="preview-info" class="text-secondary small mb-3">Complete your seller profile to preview how it will appear to customers.</div>
                    <div class="row g-2 mt-4">
                        <div class="col-4"><div class="mini-stat"><h5 class="fw-bold">24/7</h5><small class="text-muted">Marketplace</small></div></div>
                        <div class="col-4"><div class="mini-stat"><h5 class="fw-bold">Secure</h5><small class="text-muted">Verification</small></div></div>
                        <div class="col-4"><div class="mini-stat"><h5 class="fw-bold">Fast</h5><small class="text-muted">Approval</small></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
(function () {
    function initializeSellerProfilePreview() {
        const previewInfo = document.getElementById('preview-info');
        const shopNameInput = document.querySelector('[name="shop_name"]');
        const sellerDescriptionInput = document.querySelector('[name="description"]');
        const profileNamePreview = document.getElementById('profile-name-preview');
        const profileDescriptionPreview = document.getElementById('profile-description-preview');
        const sellerProfileImageInput = document.getElementById('logo');
        const profileImagePreview = document.getElementById('preview-image');
        const profileImagePreviewContainer = document.getElementById('seller-profile-image-preview');

        if (!previewInfo || !shopNameInput || !sellerDescriptionInput || !profileNamePreview || !profileDescriptionPreview) {
            return;
        }

        function updateSellerProfilePreview() {
            const shopName = shopNameInput.value.trim();
            const description = sellerDescriptionInput.value.trim();

            profileNamePreview.textContent = shopName || 'Your Shop Name';
            profileDescriptionPreview.textContent = description || 'Your seller description will appear here. Once approved, customers can discover your shop and products in the marketplace.';
            previewInfo.textContent = shopName || description ? 'This is a preview of your seller profile.' : 'Complete your seller profile to preview how it will appear to customers.';
        }

        shopNameInput.addEventListener('input', updateSellerProfilePreview);
        sellerDescriptionInput.addEventListener('input', updateSellerProfilePreview);
        updateSellerProfilePreview();

        if (sellerProfileImageInput && profileImagePreview && profileImagePreviewContainer) {
            sellerProfileImageInput.addEventListener('change', function () {
                const [image] = this.files;
                if (!image || !image.type.startsWith('image/')) {
                    this.value = '';
                    profileImagePreviewContainer.classList.remove('has-image');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (event) {
                    profileImagePreview.src = event.target.result;
                    profileImagePreviewContainer.classList.add('has-image');
                };
                reader.readAsDataURL(image);
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeSellerProfilePreview, { once: true });
    } else {
        initializeSellerProfilePreview();
    }
})();
</script>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
