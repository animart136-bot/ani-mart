<?php

$title = "Edit Product";
$shopThemes = \App\Services\SellerProfileService::themes();
$savedThemeKey = $product->shop_theme ?? 'fresh-farm';
$shopTheme = $shopThemes[$savedThemeKey] ?? $shopThemes['fresh-farm'];

ob_start();

$hasSmartPriceWarning = !empty($smartPriceWarning);

?>

<div class="container app-page-spacing product-create-page" style="
    --editor-accent: <?= htmlspecialchars($shopTheme['accent']) ?>;
    --editor-secondary: <?= htmlspecialchars($shopTheme['secondary']) ?>;
    --editor-background: <?= htmlspecialchars($shopTheme['background']) ?>;
    --editor-ink: <?= htmlspecialchars($shopTheme['text']) ?>;
">

    <header class="hero product-edit-hero mb-5" aria-labelledby="editProductTitle">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <div class="product-edit-hero-kicker">
                    <a href="/ani-mart/public/farmer/products" class="product-editor-back">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i> My Products
                    </a>
                    <span class="hero-badge"><?= htmlspecialchars($product->shop_name ?? 'Your Farm') ?> · Your Products</span>
                </div>
                <h1 id="editProductTitle" class="hero-title mt-3 mb-2">Edit your product</h1>
                <p class="hero-subtitle mb-0">Refresh your product details, update pricing, and keep your farm store looking its best.</p>
            </div>

            <div class="col-lg-5">
                <div class="product-editor-hero-note">
                    <img
                        id="hero-product-image"
                        class="product-editor-hero-image"
                        src="<?= htmlspecialchars(product_image_url($product->image ?? null)) ?>"
                        alt="<?= htmlspecialchars($product->name ?? 'Product') ?>">
                    <div>
                        <span class="product-editor-note-label">Currently editing</span>
                        <strong><?= htmlspecialchars($product->name ?? 'Your product') ?></strong>
                        <span>Details, photos, pricing, and stock</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="row align-items-start g-4">

        <!-- LEFT -->
        <div class="col-lg-8">

            <form
                method="POST"
                enctype="multipart/form-data"
                action="/ani-mart/public/farmer/products/update"
                class="product-form">

                <input type="hidden" name="_token" value="<?= csrf_token(); ?>">
                <input
                    type="hidden"
                    name="id"
                    value="<?= htmlspecialchars($product->id) ?>">

                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-light-subtle product-price-check">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h2 class="h5 fw-bold mb-0">Smart Price Check</h2>
                        <span class="badge bg-success-subtle text-success rounded-pill">Reference-based</span>
                    </div>
                    <div id="smart-price-card" class="text-muted small">
                        <?php if ($hasSmartPriceWarning): ?>
                            <div class="alert alert-warning mb-0 p-3 rounded-4">
                                <strong><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> Price Review Recommended</strong><br>
                                The price needs your confirmation before this product can be updated. Review it, keep the acknowledgement selected below, then click Save Changes again.
                            </div>
                        <?php else: ?>
                            Enter a product name and price to compare the current reference market level.
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="continue_anyway" id="continueAnywayInput" value="<?= $hasSmartPriceWarning ? '1' : '0' ?>">
                    <input type="hidden" name="price_warning_acknowledged" id="priceWarningAcknowledgedInput" value="<?= $hasSmartPriceWarning ? '1' : '0' ?>">
                    <label class="form-check small mt-3 p-3 rounded-3 price-warning-consent<?= $hasSmartPriceWarning ? ' is-required' : '' ?>">
                        <input class="form-check-input" type="checkbox" id="priceWarningConsent" <?= $hasSmartPriceWarning ? 'checked' : '' ?>>
                        <span class="form-check-label">I reviewed the price comparison and want to continue with this price.</span>
                    </label>
                </div>

                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Product Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="<?= htmlspecialchars($product->name) ?>"
                        class="form-control modern-input"
                        aria-describedby="productNameEnglishHelp"
                        required>

                    <small id="productNameEnglishHelp" class="form-text text-muted d-block mt-2">
                        <i class="bi bi-translate me-1" aria-hidden="true"></i>
                        Please enter the product name in English so customers can find it and Smart Price Check can match it correctly.
                    </small>

                </div>

                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Category
                    </label>

                    <select
                        name="category_id"
                        class="form-select modern-input"
                        required>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= htmlspecialchars($category->id) ?>"
                                <?= $product->category_id == $category->id ? 'selected' : '' ?>>

                                <?= htmlspecialchars($category->name) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Description
                    </label>

                    <textarea
                        name="description"
                        rows="5"
                        class="form-control modern-input"
                        placeholder="Describe your product..."><?= htmlspecialchars($product->description) ?></textarea>

                </div>

                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Product Image
                    </label>

                    <div class="upload-box">

                        <input
                            id="image-input"
                            type="file"
                            name="image"
                            class="form-control modern-input">

                        <small class="text-muted">
                            Leave empty to keep the current image. PNG, JPG, WEBP supported.
                        </small>

                    </div>

                </div>

                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Product Gallery
                    </label>

                    <div class="product-gallery-uploader border rounded-4 p-3">

                        <div
                            id="galleryDropzone"
                            class="gallery-dropzone rounded-4 text-center py-3 mb-3">
                            <i class="bi bi-images fs-2 text-success"></i>
                            <p class="mb-1 mt-2 fw-semibold">Drag & drop up to 10 images</p>
                            <p class="text-muted small">JPEG, PNG, WEBP supported. Click to browse.</p>
                        </div>

                        <input
                            type="file"
                            id="product_images_input"
                            name="product_images[]"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            class="d-none">

                        <input
                            type="hidden"
                            name="gallery_cover"
                            value="0">

                        <div class="row g-3" id="galleryPreview"></div>

                        <?php if (!empty($productImages)): ?>
                            <div class="mt-4">
                                <h6 class="mb-2">Current Gallery</h6>
                                <div id="productGalleryCarousel" class="carousel slide product-gallery-carousel" data-bs-ride="false">
                                    <div class="carousel-inner">
                                        <?php foreach ($productImages as $index => $image): ?>
                                            <div class="carousel-item<?= $index === 0 ? ' active' : '' ?>">
                                                <img src="<?= htmlspecialchars($image->image_url) ?>" class="d-block w-100 rounded-3" alt="Current gallery image">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if (count($productImages) > 1): ?>
                                        <button class="carousel-control-prev" type="button" data-bs-target="#productGalleryCarousel" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Previous</span>
                                        </button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#productGalleryCarousel" data-bs-slide="next">
                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Next</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <p class="text-muted small mt-2">Any newly uploaded images will be added to the existing gallery.</p>
                            </div>
                        <?php endif; ?>

                    </div>

                </div>

                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Price Type
                    </label>

                    <select
                        name="price_type"
                        id="priceType"
                        class="form-select modern-input"
                        required>

                        <option value="retail" <?= ($product->price_type ?? 'retail') === 'retail' ? 'selected' : '' ?>>Retail</option>
                        <option value="wholesale" <?= ($product->price_type ?? 'retail') === 'wholesale' ? 'selected' : '' ?>>Wholesale</option>
                        <option value="both" <?= ($product->price_type ?? 'retail') === 'both' ? 'selected' : '' ?>>Both</option>

                    </select>

                    <small class="text-muted d-block mt-2">
                        Retail is single-unit pricing; wholesale is bulk pricing for larger orders. Choose Both to show both pricing fields.
                    </small>

                </div>

                <div class="mb-4">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <label class="form-label fw-semibold mb-0">
                                Pricing and Stock
                            </label>
                            <p class="text-muted small mb-0">Update the package size, price, and stock. Existing package options are shown below.</p>
                        </div>
                    </div>

                    <div class="variant-table">
                        <div id="variantRows"></div>
                    </div>

                </div>

                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Harvest Date
                    </label>

                    <input
                        type="date"
                        name="harvest_date"
                        value="<?= htmlspecialchars($product->harvest_date ?? '') ?>"
                        class="form-control modern-input"
                        max="<?= date('Y-m-d') ?>">

                </div>

                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Harvest Photo
                    </label>

                    <div class="upload-box">

                        <input
                            type="file"
                            name="harvest_photo"
                            class="form-control modern-input">

                        <small class="text-muted">
                            Leave empty to keep the current verification photo.
                        </small>

                        <?php if (!empty($product->harvest_photo)): ?>

                            <div class="mt-3">

                                <a
                                    href="<?= htmlspecialchars($product->harvest_photo) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-sm btn-outline-success rounded-pill">
                                    <i class="bi bi-image me-1"></i>
                                    View Current Harvest Photo
                                </a>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="d-flex gap-3 flex-wrap">

                    <button
                        type="submit"
                        class="btn btn-success btn-lg px-5 rounded-pill shadow-sm">
                        <i class="bi bi-check-circle me-2"></i>
                        Save Changes
                    </button>

                    <a
                        href="/ani-mart/public/farmer/products"
                        class="btn btn-outline-success btn-lg px-4 rounded-pill">
                        Cancel
                    </a>

                </div>

            </form>

        </div>

        <div class="col-lg-4">

            <div class="product-preview-heading">
                <span class="product-editor-eyebrow">LIVE PREVIEW</span>
                <h2>Your marketplace card</h2>
                <p>See your changes as you edit.</p>
            </div>
            <div class="preview-card">

                <img
                    id="preview-image"
                    src="<?= htmlspecialchars(
                        !empty($product->image)
                            ? $product->image
                            : 'https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=1200'
                    ) ?>"
                    class="img-fluid rounded-5 shadow-sm preview-banner"
                    alt="Product Preview">

                <div class="preview-content">

                    <span
                        id="preview-category"
                        class="badge bg-success-subtle text-success mb-3">
                        <?php
                            $selectedCategory = 'Fresh Marketplace';

                            foreach ($categories as $category) {
                                if ($product->category_id == $category->id) {
                                    $selectedCategory = $category->name;
                                    break;
                                }
                            }

                            echo htmlspecialchars($selectedCategory);
                        ?>
                    </span>

                    <h3
                        id="preview-name"
                        class="fw-bold mb-3">
                        <?= htmlspecialchars($product->name) ?>
                    </h3>

                    <p
                        id="preview-description"
                        class="text-muted">
                        <?= htmlspecialchars($product->description ?: 'Product description preview will appear here.') ?>
                    </p>

                    <div class="row g-3 mt-2">

                        <div class="col-6">

                            <div class="mini-stat">

                                <h5
                                    id="preview-price"
                                    class="fw-bold mb-1">
                                    ₱<?= number_format($product->price, 2) ?>
                                </h5>

                                <small class="text-muted">
                                    Product Price
                                </small>

                            </div>

                        </div>

                        <div class="col-6">

                            <div class="mini-stat">

                                <h5
                                    id="preview-stock"
                                    class="fw-bold mb-1">
                                    <?= htmlspecialchars($product->stock) ?> Available
                                </h5>

                                <small class="text-muted">
                                    Available Stock
                                </small>

                            </div>

                        </div>

                        <div class="col-12">

                            <div class="mini-stat">

                                <h5
                                    id="preview-date"
                                    class="fw-bold mb-1">
                                    <?= !empty($product->harvest_date)
                                        ? date(
                                            'F j, Y',
                                            strtotime($product->harvest_date)
                                        )
                                        : 'No Harvest Date' ?>
                                </h5>

                                <small class="text-muted">
                                    Harvest Date
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
    const productName =
        document.querySelector('input[name="name"]');

    const retailPriceInput = document.querySelector('input[name="retail_price[]"]');
    const category =
        document.querySelector('select[name="category_id"]');

    const description =
        document.querySelector('textarea[name="description"]');

    const harvestDate =
        document.querySelector('input[name="harvest_date"]');

    const imageInput =
        document.querySelector('input[name="image"]');

    const galleryInput =
        document.getElementById('product_images_input');

    const galleryCoverInput =
        document.querySelector('input[name="gallery_cover"]');

    const galleryDropzone =
        document.getElementById('galleryDropzone');

    const galleryPreview =
        document.getElementById('galleryPreview');

    let selectedGalleryFiles = [];

    const variantRows =
        document.getElementById('variantRows');


    const priceType =
        document.getElementById('priceType');

    const previewName =
        document.getElementById('preview-name');

    const previewCategory =
        document.getElementById('preview-category');

    const previewDescription =
        document.getElementById('preview-description');

    const previewPrice =
        document.getElementById('preview-price');

    const previewStock =
        document.getElementById('preview-stock');

    const previewDate =
        document.getElementById('preview-date');

    const previewImage =
        document.getElementById('preview-image');

    const heroProductImage =
        document.getElementById('hero-product-image');

    const existingVariants = <?= json_encode($productVariants ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function runSmartPriceCheck() {
        const name = (productName.value || '').trim();
        const categoryName = category.selectedOptions[0]?.text || '';
        const price = parseFloat(retailPriceInput?.value || 0);

        if (!name || !Number.isFinite(price) || price <= 0) {
            document.getElementById('smart-price-card').innerHTML = 'Enter a product name and price to compare the current reference market level.';
            return;
        }

        const formData = new FormData();
        formData.append('name', name);
        formData.append('category', categoryName);
        formData.append('seller_price', price);
        formData.append('unit', 'kg');

        fetch('/ani-mart/public/farmer/products/price-check', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const result = data.check || {};
            const ref = result.reference_price ? `₱${Number(result.reference_price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}/kg` : 'Reference price currently unavailable';
            const seller = `₱${Number(result.seller_price || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}/kg`;
            const deviation = result.price_deviation_percentage != null ? `${Number(result.price_deviation_percentage) >= 0 ? '+' : ''}${Number(result.price_deviation_percentage).toFixed(1)}%` : 'N/A';
            const assessment = result.assessment || 'REFERENCE_UNAVAILABLE';

            if (result.requires_warning) {
                document.getElementById('smart-price-card').innerHTML = `
                    <div class="alert alert-warning mb-0 p-3 rounded-4">
                        <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Price Review Recommended</strong><br>
                        Your price: <strong>${seller}</strong><br>
                        Reference price: <strong>${ref}</strong><br>
                        Difference: <strong>${deviation}</strong><br>
                        Assessment: <strong>${assessment}</strong>
                    </div>
                `;
                const consent = document.getElementById('priceWarningConsent');
                consent.required = true;
                consent.closest('.price-warning-consent').classList.add('is-required');
                return;
            }

            document.getElementById('smart-price-card').innerHTML = `
                <div class="alert alert-success mb-0 p-3 rounded-4">
                    <strong>✓ Price Check</strong><br>
                    Your price: <strong>${seller}</strong><br>
                    Reference price: <strong>${ref}</strong><br>
                    Deviation: <strong>${deviation}</strong><br>
                    Assessment: <strong>${assessment}</strong>
                </div>
            `;
            document.getElementById('continueAnywayInput').value = '0';
            document.getElementById('priceWarningAcknowledgedInput').value = '0';
            const consent = document.getElementById('priceWarningConsent');
            consent.checked = false;
            consent.required = false;
            consent.closest('.price-warning-consent').classList.remove('is-required');
        })
        .catch(() => {
            document.getElementById('smart-price-card').innerHTML = 'Reference price currently unavailable. You can still continue with the standard approval flow.';
        });
    }

    productName.addEventListener('input', runSmartPriceCheck);
    category.addEventListener('change', runSmartPriceCheck);
    if (retailPriceInput) {
        retailPriceInput.addEventListener('input', runSmartPriceCheck);
    }
    document.getElementById('priceWarningConsent')?.addEventListener('change', function () {
        document.getElementById('continueAnywayInput').value = this.checked ? '1' : '0';
        document.getElementById('priceWarningAcknowledgedInput').value = this.checked ? '1' : '0';
    });

    productName.addEventListener('input', function() {
        previewName.textContent =
            this.value.trim() ||
            'Your Product Name';
    });

    category.addEventListener('change', function() {
        previewCategory.textContent =
            this.options[this.selectedIndex].text ||
            'Fresh Marketplace';
    });

    description.addEventListener('input', function() {
        previewDescription.textContent =
            this.value.trim() ||
            'Product description preview will appear here.';
    });

    harvestDate.addEventListener('change', function() {
        if (!this.value) {
            previewDate.textContent =
                'No Harvest Date';

            return;
        }

        const date =
            new Date(this.value);

        previewDate.textContent =
            date.toLocaleDateString(
                'en-PH',
                {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                }
            );
    });

    imageInput.addEventListener('change', function(event) {
        const file =
            event.target.files[0];

        if (!file) {
            return;
        }

        const reader =
            new FileReader();

        reader.onload =
            function(readerEvent) {
                previewImage.src =
                    readerEvent.target.result;

                if (heroProductImage) {
                    heroProductImage.src = readerEvent.target.result;
                }
            };

        reader.readAsDataURL(file);
    });

    galleryDropzone.addEventListener('click', () => {
        galleryInput.click();
    });

    galleryInput.addEventListener('change', function () {
        addGalleryFiles([...this.files]);
    });

    galleryDropzone.addEventListener('dragover', function (e) {
        e.preventDefault();
        this.classList.add('gallery-dropzone-active');
    });

    galleryDropzone.addEventListener('dragleave', function () {
        this.classList.remove('gallery-dropzone-active');
    });

    galleryDropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        this.classList.remove('gallery-dropzone-active');

        const files = [...e.dataTransfer.files];
        addGalleryFiles(files);
    });


    priceType.addEventListener('change', function () {
        updateVariantVisibility();
    });

    variantRows.addEventListener('input', function (e) {
        if (e.target.matches('input[name="retail_price[]"], input[name="wholesale_price[]"], input[name="stock[]"]')) {
            updatePreviewStats();
        }

        if (e.target.matches('.variant-weight-input, .variant-unit-select')) {
            const row = e.target.closest('.product-pricing-grid');
            refreshVariantRowLabel(row);
        }
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('.remove-gallery-btn')) {
            const previewCard = e.target.closest('.gallery-preview-card');
            const index = Number(previewCard.dataset.index);

            selectedGalleryFiles.splice(index, 1);

            if (Number(galleryCoverInput.value) === index) {
                galleryCoverInput.value = '0';
            } else if (Number(galleryCoverInput.value) > index) {
                galleryCoverInput.value = String(Number(galleryCoverInput.value) - 1);
            }

            updateGalleryFileInput();
            renderGalleryPreviews();
            updatePreviewStats();
        }

        if (e.target.closest('.cover-gallery-btn')) {
            const previewCard = e.target.closest('.gallery-preview-card');
            const index = Number(previewCard.dataset.index);
            setGalleryCover(index);
        }

    });

    function addGalleryFiles(files) {
        const availableSlots = Math.max(0, 10 - selectedGalleryFiles.length);
        const allowedFiles = files.filter(file => file.type.startsWith('image/')).slice(0, availableSlots);

        if (!allowedFiles.length) {
            return;
        }

        selectedGalleryFiles = selectedGalleryFiles.concat(allowedFiles);
        updateGalleryFileInput();
        renderGalleryPreviews();
    }

    function updateGalleryFileInput() {
        const dataTransfer = new DataTransfer();

        selectedGalleryFiles.forEach(file => {
            dataTransfer.items.add(file);
        });

        galleryInput.files = dataTransfer.files;
    }

    function renderGalleryPreviews() {
        galleryPreview.innerHTML = '';

        selectedGalleryFiles.forEach((file, index) => {
            const reader = new FileReader();

            reader.onload = function (event) {
                const row = document.createElement('div');
                row.className = 'col-6 col-md-4';
                row.innerHTML = `
                    <div class="gallery-preview-card position-relative rounded-4 overflow-hidden border" data-index="${index}">
                        <img src="${event.target.result}" class="img-fluid" alt="Selected gallery image preview">
                        <button type="button" class="btn btn-sm btn-light position-absolute remove-gallery-btn" aria-label="Remove selected image" title="Remove selected image">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <button type="button" class="btn btn-sm position-absolute bottom-2 start-2 cover-gallery-btn">
                            Cover
                        </button>
                        <input type="hidden" name="gallery_order[]" value="${index}">
                    </div>
                `;

                galleryPreview.appendChild(row);
                updateGalleryCardState();
            };

            reader.readAsDataURL(file);
        });

        if (selectedGalleryFiles.length > 0 && Number(galleryCoverInput.value) >= selectedGalleryFiles.length) {
            galleryCoverInput.value = '0';
        }
    }

    function updateGalleryCardState() {
        const rows = galleryPreview.querySelectorAll('.gallery-preview-card');

        rows.forEach((row, index) => {
            row.dataset.index = index;
            const order = row.querySelector('input[name="gallery_order[]"]');
            if (order) {
                order.value = index;
            }
        });

        updateGalleryCoverButtons();
    }

    function updateGalleryCoverButtons() {
        const coverIndex = Number(galleryCoverInput.value);

        galleryPreview.querySelectorAll('.cover-gallery-btn').forEach((button, index) => {
            if (index === coverIndex) {
                button.classList.remove('btn-outline-success');
                button.classList.add('btn-success');
                button.textContent = 'Cover Image';
            } else {
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-success');
                button.textContent = 'Cover';
            }
        });

        updatePreviewImage();
    }

    function setGalleryCover(index) {
        galleryCoverInput.value = String(index);
        updateGalleryCoverButtons();
    }

    function updatePreviewImage() {
        const coverIndex = Number(galleryCoverInput.value);
        const previewCard = galleryPreview.querySelector(`.gallery-preview-card[data-index="${coverIndex}"]`);

        if (!previewCard) {
            return;
        }

        const img = previewCard.querySelector('img');
        if (img && img.src) {
            previewImage.src = img.src;
        }
    }

    function updatePreviewStats() {
        const retailInputs = Array.from(document.querySelectorAll('input[name="retail_price[]"]'));
        const wholesaleInputs = Array.from(document.querySelectorAll('input[name="wholesale_price[]"]'));
        const stockInputs = Array.from(document.querySelectorAll('input[name="stock[]"]'));

        const totalStock = stockInputs.reduce((sum, input) => {
            const value = parseInt(input.value, 10);
            return sum + (isNaN(value) ? 0 : value);
        }, 0);

        const retailPrices = retailInputs
            .map(input => parseFloat(input.value))
            .filter(value => !isNaN(value) && value > 0);

        const wholesalePrices = wholesaleInputs
            .map(input => parseFloat(input.value))
            .filter(value => !isNaN(value) && value > 0);

        const displayPrice = retailPrices.length > 0
            ? Math.min(...retailPrices)
            : wholesalePrices.length > 0
                ? Math.min(...wholesalePrices)
                : 0;

        previewPrice.textContent = `₱${displayPrice.toFixed(2)}`;
        previewStock.textContent = `${totalStock} Available`;
    }

    function formatVariantLabel(weight, unit) {
        const amount = (weight || '').trim();
        const unitValue = (unit || '').trim();

        if (!amount && !unitValue) {
            return '';
        }

        if (!unitValue) {
            return amount;
        }

        return `${amount} ${unitValue}`;
    }

    function refreshVariantRowLabel(row) {
        const weightInput = row.querySelector('.variant-weight-input');
        const unitSelect = row.querySelector('.variant-unit-select');
        const hiddenName = row.querySelector('.variant-name-hidden');
        const preview = row.querySelector('.variant-label-preview');

        if (!weightInput || !unitSelect || !hiddenName || !preview) {
            return;
        }

        const label = formatVariantLabel(weightInput.value, unitSelect.value);
        hiddenName.value = label;
        preview.textContent = label ? `Package: ${label}` : 'Enter amount and unit to create the label.';
    }

    function updateVariantVisibility() {
        const type = priceType.value;
        const showRetail = type === 'retail' || type === 'both';
        const showWholesale = type === 'wholesale' || type === 'both';
        const showMinQty = type === 'wholesale' || type === 'both';

        document.querySelector('.variant-table').dataset.priceType = type;

        document.querySelectorAll('.variant-retail').forEach(el => {
            el.style.display = showRetail ? '' : 'none';
        });
        document.querySelectorAll('.variant-wholesale').forEach(el => {
            el.style.display = showWholesale ? '' : 'none';
        });
        document.querySelectorAll('.variant-minqty').forEach(el => {
            el.style.display = showMinQty ? '' : 'none';
        });

    }

    function addVariantRow(values = {}) {
        const rowNumber = variantRows.children.length;
        const escapeAttribute = value => String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[char]));
        values = Object.fromEntries(Object.entries(values).map(([key, value]) => [key, escapeAttribute(value)]));
        const row = document.createElement('div');
        row.className = 'product-pricing-grid';
        row.innerHTML = `
            <div class="product-pricing-field">
                <label class="form-label" for="productWeight-${rowNumber}">Package size</label>
                <div class="variant-label-group">
                    <div class="variant-meta-row">
                        <input type="number" step="0.01" min="0" id="productWeight-${rowNumber}" name="variant_weight[]" class="form-control variant-weight-input" placeholder="Amount (e.g. 250)" value="${values.weight || ''}" required>
                        <select aria-label="Package unit" name="variant_unit[]" class="form-select variant-unit-select">
                            <option value="" ${!values.unit ? 'selected' : ''}>Unit</option>
                            <option value="g" ${values.unit === 'g' ? 'selected' : ''}>g</option>
                            <option value="kg" ${values.unit === 'kg' ? 'selected' : ''}>kg</option>
                            <option value="pcs" ${values.unit === 'pcs' ? 'selected' : ''}>pcs</option>
                            <option value="bundle" ${values.unit === 'bundle' ? 'selected' : ''}>bundle</option>
                            <option value="box" ${values.unit === 'box' ? 'selected' : ''}>box</option>
                            <option value="set" ${values.unit === 'set' ? 'selected' : ''}>set</option>
                        </select>
                    </div>
                    <input type="hidden" name="variant_name[]" class="variant-name-hidden" value="${values.name || ''}">
                    <p class="text-muted small mt-2 variant-label-preview"></p>
                </div>
            </div>
            <div class="product-pricing-field variant-retail">
                <label class="form-label" for="productRetail-${rowNumber}">Retail price</label>
                <div class="input-group">
                    <span class="input-group-text">₱</span>
                    <input type="number" step="0.01" id="productRetail-${rowNumber}" name="retail_price[]" class="form-control" value="${values.retail_price || ''}" placeholder="Retail">
                </div>
            </div>
            <div class="product-pricing-field variant-wholesale">
                <label class="form-label" for="productWholesale-${rowNumber}">Wholesale price</label>
                <div class="input-group">
                    <span class="input-group-text">₱</span>
                    <input type="number" step="0.01" id="productWholesale-${rowNumber}" name="wholesale_price[]" class="form-control" value="${values.wholesale_price || ''}" placeholder="Wholesale">
                </div>
            </div>
            <div class="product-pricing-field variant-minqty">
                <label class="form-label" for="productMinimum-${rowNumber}">Minimum wholesale quantity</label>
                <input type="number" id="productMinimum-${rowNumber}" name="minimum_wholesale_quantity[]" class="form-control" value="${values.minimum_wholesale_quantity || ''}" placeholder="Qty">
            </div>
            <div class="product-pricing-field">
                <label class="form-label" for="productStock-${rowNumber}">Available stock</label>
                <input type="number" id="productStock-${rowNumber}" name="stock[]" class="form-control" value="${values.stock || 0}" required>
            </div>
            <input type="hidden" name="variant_active[]" value="${values.is_active ?? 1}">
        `;

        variantRows.appendChild(row);
        refreshVariantRowLabel(row);
    }

    function initializeVariants() {
        if (existingVariants.length > 0) {
            existingVariants.forEach(variant => {
                addVariantRow({
                    name: variant.variant_name,
                    weight: variant.weight,
                    unit: variant.unit,
                    retail_price: variant.retail_price || '',
                    wholesale_price: variant.wholesale_price || '',
                    minimum_wholesale_quantity: variant.minimum_wholesale_quantity || '',
                    stock: variant.stock || 0,
                    is_active: variant.is_active
                });
            });
        } else {
            addVariantRow({
                name: '250 g',
                weight: '250',
                unit: 'g',
                retail_price: '',
                wholesale_price: '',
                minimum_wholesale_quantity: '',
                stock: 0,
                is_active: 1
            });
        }
    }

    initializeVariants();
    updateVariantVisibility();
    updatePreviewStats();
</script>

<?php

$content = ob_get_clean();

$pageCss = '/ani-mart/public/assets/css/pages/product-editor.css?v='
    . filemtime(__DIR__ . '/../../../../public/assets/css/pages/product-editor.css');
require __DIR__ . '/../../layouts/main.php';

?>
