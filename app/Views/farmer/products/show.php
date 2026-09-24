<?php

$title = $product->name;

$description = trim((string) ($product->description ?? ''));
$description = preg_replace('/\s*\*\s*/u', "\n• ", $description);
$description = preg_replace('/\s*(Product Highlights:)/iu', "\n\n$1\n", $description);
$description = preg_replace('/\s*(—\s*)/u', "\n\n$1", $description);

ob_start();

?>

<div class="container app-page-spacing">

    <div class="row g-5 align-items-center">

        <div class="col-lg-6">

            <img
                src="<?= $product->image ?>"
                class="img-fluid rounded-5 shadow-sm"
                alt="<?= htmlspecialchars($product->name) ?>"
                data-image-modal="true"
            >

        </div>

        <div class="col-lg-6">

            <span class="preview-category">

                <?= $product->category_name ?>

            </span>

            <h1 class="fw-bold mt-3 mb-3">

                <?= $product->name ?>

            </h1>

            <div class="preview-price mb-4">

                ₱<?= number_format($product->price, 2) ?>

            </div>

            <div class="product-preview-description">

                <?= nl2br(htmlspecialchars($description)) ?>

            </div>

            <div class="mt-4">

                <span class="stock-badge">

                    Stock:
                    <?= $product->stock ?>

                </span>

            </div>

        </div>

    </div>

</div>

<?php

$content = ob_get_clean();

require __DIR__ . '/../../layouts/main.php';

?>
