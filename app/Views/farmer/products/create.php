<?php

$title = "Create Product";
$shopThemes = \App\Services\SellerProfileService::themes();
$savedThemeKey = $activeShop->shop_theme ?? 'fresh-farm';
$shopTheme = $shopThemes[$savedThemeKey] ?? $shopThemes['fresh-farm'];

ob_start();

?>

<div class="container app-page-spacing product-create-page" style="
    --editor-accent: <?= htmlspecialchars($shopTheme['accent']) ?>;
    --editor-secondary: <?= htmlspecialchars($shopTheme['secondary']) ?>;
    --editor-background: <?= htmlspecialchars($shopTheme['background']) ?>;
    --editor-ink: <?= htmlspecialchars($shopTheme['text']) ?>;
">

    <header class="product-create-hero" aria-labelledby="createProductTitle">
        <div class="product-create-hero-copy">
            <span class="product-create-eyebrow">
                <i class="bi bi-shop" aria-hidden="true"></i>
                <?= htmlspecialchars($activeShop->shop_name ?? 'Your shop') ?>
            </span>
            <h1 id="createProductTitle">Add a new product</h1>
            <p>Fresh listing. Ready for customers.</p>
            <span class="product-create-hero-description">Add product details, pricing, stock, and photos for your marketplace listing.</span>
        </div>
        <a href="/ani-mart/public/farmer/products" class="product-create-hero-action">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>My Products
        </a>
    </header>

    <aside class="product-review-reminder" aria-label="Product approval reminder">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <div>
                <strong>Price-based automatic approval</strong>
                <p>A product is automatically published when Smart Price Check finds an available reference and your price is within its normal range.</p>
                <small>Listings with an unusual price or no suitable reference remain pending administrator review.</small>
            </div>
    </aside>

    <div class="row align-items-start g-4">

        <!-- LEFT -->
        <div class="col-lg-8">

            <form
                method="POST"
                enctype="multipart/form-data"
                action="/ani-mart/public/farmer/products/store"
                class="product-form">

                <input
                    type="hidden"
                    name="_token"
                    value="<?= csrf_token(); ?>">

                <div id="smartPriceSection" class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-light-subtle product-price-check<?= !empty($smartPriceWarning) ? ' needs-attention' : '' ?>" tabindex="-1">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h2 class="h5 fw-bold mb-0">Smart Price Check</h2>
                        <span class="badge bg-success-subtle text-success rounded-pill">Reference-based</span>
                    </div>
                    <div id="smart-price-card" class="text-muted small">
                        <?php if (!empty($smartPriceWarning)): ?>
                            <div class="alert alert-warning mb-0 p-3 rounded-4">
                                <strong>Price Review Recommended</strong><br>
                                Review the recommended reference price below, then confirm if you want to keep your price.
                                <?php if (!empty($smartPriceWarning['reference_price'])): ?>
                                    <br>Recommended reference price:
                                    <strong>₱<?= number_format((float) $smartPriceWarning['reference_price'], 2) ?>/<?= htmlspecialchars($smartPriceWarning['reference_unit'] ?? 'kg') ?></strong>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            Enter a product name and price to compare the current reference market level.
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="continue_anyway" id="continueAnywayInput" value="0">
                    <input type="hidden" name="price_warning_acknowledged" id="priceWarningAcknowledgedInput" value="0">
                    <label id="priceWarningConsentRow" class="form-check small mt-3<?= empty($smartPriceWarning) ? ' d-none' : '' ?>">
                        <input class="form-check-input" type="checkbox" id="priceWarningConsent">
                        <span class="form-check-label">I reviewed the recommended price and want to continue with my price.</span>
                    </label>
                </div>

                <!-- PRODUCT NAME -->
                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Product Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        class="form-control modern-input"
                        placeholder="Ex: Fresh Organic Tomato"
                        aria-describedby="productNameEnglishHelp"
                        required>

                    <small id="productNameEnglishHelp" class="form-text text-muted d-block mt-2">
                        <i class="bi bi-translate me-1" aria-hidden="true"></i>
                        Please enter the product name in English so customers can find it and Smart Price Check can match it correctly.
                    </small>

                </div>

                <!-- CATEGORY -->
                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Category
                    </label>

                    <select
                        name="category_id"
                        class="form-select modern-input"
                        required>

                        <option value="" selected disabled>
                            Select a category
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option value="<?= $category->id ?>">

                                <?= htmlspecialchars($category->name) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- DESCRIPTION -->
                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Description
                    </label>

                    <textarea
                        name="description"
                        rows="5"
                        class="form-control modern-input"
                        placeholder="Describe your product..."></textarea>

                </div>

                <!-- IMAGE GALLERY -->
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

                    </div>

                </div>

                <!-- PRICE TYPE -->
                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Price Type
                    </label>

                    <select
                        name="price_type"
                        id="priceType"
                        class="form-select modern-input"
                        required>

                        <option value="retail">Retail</option>
                        <option value="wholesale">Wholesale</option>
                        <option value="both">Both</option>

                    </select>

                <small class="text-muted d-block mt-2">
                    Retail is single-unit pricing; wholesale is bulk pricing for larger orders. Choose Both to show both pricing fields.
                </small>

                </div>

                <!-- VARIANTS -->
                <div class="mb-4">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <label class="form-label fw-semibold mb-0">
                                Pricing and Stock
                            </label>
                            <p class="text-muted small mb-0">Set the package size, price, and available stock for this product.</p>
                        </div>
                    </div>

                    <div class="variant-table">
                        <div id="variantRows"></div>
                    </div>

                </div>

                <!-- HARVEST DATE -->
                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Harvest Date
                    </label>

                    <input
                        type="date"
                        name="harvest_date"
                        id="harvestDate"
                        class="form-control modern-input"
                        max="<?= date('Y-m-d') ?>">

                    <small class="text-muted">
                        Enter the actual harvest date of the product.
                    </small>

                </div>

                <!-- HARVEST PHOTO -->
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
                            Add a field, crate, or packing photo for farm-to-table verification.
                        </small>
                    </div>

                </div>

                <!-- BUTTON -->
                <button
                    type="submit"
                    class="btn btn-success btn-lg px-5 rounded-pill shadow-sm">
                    <i class="bi bi-plus-circle me-2"></i>
                    Publish Product
                </button>

            </form>

        </div>

        <!-- RIGHT -->
        <div class="col-lg-4">

            <div class="product-preview-heading">
                <span class="product-editor-eyebrow">LIVE PREVIEW</span>
                <h2>Your marketplace card</h2>
                <p>See your changes as you edit.</p>
            </div>
            <div class="preview-card">

                <!-- LIVE PRODUCT IMAGE -->
                <img
                    id="preview-image"
                    src="https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=1200"
                    class="img-fluid rounded-5 shadow-sm preview-banner"
                    alt="Product Preview">
                <div class="preview-content">
                    <!-- CATEGORY -->
                    <span
                        id="preview-category"
                        class="badge bg-success-subtle text-success mb-3">
                        Fresh Marketplace
                    </span>

                    <!-- PRODUCT NAME -->
                    <h3
                        id="preview-name"
                        class="fw-bold mb-3">
                        Your Product Name
                    </h3>

                    <!-- DESCRIPTION -->
                    <p
                        id="preview-description"
                        class="text-muted">
                        Product description preview will appear here.
                    </p>

                    <div class="row g-3 mt-2">

                        <!-- PRICE -->
                        <div class="col-6">

                            <div class="mini-stat">

                                <h5
                                    id="preview-price"
                                    class="fw-bold mb-1">
                                    ₱0.00
                                </h5>

                                <small class="text-muted">
                                    Product Price
                                </small>

                            </div>

                        </div>

                        <!-- STOCK -->
                        <div class="col-6">

                            <div class="mini-stat">

                                <h5
                                    id="preview-stock"
                                    class="fw-bold mb-1">
                                    0
                                </h5>

                                <small class="text-muted">
                                    Available Stock
                                </small>

                            </div>

                        </div>

                        <!-- HARVEST DATE -->
                        <div class="col-12">

                            <div class="mini-stat">

                                <h5
                                    id="preview-date"
                                    class="fw-bold mb-1">
                                    No Harvest Date
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

<!-- LIVE PREVIEW SCRIPT -->
<script>
    // Do not let a refresh reopen this form halfway down the page.
    const shouldFocusSmartPriceWarning = <?= !empty($smartPriceWarning) ? 'true' : 'false' ?>;
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }

    window.addEventListener('pageshow', () => {
        if (shouldFocusSmartPriceWarning) {
            document.getElementById('smartPriceSection')?.scrollIntoView({ block: 'center' });
        } else if (!window.location.hash) {
            window.scrollTo(0, 0);
        }
    });

    /*
|--------------------------------------------------------------------------
| FORM ELEMENTS
|--------------------------------------------------------------------------
*/

    const productName =
        document.querySelector('input[name="name"]');

    const priceInput = document.querySelector('input[name="retail_price[]"]');
    const category =
        document.querySelector('select[name="category_id"]');

    const description =
        document.querySelector('textarea[name="description"]');

    const harvestDate =
        document.querySelector('input[name="harvest_date"]');

    const galleryInput =
        document.getElementById('product_images_input');

    const galleryCoverInput =
        document.querySelector('input[name="gallery_cover"]');

    const galleryDropzone =
        document.getElementById('galleryDropzone');

    const galleryPreview =
        document.getElementById('galleryPreview');

    const variantRows =
        document.getElementById('variantRows');


    const priceType =
        document.getElementById('priceType');

    const productForm = document.querySelector('.product-form');
    const smartPriceSection = document.getElementById('smartPriceSection');
    const smartPriceCard = document.getElementById('smart-price-card');
    const priceWarningConsent = document.getElementById('priceWarningConsent');
    const priceWarningConsentRow = document.getElementById('priceWarningConsentRow');
    let latestSmartPriceCheck = <?= json_encode($smartPriceWarning ?? null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    let priceCheckSequence = 0;

    function setPriceWarningAcknowledgement(checked) {
        priceWarningConsent.checked = checked;
        document.getElementById('continueAnywayInput').value = checked ? '1' : '0';
        document.getElementById('priceWarningAcknowledgedInput').value = checked ? '1' : '0';
    }

    function showSmartPriceResult(result, price) {
        const reference = result.reference_price
            ? `₱${Number(result.reference_price).toFixed(2)}/${result.reference_unit || 'kg'}`
            : 'Reference price unavailable';
        const deviation = result.price_deviation_percentage == null
            ? 'N/A'
            : `${Number(result.price_deviation_percentage) >= 0 ? '+' : ''}${Number(result.price_deviation_percentage).toFixed(1)}%`;
        const referenceUnavailable = !result.reference_available;
        const heading = referenceUnavailable
            ? 'Reference Unavailable'
            : (result.requires_warning ? 'Price Review Recommended' : 'Price Check Complete');
        const referenceLabel = result.requires_warning ? 'Recommended reference price' : 'Reference price';

        smartPriceCard.innerHTML = `<div class="alert ${referenceUnavailable ? 'alert-secondary' : (result.requires_warning ? 'alert-warning' : 'alert-success')} mb-0 p-3 rounded-4"><strong>${heading}</strong><br>Your price: <strong>₱${price.toFixed(2)}/kg</strong><br>${referenceLabel}: <strong>${reference}</strong><br>Difference: <strong>${deviation}</strong><br><small>${result.message?.body || 'No current reference price was found for this product.'}</small></div>`;
        priceWarningConsentRow.classList.toggle('d-none', !result.requires_warning);

        if (!result.requires_warning) {
            setPriceWarningAcknowledgement(false);
        }
    }

    function runSmartPriceCheck({ preserveAcknowledgement = false } = {}) {
        const name = (productName.value || '').trim();
        const categoryName = category.selectedOptions[0]?.text || '';
        const currentPriceInput = document.querySelector('input[name="retail_price[]"]');
        const price = parseFloat(currentPriceInput?.value || 0);
        if (!name || !Number.isFinite(price) || price <= 0) return Promise.resolve(null);

        const requestSequence = ++priceCheckSequence;
        if (!preserveAcknowledgement) {
            setPriceWarningAcknowledgement(false);
        }

        const formData = new FormData();
        formData.append('name', name);
        formData.append('category', categoryName);
        formData.append('seller_price', price);
        formData.append('unit', 'kg');
        return fetch('/ani-mart/public/farmer/products/price-check', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: formData })
            .then(response => response.json())
            .then(data => {
                const result = data.check || {};
                if (requestSequence !== priceCheckSequence) return latestSmartPriceCheck;
                latestSmartPriceCheck = result;
                showSmartPriceResult(result, price);
                return result;
            })
            .catch(() => null);
    }

    productName.addEventListener('input', runSmartPriceCheck);
    category.addEventListener('change', runSmartPriceCheck);
    priceWarningConsent?.addEventListener('change', function () {
        setPriceWarningAcknowledgement(this.checked);
        if (this.checked) {
            smartPriceSection.classList.remove('needs-attention');
        }
    });

    productForm?.addEventListener('submit', async function (event) {
        event.preventDefault();

        const result = await runSmartPriceCheck({ preserveAcknowledgement: true });
        const activeResult = result || latestSmartPriceCheck;

        if (activeResult?.requires_warning && !priceWarningConsent.checked) {
            priceWarningConsentRow.classList.remove('d-none');
            smartPriceSection.classList.add('needs-attention');
            smartPriceSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            window.setTimeout(() => smartPriceSection.focus({ preventScroll: true }), 350);
            return;
        }

        HTMLFormElement.prototype.submit.call(productForm);
    });

    /*
    |--------------------------------------------------------------------------
    | PREVIEW ELEMENTS
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | PRODUCT NAME
    |--------------------------------------------------------------------------
    */

    productName.addEventListener(
        'input',
        function() {

            previewName.textContent =
                this.value.trim() ||
                'Your Product Name';
        }
    );

    /*
    |--------------------------------------------------------------------------
    | CATEGORY
    |--------------------------------------------------------------------------
    */

    category.addEventListener(
        'change',
        function() {

            const selectedCategory =
                this.options[
                    this.selectedIndex
                ].text;

            previewCategory.textContent =
                selectedCategory ||
                'Fresh Marketplace';
        }
    );

    /*
    |--------------------------------------------------------------------------
    | DESCRIPTION
    |--------------------------------------------------------------------------
    */

    description.addEventListener(
        'input',
        function() {

            previewDescription.textContent =
                this.value.trim() ||
                'Product description preview will appear here.';
        }
    );

    /*
    |--------------------------------------------------------------------------
    | HARVEST DATE
    |--------------------------------------------------------------------------
    */

    harvestDate.addEventListener(
        'change',
        function() {

            if (this.value) {

                const date =
                    new Date(this.value);

                previewDate.textContent =
                    date.toLocaleDateString(
                        'en-PH', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        }
                    );

            } else {

                previewDate.textContent =
                    'No Harvest Date';
            }
        }
    );

    let selectedGalleryFiles = [];

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

    function addVariantRow(values = {}) {
        const row = document.createElement('div');
        row.className = 'product-pricing-grid';
        row.innerHTML = `
            <div class="product-pricing-field">
                <label class="form-label" for="productWeight">Package size</label>
                <div class="variant-label-group">
                    <div class="variant-meta-row">
                        <input type="number" step="0.01" min="0" id="productWeight" name="variant_weight[]" class="form-control variant-weight-input" placeholder="Amount (e.g. 250)" value="${values.weight || ''}" required>
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
                <label class="form-label" for="productRetail">Retail price</label>
                <div class="input-group">
                    <span class="input-group-text">₱</span>
                    <input type="number" step="0.01" id="productRetail" name="retail_price[]" class="form-control" value="${values.retail_price || ''}" placeholder="Retail">
                </div>
            </div>
            <div class="product-pricing-field variant-wholesale">
                <label class="form-label" for="productWholesale">Wholesale price</label>
                <div class="input-group">
                    <span class="input-group-text">₱</span>
                    <input type="number" step="0.01" id="productWholesale" name="wholesale_price[]" class="form-control" value="${values.wholesale_price || ''}" placeholder="Wholesale">
                </div>
            </div>
            <div class="product-pricing-field variant-minqty">
                <label class="form-label" for="productMinimum">Minimum wholesale quantity</label>
                <input type="number" id="productMinimum" name="minimum_wholesale_quantity[]" class="form-control" value="${values.minimum_wholesale_quantity || ''}" placeholder="Qty">
            </div>
            <div class="product-pricing-field">
                <label class="form-label" for="productStock">Available stock</label>
                <input type="number" id="productStock" name="stock[]" class="form-control" value="${values.stock || 0}" required>
            </div>
            <input type="hidden" name="variant_active[]" value="1">
        `;

        variantRows.appendChild(row);
        refreshVariantRowLabel(row);
    }


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

    document.querySelector('input[name="retail_price[]"]')?.addEventListener('input', runSmartPriceCheck);

    updateVariantVisibility();
</script>

<?php

$content = ob_get_clean();

$pageCss = '/ani-mart/public/assets/css/pages/product-editor.css?v='
    . filemtime(__DIR__ . '/../../../../public/assets/css/pages/product-editor.css');
require __DIR__ . '/../../layouts/main.php';

?>
