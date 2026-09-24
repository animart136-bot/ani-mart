<?php

$title = "Dispute #" . htmlspecialchars($complaint->id);
$bodyClass = 'customer-complaint-show-page';
$pageStyles[] = '/ani-mart/public/assets/css/pages/customer-complaints.css?v=' .
    filemtime(__DIR__ . '/../../../../public/assets/css/pages/customer-complaints.css');

ob_start();

?>

<main class="container app-page-spacing customer-complaint-detail-shell">
    <section class="dashboard-hero mb-5" aria-labelledby="customer-dispute-title">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="hero-badge">Trust & Resolution Center</span>
                <h1 class="hero-title mt-4" id="customer-dispute-title">Dispute #<?= (int) $complaint->id ?></h1>
                <p class="hero-subtitle mt-3">Order <?= htmlspecialchars($complaint->order_number ?? 'N/A') ?> · Filed <?= date('F j, Y', strtotime($complaint->created_at ?? 'now')) ?></p>
                <div class="customer-complaint-hero-actions">
                    <a href="/ani-mart/public/customer/complaints" class="hero-back-link">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i> My disputes
                    </a>
                    <span class="complaint-hero-status complaint-status-<?= htmlspecialchars(strtolower((string) $complaint->status)) ?>">
                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $complaint->status))) ?>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4 customer-complaint-detail-grid">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-5 p-4 mb-4 customer-complaint-detail-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h1 class="h3 mb-1">Dispute #<?= htmlspecialchars($complaint->id) ?></h1>
                        <p class="text-muted mb-0">Order <?= htmlspecialchars($complaint->order_number ?? 'N/A') ?> · Filed on <?= date('F j, Y', strtotime($complaint->created_at ?? 'now')) ?></p>
                    </div>
                    <span class="badge <?= e(\App\Services\StatusPresenter::badgeClass($complaint->status)) ?> rounded-pill text-capitalize">
                        <?= e(\App\Services\StatusPresenter::label($complaint->status)) ?>
                    </span>
                </div>

                <div class="mb-4">
                    <h5 class="fw-semibold">Issue Summary</h5>
                    <p class="mb-1"><strong>Category:</strong> <?= htmlspecialchars($complaint->category) ?></p>
                    <p class="mb-0"><strong>Priority:</strong> <?= ucfirst(htmlspecialchars($complaint->priority)) ?></p>
                </div>

                <div class="mb-4">
                    <h5 class="fw-semibold">Description</h5>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($complaint->description)) ?></p>
                </div>

                <?php if (!empty($complaint->admin_notes)): ?>
                    <div class="mb-4">
                        <h5 class="fw-semibold">Investigation Notes</h5>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($complaint->admin_notes)) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($evidence)): ?>
                    <div class="mb-4">
                        <h5 class="fw-semibold">Evidence</h5>
                        <div class="row g-3">
                            <?php foreach ($evidence as $item): ?>
                                <div class="col-sm-6 col-xl-4">
                                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                                        <img src="<?= htmlspecialchars($item->image_url) ?>" class="img-fluid" alt="Evidence" data-image-modal="true">
                                        <div class="card-body p-3 small text-muted">Uploaded <?= date('M d, Y', strtotime($item->created_at)) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mb-4">
                    <h5 class="fw-semibold">Conversation</h5>
                    <?php if (!empty($replies)): ?>
                        <div class="list-group">
                            <?php foreach ($replies as $reply): ?>
                                <div class="list-group-item border-0 rounded-4 mb-3 shadow-sm">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?= htmlspecialchars($reply->first_name . ' ' . $reply->last_name) ?></strong>
                                            <div class="small text-muted"><?= htmlspecialchars($reply->role) ?> · <?= date('M d, Y H:i', strtotime($reply->created_at)) ?></div>
                                        </div>
                                    </div>
                                    <p class="mb-0 mt-2 text-muted"><?= nl2br(htmlspecialchars($reply->message)) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No conversation updates yet. The resolution team will notify you once there is progress.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-5 p-4 customer-complaint-detail-card">
                <h5 class="mb-3">Add a Message</h5>
                <form action="/ani-mart/public/complaints/replies/store" method="POST">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="complaint_id" value="<?= htmlspecialchars($complaint->id) ?>">
                    <div class="mb-3">
                        <textarea name="message" class="form-control modern-input" rows="4" maxlength="1000" placeholder="Leave an update for the farmer or resolution team." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-send me-2"></i>
                        Post Message
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-4 customer-complaint-sidebar">
            <div class="card border-0 shadow-sm rounded-5 p-4 mb-4 customer-complaint-detail-card">
                <h5 class="fw-semibold mb-3">Case Summary</h5>
                <div class="mb-3">
                    <div class="text-muted small">Seller</div>
                    <div><?= htmlspecialchars($complaint->shop_name ?? 'Unknown Seller') ?></div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Order Total</div>
                    <div>₱<?= number_format($complaint->total_amount ?? 0, 2) ?></div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Current Status</div>
                    <div class="fw-semibold text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $complaint->status)) ?></div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Priority</div>
                    <div class="fw-semibold"><?= ucfirst(htmlspecialchars($complaint->priority)) ?></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-5 p-4 customer-complaint-detail-card">
                <h5 class="fw-semibold mb-3">Next Steps</h5>
                <ul class="list-unstyled text-muted lh-lg">
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>A resolution officer will review your dispute.</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Attach more evidence if requested via the conversation.</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>We will notify you when the dispute is resolved.</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
