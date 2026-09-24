<?php

$title = "Apply as a Seller";

$applicationError = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

$pageStyles[] = '/ani-mart/public/assets/css/pages/farmer-application.css?v=' . filemtime(__DIR__ . '/../../../public/assets/css/pages/farmer-application.css');

ob_start();

?>

<div class="container app-page-spacing">

    <div class="row justify-content-center align-items-start g-5">

        <!-- LEFT SIDE -->

        <?php if (!empty($application) && empty($isEditing)): ?>

            <div class="col-lg-7">

                <div class="application-card">

                    <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2 rounded-pill mb-4">
                        <i class="bi bi-pencil-square me-2"></i>Application Needs an Update
                    </span>

                    <h1 class="fw-bold display-6 mb-3">Your Seller Application</h1>

                    <p class="text-muted mb-4">
                        Please update the requested seller information to activate your shop.
                        <?php if (($application->rsbsa_verification_status ?? '') !== 'not_submitted'): ?>
                            Your optional RSBSA information will also be retained as evidence for a Local Farmer badge.
                        <?php endif; ?>
                    </p>

                    <?php $reviewStatus = (string) ($application->ai_review_status ?? 'pending'); ?>
                    <div class="alert <?= $reviewStatus === 'reference_match' ? 'alert-success' : ($reviewStatus === 'information_mismatch' ? 'alert-warning' : 'alert-info') ?>">
                        <strong><?= ($application->rsbsa_verification_status ?? '') === 'not_submitted' ? 'Seller Review' : 'Local Farmer Documentation' ?></strong><br>
                        <?= htmlspecialchars($application->ai_review_summary ?? 'Your submitted documents are awaiting preliminary automated review.') ?><br>
                        <span><?= ($application->rsbsa_verification_status ?? '') === 'not_submitted' ? 'RSBSA: Not submitted' : 'RSBSA: Submitted as optional evidence' ?></span>
                    </div>

                    <div class="border rounded-4 p-4 mb-4 bg-light">
                        <div class="d-flex align-items-center gap-3">
                            <?php if (!empty($application->seller_profile_image_path)): ?>
                                <img src="<?= htmlspecialchars($application->seller_profile_image_path) ?>" alt="<?= htmlspecialchars($application->farm_name) ?> profile image" class="application-profile-thumb">
                            <?php endif; ?>
                            <div>
                                <span class="text-muted small d-block">Shop name</span>
                                <strong><?= htmlspecialchars($application->farm_name) ?></strong>
                            </div>
                        </div>
                        <hr>
                        <span class="text-muted small d-block mb-1">Shop description</span>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($application->farm_description)) ?></p>
                    </div>

                    <a href="/ani-mart/public/farmer/apply?edit=1" class="btn btn-success btn-lg rounded-pill px-5">
                        <i class="bi bi-pencil-square me-2"></i>Edit Application
                    </a>

                </div>

            </div>

        <?php else: ?>

        <div class="col-lg-7">

            <div class="application-card">

                <?php if ($applicationError): ?>
                    <div class="alert alert-danger mb-4" role="alert">
                        <?= htmlspecialchars($applicationError) ?>
                    </div>
                <?php endif; ?>

                <div class="mb-4">

                    <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">

                        Seller Application

                    </span>

                </div>

                <h1 class="fw-bold display-6 mb-3">

                    Apply as an Ani-Mart Seller

                </h1>

                <p class="text-muted mb-5">
                    <?= !empty($isEditing)
                        ? 'Update your seller profile and submit the required images again for review.'
                        : 'Anyone can apply to sell on Ani-Mart. RSBSA information is optional and is used only to qualify for the Verified Local Farmer badge.' ?>
                </p>

                <div class="alert alert-success border-0 rounded-4 mb-4" role="note">
                    <div class="d-flex gap-3 align-items-start">
                        <i class="bi bi-person-vcard-fill fs-3" aria-hidden="true"></i>
                        <div>
                            <strong>Have an RSBSA ID? Start with a 40 trust score.</strong>
                            <p class="mb-1 mt-1">Enter your RSBSA number and upload a clear photo of the card. Its first and last name must match your application before Ani-Mart can display the <strong>RSBSA ID Seller</strong> badge.</p>
                            <small>Sellers without RSBSA information can still join and will start at 0 with the <strong>New Seller · Building Trust</strong> badge.</small>
                        </div>
                    </div>
                </div>

                <form
                    id="farmer-application-form"
                    method="POST"
                    enctype="multipart/form-data"
                    action="/ani-mart/public/farmer/apply"
                >

                    <input
                        type="hidden"
                        name="_token"
                        value="<?= csrf_token(); ?>"
                    >

                    <!-- SELLER PROFILE -->

                    <div class="mb-4">

                        <h2 class="h5 fw-bold mb-1">Seller Profile</h2>

                        <p class="text-muted small mb-3">
                            This information will appear on your public seller profile after approval.
                        </p>

                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4"><label class="form-label fw-semibold">First Name</label><input type="text" name="first_name" class="form-control modern-input" value="<?= htmlspecialchars($application->first_name ?? $_SESSION['user']['first_name'] ?? '') ?>" required></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Middle Name</label><input type="text" name="middle_name" class="form-control modern-input" value="<?= htmlspecialchars($application->middle_name ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Last Name</label><input type="text" name="last_name" class="form-control modern-input" value="<?= htmlspecialchars($application->last_name ?? $_SESSION['user']['last_name'] ?? '') ?>" required></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Name Extension</label><input type="text" name="name_extension" class="form-control modern-input" placeholder="Jr., Sr., III" value="<?= htmlspecialchars($application->name_extension ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Sex <span class="text-muted">(optional)</span></label><select name="sex" class="form-select modern-input"><option value="">Prefer not to provide</option><?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say'] as $value => $label): ?><option value="<?= $value ?>" <?= (($application->sex ?? '') === $value) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Date of Birth <span class="text-muted">(optional)</span></label><input type="date" name="date_of_birth" class="form-control modern-input" value="<?= htmlspecialchars($application->date_of_birth ?? '') ?>"></div>
                        <div class="col-12"><label class="form-label fw-semibold">RSBSA Number <span class="text-muted">(optional)</span></label><input type="text" name="rsbsa_number" class="form-control modern-input" value="<?= htmlspecialchars($application->rsbsa_number ?? '') ?>"><small class="text-muted">If you have an RSBSA ID, enter its number and upload the card below to begin at 40 trust points.</small></div>
                    </div>

                    <!-- SELLER PROFILE IMAGE -->

                    <div class="mb-4">

                        <label for="seller_profile_image" class="form-label fw-semibold">
                            Seller Profile Image
                        </label>

                        <input
                            type="file"
                            name="seller_profile_image"
                            id="seller_profile_image"
                            class="form-control modern-input"
                            accept="image/jpeg,image/png,image/webp"
                            required
                        >

                        <small class="text-muted">
                            Upload a clear image that represents your shop. It will be shown on your public seller profile after approval.
                        </small>

                    </div>

                    <!-- SHOP NAME -->

                    <div class="mb-4">

                        <label class="form-label fw-semibold">

                            Shop Name

                        </label>

                        <input
                            type="text"
                            name="farm_name"
                            class="form-control modern-input"
                            placeholder="Ex. Green Valley Produce"
                            value="<?= htmlspecialchars($application->farm_name ?? '') ?>"
                            required
                        >

                    </div>

                    <!-- SHOP DESCRIPTION -->

                    <div class="mb-4">

                        <label class="form-label fw-semibold">

                            Shop Description

                        </label>

                        <textarea
                            name="farm_description"
                            rows="5"
                            class="form-control modern-input"
                            placeholder="Tell customers about your shop and products..."
                            required
                        ><?= htmlspecialchars($application->farm_description ?? '') ?></textarea>

                    </div>

                    <div class="row g-4 mb-4">

                        <div class="col-md-7">

                            <label class="form-label fw-semibold">
                                Farm or Shop Address
                            </label>

                            <input
                                type="text"
                                name="address"
                                class="form-control modern-input"
                                placeholder="Barangay, city, or complete shop address"
                                value="<?= htmlspecialchars($application->address ?? '') ?>"
                                required
                            >

                        </div>

                        <div class="col-md-5">

                            <label class="form-label fw-semibold">
                                Contact Number
                            </label>

                            <input
                                type="tel"
                                name="contact_number"
                                class="form-control modern-input"
                                placeholder="09XX XXX XXXX"
                                value="<?= htmlspecialchars($application->contact_number ?? '') ?>"
                                required
                            >

                        </div>

                    </div>

                    <!-- VERIFICATION DOCUMENTS -->

                    <div class="mb-4">

                        <label class="form-label fw-semibold">

                            RSBSA Farmer Registry Card <span class="text-muted">(optional)</span>

                        </label>

                        <input
                            type="file"
                            name="rsbsa_card"
                            id="rsbsa_card"
                            class="form-control modern-input"
                            accept="image/*"
                        >

                        <small class="text-muted">
                            <strong>Recommended for RSBSA ID holders:</strong> upload the clear front of your Department of Agriculture
                            National Registry System for Farmers and Fishers card. Include
                            its QR code and RSBSA reference number (JPG, PNG, or WEBP). Regular sellers may leave this blank and start at 0 trust points.
                        </small>

                        <small class="text-muted d-block mt-2">
                            Your document is stored as optional application evidence. Ani-Mart does not authenticate government registration from the uploaded image.
                        </small>

                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="verification_declaration" value="1" id="verification-declaration" required>
                        <label class="form-check-label" for="verification-declaration">I confirm that my seller information is accurate. If I supplied RSBSA information, I confirm it is mine and understand it requires administrator verification.</label>
                    </div>

                    <button
                        id="submit-application-button"
                        class="btn btn-success btn-lg rounded-pill px-5"
                    >

                        <i class="bi bi-send me-2"></i>

                        <?= !empty($isEditing) ? 'Update Application' : 'Submit Application' ?>

                    </button>

                    <div id="verification-progress" class="alert alert-success border-0 mt-4 d-none" role="status">
                        <div class="d-flex align-items-center gap-3">
                            <div class="spinner-border spinner-border-sm" aria-hidden="true"></div>
                            <div><strong id="application-progress-title">Submitting your seller application.</strong><br><small id="application-progress-copy">Please wait while Ani-Mart prepares it for administrator review.</small></div>
                        </div>
                    </div>

                </form>

            </div>

        </div>

        <?php endif; ?>

        <!-- RIGHT SIDE -->

        <div class="col-lg-5">

            <div class="preview-card">

                <div class="seller-profile-image-preview <?= !empty($application->seller_profile_image_path) ? 'has-image' : '' ?>" id="seller-profile-image-preview" aria-live="polite">
                    <img
                        id="preview-image"
                        src="<?= !empty($application->seller_profile_image_path) ? htmlspecialchars($application->seller_profile_image_path) : 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?q=80&w=1200' ?>"
                        class="img-fluid rounded-5 shadow-sm preview-banner"
                        alt="Seller profile preview"
                    >
                    <div class="seller-profile-image-placeholder" id="seller-profile-image-placeholder">
                        <i class="bi bi-image"></i>
                        <span>Your seller profile image</span>
                    </div>
                </div>

                <div class="preview-content">

                    <span class="badge bg-success text-white mb-3">

                        Seller Profile Preview

                    </span>

                    <h3 class="fw-bold">

                        <span id="profile-name-preview">Your Shop Name</span>

                    </h3>

                    <p class="text-muted preview-description">

                        <span id="profile-description-preview">Your shop description will appear here. Once approved, customers can discover your shop and products in the marketplace.</span>

                    </p>

                    <div id="preview-info" class="text-secondary small mb-3">
                        Complete your seller profile to preview how it will appear to customers.
                    </div>

                    <div class="row mt-4">

                        <div class="col-4">

                            <div class="mini-stat">

                                <h5 class="fw-bold">

                                    24/7

                                </h5>

                                <small class="text-muted">

                                    Marketplace
                                </small>

                            </div>

                        </div>

                        <div class="col-4">

                            <div class="mini-stat">

                                <h5 class="fw-bold">

                                    Secure

                                </h5>

                                <small class="text-muted">

                                    Verification
                                </small>

                            </div>

                        </div>

                        <div class="col-4">

                            <div class="mini-stat">

                                <h5 class="fw-bold">

                                    Fast

                                </h5>

                                <small class="text-muted">

                                    Approval
                                </small>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>



<script>

const previewInfo = document.getElementById('preview-info');
const shopNameInput = document.querySelector('[name="farm_name"]');
const sellerDescriptionInput = document.querySelector('[name="farm_description"]');
const profileNamePreview = document.getElementById('profile-name-preview');
const profileDescriptionPreview = document.getElementById('profile-description-preview');
const sellerProfileImageInput = document.getElementById('seller_profile_image');
const profileImagePreview = document.getElementById('preview-image');
const profileImagePreviewContainer = document.getElementById('seller-profile-image-preview');

function updateSellerProfilePreview() {
    const shopName = shopNameInput.value.trim();
    const description = sellerDescriptionInput.value.trim();

    profileNamePreview.textContent = shopName || 'Your Shop Name';
    profileDescriptionPreview.textContent = description
        || 'Your shop description will appear here. Once approved, customers can discover your shop and products in the marketplace.';
    previewInfo.textContent = shopName || description
        ? 'This is a preview of your seller profile.'
        : 'Complete your seller profile to preview how it will appear to customers.';
}

if (shopNameInput && sellerDescriptionInput) {
    shopNameInput.addEventListener('input', updateSellerProfilePreview);
    sellerDescriptionInput.addEventListener('input', updateSellerProfilePreview);
    updateSellerProfilePreview();
}

if (sellerProfileImageInput) {
    sellerProfileImageInput.addEventListener('change', function () {
        const [image] = this.files;

        if (!image) {
            profileImagePreviewContainer.classList.remove('has-image');
            return;
        }

        const reader = new FileReader();

        reader.addEventListener('load', function () {
            profileImagePreview.src = reader.result;
            profileImagePreviewContainer.classList.add('has-image');
        });

        reader.readAsDataURL(image);
    });
}

const applicationForm = document.getElementById('farmer-application-form');

if (applicationForm) {
    applicationForm.addEventListener('submit', function () {
        const rsbsaInput = document.getElementById('rsbsa_card');
        if (rsbsaInput && rsbsaInput.files.length > 0) {
            document.getElementById('application-progress-title').textContent = 'SmartVerify™ is analyzing your optional RSBSA card.';
            document.getElementById('application-progress-copy').textContent = 'Your seller application will include a verification recommendation for the administrator.';
        }
        document.getElementById('verification-progress').classList.remove('d-none');
        document.getElementById('submit-application-button').setAttribute('disabled', 'disabled');
    });
}

</script>

<?php

$content = ob_get_clean();

require ANI_MART_ROOT . '/app/Views/layouts/main.php';

?>
