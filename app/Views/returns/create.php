<?php
$title = 'Request a Return';
$order = $order ?? (object) [];
$items = $items ?? [];
ob_start();
?>

<div class="container app-page-spacing">
    <div class="dashboard-hero mb-4">
        <div class="col-lg-8">
            <span class="hero-badge">Returns & Refunds</span>
            <h1 class="hero-title mt-3">Request a product return</h1>
            <p class="hero-subtitle">Choose what you are returning, explain why, and attach clear evidence. Refunds are recorded even for cash-on-delivery orders.</p>
        </div>
    </div>

    <form action="/ani-mart/public/customer/returns/store" method="POST" enctype="multipart/form-data" class="row g-4">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="order_id" value="<?= (int) $order->id ?>">

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-muted small">Order</div>
                        <h4 class="mb-0"><?= htmlspecialchars($order->order_number ?? ('#' . $order->id)) ?></h4>
                    </div>
                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($order->shop_name ?? 'Seller') ?></span>
                </div>

                <h5 class="mb-3">Products to return</h5>
                <?php foreach ($items as $item): ?>
                    <?php $unitPrice = (float) ($item->unit_price ?? $item->price); ?>
                    <?php $receivedQty = max(0, (int) ($item->quantity ?? 0)); ?>
                    <div class="d-flex gap-3 align-items-center border rounded-3 p-3 mb-3">
                        <img src="<?= htmlspecialchars($item->image ?? '/ani-mart/public/assets/img/product-placeholder.svg') ?>" alt="" width="64" height="64" class="rounded-3 object-fit-cover">
                        <div class="flex-grow-1">
                            <div class="fw-semibold"><?= htmlspecialchars($item->product_name) ?></div>
                            <?php if (!empty($item->variant_label)): ?><div class="small text-muted"><?= htmlspecialchars($item->variant_label) ?></div><?php endif; ?>
                            <div class="small text-muted">₱<?= number_format($unitPrice, 2) ?> each · <?= $receivedQty ?> received</div>
                        </div>
                        <div style="width: 110px">
                            <label class="form-label small">Return qty</label>
                            <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control return-quantity plain-number-input" name="quantities[<?= (int) $item->id ?>]" min="0" max="<?= $receivedQty ?>" value="<?= $receivedQty ?>" data-price="<?= htmlspecialchars((string) $unitPrice) ?>" readonly>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="mb-4">
                    <label class="form-label fw-semibold">Reason for return</label>
                    <textarea name="reason" class="form-control" rows="5" minlength="10" maxlength="1200" required placeholder="Describe the issue and the condition of the products."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Evidence photos</label>
                    <input type="file" name="evidence_images[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple required>
                    <div class="form-text">Attach up to 5 JPG, PNG, or WebP images, 5 MB each.</div>
                </div>
                <div>
                    <label class="form-label fw-semibold">Evidence caption <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" name="evidence_caption" class="form-control" maxlength="500" placeholder="What the photos show">
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 position-sticky" style="top: 100px">
                <div class="text-muted">Requested refund</div>
                <div class="display-6 fw-bold text-success" id="requestedRefund">₱0.00</div>
                <p class="small text-muted mt-2">Calculated from the purchased price and selected quantities. The seller records the approved and actually refunded amounts separately.</p>
                <button type="submit" class="btn btn-success btn-lg w-100 mt-3"><i class="bi bi-arrow-return-left me-2"></i>Submit Return Request</button>
                <a href="/ani-mart/public/orders" class="btn btn-link text-muted mt-2">Back to orders</a>
            </div>
        </div>
    </form>
</div>

<style>
.plain-number-input {
    appearance: textfield;
    -moz-appearance: textfield;
}

.plain-number-input::-webkit-outer-spin-button,
.plain-number-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const output = document.getElementById('requestedRefund');
    const update = () => {
        let total = 0;
        document.querySelectorAll('.return-quantity').forEach(input => {
            total += (Number(input.value) || 0) * (Number(input.dataset.price) || 0);
        });
        output.textContent = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(total);
    };
    document.querySelectorAll('.return-quantity').forEach(input => {
        input.addEventListener('input', update);
        input.setAttribute('aria-readonly', 'true');
    });
    update();
});
</script>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
