<?php

$title = "Edit Profile";
$bodyClass = "profile-page";

$role =
    $profileUser['role'] ?? 'customer';

$roleLabel =
    ucfirst($role);

$initial =
    strtoupper(
        substr(
            $profileUser['first_name'] ?? 'U',
            0,
            1
        )
    );

$profileImage =
    $profileUser['profile_image'] ?? null;

ob_start();

?>

<div class="container app-page-spacing">

    <?php if ($role === 'admin'): ?>
        <section class="admin-page-hero" aria-labelledby="admin-profile-title">
            <div class="admin-page-hero-copy">
                <span class="hero-badge">Administrator Account</span>
                <h1 class="hero-title" id="admin-profile-title">Profile &amp; Security</h1>
                <p class="hero-subtitle">Manage your administrator identity, contact details, and account protection settings.</p>
            </div>
        </section>
    <?php endif; ?>

    <div class="profile-shell">

        <div class="profile-panel profile-summary">

            <div class="profile-large-avatar">

                <?php if (!empty($profileImage)): ?>

                    <img
                        id="profile-summary-preview"
                        src="<?= htmlspecialchars($profileImage) ?>"
                        alt="Profile photo">

                <?php else: ?>

                    <?= htmlspecialchars($initial) ?>

                <?php endif; ?>

                <?php if (!empty($profileImage)): ?>
                    <button
                        type="submit"
                        form="profile-update-form"
                        name="remove_profile_image"
                        value="1"
                        class="profile-remove-image"
                        aria-label="Remove profile photo"
                        title="Remove profile photo">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                <?php endif; ?>

            </div>

            <div>

                <p class="profile-eyebrow mb-2">

                    <?= htmlspecialchars($roleLabel) ?> Account

                </p>

                <h1 class="fw-bold mb-2">

                    <?= htmlspecialchars($profileUser['first_name'] ?? '') ?>
                    <?= htmlspecialchars($profileUser['last_name'] ?? '') ?>

                </h1>

                <p class="text-muted mb-0">

                    <?= htmlspecialchars($profileUser['email'] ?? '') ?>

                </p>

            </div>

        </div>

        <div class="profile-panel">

            <form
                id="profile-update-form"
                method="POST"
                enctype="multipart/form-data"
                action="/ani-mart/public/profile">

                <input
                    type="hidden"
                    name="_token"
                    value="<?= csrf_token(); ?>">

                <div class="d-flex align-items-center justify-content-between gap-3 mb-4">

                    <div>

                        <h2 class="profile-section-title mb-1">

                            <span class="profile-section-icon">

                                <i class="bi bi-person-lines-fill"></i>

                            </span>

                            Profile Details

                        </h2>

                        <p class="text-muted mb-0">

                            Update your account information.

                        </p>

                    </div>

                    <a
                        href="/ani-mart/public/dashboard"
                        class="btn profile-back-btn">

                        <i class="bi bi-arrow-left me-2"></i>
                        Dashboard

                    </a>

                </div>

                <div class="row g-4">

                    <div class="col-12">

                        <label class="form-label fw-semibold">

                            Profile Image

                        </label>

                        <label class="profile-upload">

                            <span class="profile-upload-preview">

                                <?php if (!empty($profileImage)): ?>

                                    <img
                                        id="profile-upload-preview-image"
                                        src="<?= htmlspecialchars($profileImage) ?>"
                                        alt="Profile photo preview">

                                <?php else: ?>

                                    <?= htmlspecialchars($initial) ?>

                                <?php endif; ?>

                            </span>

                            <span class="profile-upload-copy">

                                <strong>Upload a profile photo</strong>

                                <small>

                                    JPG, PNG, or WEBP

                                </small>

                            </span>

                            <span class="profile-upload-action">

                                <i class="bi bi-camera-fill"></i>
                                Choose Image

                            </span>

                            <input
                                id="profile-image-input"
                                type="file"
                                name="profile_image"
                                accept="image/jpeg,image/png,image/webp">

                        </label>

                    </div>

                    <div class="col-md-6">

                        <label class="form-label fw-semibold">

                            First Name

                        </label>

                        <input
                            type="text"
                            name="first_name"
                            value="<?= htmlspecialchars($profileUser['first_name'] ?? '') ?>"
                            class="form-control modern-input"
                            required>

                    </div>

                    <div class="col-md-6">

                        <label class="form-label fw-semibold">

                            Last Name

                        </label>

                        <input
                            type="text"
                            name="last_name"
                            value="<?= htmlspecialchars($profileUser['last_name'] ?? '') ?>"
                            class="form-control modern-input"
                            required>

                    </div>

                    <div class="col-md-8">

                        <label class="form-label fw-semibold">

                            Email Address

                        </label>

                        <input
                            type="email"
                            name="email"
                            value="<?= htmlspecialchars($profileUser['email'] ?? '') ?>"
                            class="form-control modern-input"
                            required>

                    </div>

                    <div class="col-md-4">

                        <label class="form-label fw-semibold">

                            Role

                        </label>

                        <input
                            type="text"
                            value="<?= htmlspecialchars($roleLabel) ?>"
                            class="form-control modern-input"
                            disabled>

                    </div>

                </div>

                <?php if ($role === 'farmer'): ?>

                    <hr class="my-5">

                    <h3 class="profile-section-title mb-3">

                        <span class="profile-section-icon">

                            <i class="bi bi-shop-window"></i>

                        </span>

                        Farmer Shop

                    </h3>

                    <div class="row g-4">

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">

                                Shop Name

                            </label>

                            <input
                                type="text"
                                name="shop_name"
                                value="<?= htmlspecialchars($sellerProfile->shop_name ?? '') ?>"
                                class="form-control modern-input">

                        </div>

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">

                                Shop Status

                            </label>

                            <input
                                type="text"
                                value="<?= htmlspecialchars(ucfirst($sellerProfile->status ?? 'approved')) ?>"
                                class="form-control modern-input"
                                disabled>

                        </div>

                        <div class="col-12">

                            <label class="form-label fw-semibold">

                                Shop Description

                            </label>

                            <textarea
                                name="description"
                                rows="4"
                                class="form-control modern-input"><?= htmlspecialchars($sellerProfile->description ?? '') ?></textarea>

                        </div>

                    </div>

                <?php endif; ?>

                <hr class="my-5">

                <h3 class="profile-section-title mb-3">

                    <span class="profile-section-icon">

                        <i class="bi bi-shield-lock"></i>

                    </span>

                    Change Password

                </h3>

                <div class="row g-4">

                    <div class="col-md-6">

                        <label class="form-label fw-semibold">

                            New Password

                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control modern-input"
                            autocomplete="new-password">

                    </div>

                    <div class="col-md-6">

                        <label class="form-label fw-semibold">

                            Confirm Password

                        </label>

                        <input
                            type="password"
                            name="password_confirmation"
                            class="form-control modern-input"
                            autocomplete="new-password">

                    </div>

                </div>

                <div class="d-flex justify-content-end mt-5">

                    <button
                        type="submit"
                        class="btn profile-save-btn">

                        <i class="bi bi-check-circle me-2"></i>
                        Save Changes

                    </button>

                </div>

            </form>

            <?php if ($role === 'admin'): ?>

                <hr class="my-5">

                <form
                    method="POST"
                    action="/ani-mart/public/profile/admin-login-protection">

                    <input type="hidden" name="_token" value="<?= csrf_token(); ?>">

                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">

                        <div>

                            <h3 class="profile-section-title mb-2">
                                <span class="profile-section-icon">
                                    <i class="bi bi-envelope-check"></i>
                                </span>
                                Administrator Login Protection
                            </h3>

                            <p class="text-muted mb-0">
                                Require a one-time code sent to your email after your password is accepted.
                            </p>

                        </div>

                        <div class="d-flex align-items-center gap-3">

                            <div class="form-check form-switch mb-0">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    role="switch"
                                    id="admin-login-otp"
                                    name="admin_login_otp_enabled"
                                    value="1"
                                    <?= !empty($profileUser['admin_login_otp_enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="admin-login-otp">
                                    Email code
                                </label>
                            </div>

                            <button type="submit" class="btn profile-save-btn">
                                Save Protection
                            </button>

                        </div>

                    </div>

                </form>

            <?php endif; ?>

            <?php if ($role !== 'admin'): ?>

                <hr class="my-5">

                <section class="account-danger-zone" aria-labelledby="delete-account-title">
                    <div>
                        <h3 class="profile-section-title mb-2" id="delete-account-title">
                            <span class="profile-section-icon account-danger-icon"><i class="bi bi-trash3"></i></span>
                            Delete Account
                        </h3>
                        <p class="text-muted mb-3">
                            Your account will be scheduled for deletion in 30 days. A successful login during that period automatically cancels the request.
                        </p>
                        <p class="small mb-0">
                            After 30 days, your personal profile will be removed and your account disabled. Order records are retained for marketplace records.
                        </p>
                    </div>

                    <form method="POST" action="/ani-mart/public/profile/delete-account" class="account-delete-form">
                        <input type="hidden" name="_token" value="<?= csrf_token(); ?>">
                        <label class="form-label fw-semibold" for="delete-account-password">Current password</label>
                        <input
                            class="form-control modern-input"
                            id="delete-account-password"
                            type="password"
                            name="current_password"
                            autocomplete="current-password"
                            required>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" value="1" id="confirm-account-deletion" name="confirm_account_deletion" required>
                            <label class="form-check-label" for="confirm-account-deletion">
                                I understand that deletion will be completed after 30 days unless I sign in again.
                            </label>
                        </div>
                        <button type="submit" class="btn btn-danger mt-3" data-confirm="Schedule this account for deletion in 30 days?" data-confirm-title="Delete account" data-confirm-text="Continue" data-confirm-class="btn-danger">
                            <i class="bi bi-trash3 me-2"></i>Schedule Account Deletion
                        </button>
                    </form>
                </section>

            <?php endif; ?>

        </div>

    </div>

</div>

<script>
    const profileImageInput =
        document.getElementById('profile-image-input');

    if (profileImageInput) {
        profileImageInput.addEventListener('change', function(event) {
            const file =
                event.target.files[0];

            if (!file || !file.type.startsWith('image/')) {
                return;
            }

            const reader =
                new FileReader();

            reader.onload = function(loadEvent) {
                const imageUrl =
                    loadEvent.target.result;

                const uploadPreview =
                    document.querySelector('.profile-upload-preview');

                const summaryPreview =
                    document.querySelector('.profile-large-avatar');

                if (uploadPreview) {
                    uploadPreview.innerHTML =
                        '<img id="profile-upload-preview-image" src="' +
                        imageUrl +
                        '" alt="Profile photo preview">';
                }

                if (summaryPreview) {
                    summaryPreview.innerHTML =
                        '<img id="profile-summary-preview" src="' +
                        imageUrl +
                        '" alt="Profile photo">';
                }
            };

            reader.readAsDataURL(file);
        });
    }
</script>

<?php

$content = ob_get_clean();

require ANI_MART_ROOT . '/app/Views/layouts/main.php';

?>
