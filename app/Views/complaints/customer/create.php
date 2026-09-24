<?php

$title = 'File a Dispute';
$bodyClass = 'customer-complaint-create-page';

ob_start();

?>

<div class="container app-page-spacing">
    <div class="hero mb-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="hero-badge">Trust & Resolution Center</span>
                <h1 class="hero-title mt-3">File a new dispute</h1>
                <p class="hero-subtitle mt-3">Submit a complaint, attach evidence, and help our team resolve your order issue quickly.</p>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-5 p-4">
                <h1 class="mb-4">File a New Dispute</h1>
                <form action="/ani-mart/public/customer/complaints/store" method="POST" enctype="multipart/form-data">
                    <input
    type="hidden"
    name="_token"
    value="<?= htmlspecialchars(csrf_token()) ?>">
                <div class="mb-4">
                        <label class="form-label fw-semibold">Eligible Order</label>
                        <select name="order_id" class="form-select modern-input" required>
                            <option value="">Select an order</option>
                            <?php foreach (($orders ?? []) as $order): ?>
                                <option value="<?= htmlspecialchars($order->id) ?>" <?= $order->id == ($selectedOrderId ?? 0) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($order->order_number ?? ('#' . $order->id)) ?> — <?= htmlspecialchars($order->shop_name ?? 'Seller') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Complaint Category</label>
                        <select name="category" class="form-select modern-input" required>
                            <option value="">Choose a category</option>
                            <option value="Late Delivery">Late Delivery</option>
                            <option value="Damaged Product">Damaged Product</option>
                            <option value="Missing Items">Missing Items</option>
                            <option value="Wrong Item">Wrong Item</option>
                            <option value="Unsatisfactory Quality">Unsatisfactory Quality</option>
                            <option value="Seller Behavior">Seller Behavior</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Describe the issue</label>
                        <textarea name="description" class="form-control modern-input" rows="6" maxlength="1200" required placeholder="Explain the problem, symptoms, and how it affected your order."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Priority</label>
                        <select name="priority" class="form-select modern-input">
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="low">Low</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Upload evidence photos</label>
                        <input type="file" name="evidence_images[]" class="form-control modern-input" accept="image/jpeg,image/png,image/webp" multiple>
                        <small class="text-muted">Optional. Upload up to 5 images to support your dispute.</small>
                    </div>

                    <button type="submit" class="btn btn-primary px-5 py-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Submit Dispute
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-5 p-4">
                <h3 class="mb-3">Dispute Guidelines</h3>
                <ul class="list-unstyled text-muted lh-lg">
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Only delivered or completed orders are eligible.</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Provide clear details and attach evidence when possible.</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>The resolution team will review your case and may request more information.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
