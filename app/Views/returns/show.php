<?php
$title = 'Return & Refund Record';
$statusColor = \App\Services\StatusPresenter::badgeClass($return->status);
ob_start();
?>

<div class="container app-page-spacing">
    <div class="dashboard-hero mb-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="hero-badge">Returns & Refunds</span>
                <h1 class="hero-title mt-4">Return #<?= (int) $return->id ?></h1>
                <p class="hero-subtitle mt-3">Order <?= htmlspecialchars($return->order_number) ?> · <?= htmlspecialchars($return->shop_name) ?> · Status: <?= htmlspecialchars(ucfirst($return->status)) ?></p>
                <a href="<?= $role === 'farmer' ? '/ani-mart/public/farmer/orders' : '/ani-mart/public/orders' ?>" class="hero-back-link">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to orders
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h4 class="mb-3">Returned products</h4>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Product</th><th>Unit price</th><th>Requested</th><th>Approved</th><th>Received</th><th>Refunded</th></tr></thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($item->product_name) ?></strong><?php if ($item->variant_label): ?><div class="small text-muted"><?= htmlspecialchars($item->variant_label) ?></div><?php endif; ?></td>
                                <td>₱<?= number_format((float) $item->unit_price, 2) ?></td>
                                <td><?= (int) $item->requested_quantity ?></td>
                                <td><?= (int) $item->approved_quantity ?></td>
                                <td><?= (int) $item->received_quantity ?></td>
                                <td>₱<?= number_format((float) $item->refund_amount, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h4 class="mb-3">Reason and decision</h4>
                <div class="mb-3"><div class="small text-muted">Customer reason</div><div><?= nl2br(htmlspecialchars($return->reason)) ?></div></div>
                <?php if ($return->decision_reason): ?><div class="mb-3"><div class="small text-muted">Seller decision reason</div><div><?= nl2br(htmlspecialchars($return->decision_reason)) ?></div></div><?php endif; ?>
                <?php if ($return->resolution_note): ?><div><div class="small text-muted">Final resolution</div><div><?= nl2br(htmlspecialchars($return->resolution_note)) ?></div></div><?php endif; ?>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h4 class="mb-3">Evidence</h4>
                <?php if ($evidence): ?>
                    <div class="row g-3">
                    <?php foreach ($evidence as $proof): ?>
                        <div class="col-sm-6 col-xl-4">
                            <a href="<?= htmlspecialchars($proof->image_url) ?>" target="_blank" rel="noopener"><img src="<?= htmlspecialchars($proof->image_url) ?>" alt="Return evidence" class="img-fluid rounded-3 border" style="aspect-ratio: 4/3; object-fit: cover" data-image-modal="true"></a>
                            <div class="small text-muted mt-1"><?= htmlspecialchars(ucfirst($proof->uploader_role)) ?> · <?= htmlspecialchars($proof->caption ?: 'Evidence') ?></div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php else: ?><p class="text-muted mb-0">No evidence has been attached.</p><?php endif; ?>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h4 class="mb-3">Resolution history</h4>
                <?php foreach ($history as $event): ?>
                    <div class="d-flex gap-3 pb-3 mb-3 border-bottom">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px;height:40px"><i class="bi bi-clock-history"></i></div>
                        <div>
                            <div class="fw-semibold"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $event->event_type))) ?></div>
                            <div><?= nl2br(htmlspecialchars($event->note)) ?></div>
                            <?php if ($event->amount !== null): ?><div class="small text-success">Amount recorded: ₱<?= number_format((float) $event->amount, 2) ?></div><?php endif; ?>
                            <div class="small text-muted"><?= htmlspecialchars(ucfirst($event->actor_role)) ?> · <?= date('M j, Y g:i A', strtotime($event->created_at)) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h4>Refund record</h4>
                <dl class="row mb-0">
                    <dt class="col-7 text-muted fw-normal">Order payment</dt><dd class="col-5 text-end"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $return->order_payment_method))) ?></dd>
                    <dt class="col-7 text-muted fw-normal">Requested</dt><dd class="col-5 text-end">₱<?= number_format((float) $return->requested_refund_amount, 2) ?></dd>
                    <dt class="col-7 text-muted fw-normal">Approved</dt><dd class="col-5 text-end"><?= $return->approved_refund_amount === null ? '—' : '₱' . number_format((float) $return->approved_refund_amount, 2) ?></dd>
                    <dt class="col-7 text-muted fw-normal">Actually refunded</dt><dd class="col-5 text-end fw-bold text-success">₱<?= number_format((float) $return->refunded_amount, 2) ?></dd>
                    <dt class="col-7 text-muted fw-normal">Refund method</dt><dd class="col-5 text-end"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $return->refund_method))) ?></dd>
                    <?php if ($return->refund_reference): ?><dt class="col-7 text-muted fw-normal">Reference</dt><dd class="col-5 text-end text-break"><?= htmlspecialchars($return->refund_reference) ?></dd><?php endif; ?>
                </dl>
            </div>

            <?php if ($role === 'farmer' && $return->status === 'pending'): ?>
                <form action="/ani-mart/public/farmer/returns/decision" method="POST" enctype="multipart/form-data" class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="return_id" value="<?= (int) $return->id ?>">
                    <h4>Seller decision</h4>
                    <label class="form-label">Approved refund amount</label><input type="number" name="approved_refund_amount" class="form-control mb-3" min="0" max="<?= htmlspecialchars((string) $return->requested_refund_amount) ?>" step="0.01" value="<?= htmlspecialchars((string) $return->requested_refund_amount) ?>">
                    <label class="form-label">Decision reason</label><textarea name="decision_reason" class="form-control mb-3" rows="4" minlength="5" maxlength="1000" required></textarea>
                    <label class="form-label">Decision evidence <span class="text-muted">(optional)</span></label><input type="file" name="evidence_images[]" class="form-control mb-3" accept="image/jpeg,image/png,image/webp" multiple>
                    <div class="d-grid gap-2"><button name="decision" value="approved" class="btn btn-success">Approve Return</button><button name="decision" value="rejected" class="btn btn-outline-danger">Reject Return</button></div>
                </form>
            <?php elseif ($role === 'farmer' && $return->status === 'approved'): ?>
                <form action="/ani-mart/public/farmer/returns/resolve" method="POST" enctype="multipart/form-data" class="card border-0 shadow-sm rounded-4 p-4">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="return_id" value="<?= (int) $return->id ?>">
                    <h4>Record resolution</h4>
                    <p class="small text-muted">Record the products actually received and the refund actually issued.</p>
                    <?php foreach ($items as $item): ?>
                        <label class="form-label small"><?= htmlspecialchars($item->product_name) ?> received (max <?= (int) $item->approved_quantity ?>)</label>
                        <input type="number" name="received_quantities[<?= (int) $item->id ?>]" class="form-control mb-3" min="0" max="<?= (int) $item->approved_quantity ?>" value="<?= (int) $item->approved_quantity ?>">
                    <?php endforeach; ?>
                    <label class="form-label">Amount refunded</label><input type="number" name="refunded_amount" class="form-control mb-3" min="0" max="<?= htmlspecialchars((string) $return->approved_refund_amount) ?>" step="0.01" value="<?= htmlspecialchars((string) $return->approved_refund_amount) ?>" required>
                    <label class="form-label">Refund method</label><select name="refund_method" class="form-select mb-3" required><option value="cash">Cash (including COD)</option><option value="bank_transfer">Bank transfer</option><option value="e_wallet">E-wallet</option><option value="other">Other</option><option value="none">No refund issued</option></select>
                    <label class="form-label">Reference <span class="text-muted">(optional)</span></label><input type="text" name="refund_reference" class="form-control mb-3" maxlength="255">
                    <label class="form-label">Receipt/evidence <span class="text-muted">(optional)</span></label><input type="file" name="evidence_images[]" class="form-control mb-3" accept="image/jpeg,image/png,image/webp" multiple>
                    <button class="btn btn-success w-100">Complete Return Record</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
