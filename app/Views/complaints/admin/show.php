<?php

$title = "Dispute #" . htmlspecialchars($complaint->id) . ' Review';
$bodyClass = 'admin-complaint-show-page';
$pageStyles[] = '/ani-mart/public/assets/css/pages/admin-complaint-show.css?v=' .
    filemtime(__DIR__ . '/../../../../public/assets/css/pages/admin-complaint-show.css');

ob_start();

$isFinalized = \App\Services\TrustCasePolicy::isFinal('complaint', $complaint->status);

?>

<main class="container app-page-spacing complaint-show-page">
    <?php render_component('page-hero', [
        'class' => 'admin-page-hero',
        'id' => 'dispute-review-title',
        'eyebrow' => 'Resolution Case',
        'title' => 'Dispute #' . (int) $complaint->id,
        'subtitle' => 'Review the order issue, supporting evidence, conversation, and resolution history.',
        'backHref' => app_url('admin/complaints'),
        'backLabel' => 'Complaint List',
        'stats' => [['label' => 'Status', 'value' => \App\Services\StatusPresenter::label($complaint->status)]],
    ]); ?>

    <div class="row g-4 complaint-show-layout">
        <div class="col-xl-8">
            <div class="card complaint-case-card">
                <div class="complaint-case-heading">
                    <div>
                        <h2 class="h3 mb-1">Dispute summary</h2>
                        <p class="text-muted mb-0">Order <?= htmlspecialchars($complaint->order_number ?? 'N/A') ?> · Filed by <?= htmlspecialchars($complaint->customer_first_name . ' ' . $complaint->customer_last_name) ?></p>
                    </div>
                    <span class="badge <?= e(\App\Services\StatusPresenter::badgeClass($complaint->status)) ?> rounded-pill text-capitalize complaint-status-badge">
                        <?= e(\App\Services\StatusPresenter::label($complaint->status)) ?>
                    </span>
                </div>

                <div class="complaint-section complaint-issue-section">
                    <h5 class="fw-semibold">Issue Details</h5>
                    <div class="complaint-facts">
                        <div><small>Category</small><strong><?= htmlspecialchars($complaint->category) ?></strong></div>
                        <div><small>Priority</small><strong><?= ucfirst(htmlspecialchars($complaint->priority)) ?></strong></div>
                        <div><small>Seller</small><strong><?= htmlspecialchars($complaint->shop_name ?? 'Unknown') ?></strong></div>
                    </div>
                </div>

                <div class="complaint-section">
                    <h5 class="fw-semibold">Description</h5>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($complaint->description)) ?></p>
                </div>

                <?php if (!empty($complaint->admin_notes)): ?>
                    <div class="complaint-section">
                        <h5 class="fw-semibold">Previous Admin Notes</h5>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($complaint->admin_notes)) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($evidence)): ?>
                    <div class="complaint-section">
                        <h5 class="fw-semibold">Evidence</h5>
                        <?php render_component('evidence-gallery', [
                            'items' => $evidence,
                            'allowPermanentDelete' => true,
                            'cardClass' => 'card complaint-evidence-card',
                            'imageClass' => 'complaint-evidence-image',
                        ]); ?>
                    </div>
                <?php endif; ?>

                <div class="complaint-section complaint-timeline-section">
                    <h5 class="fw-semibold">Conversation Timeline</h5>
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
                        <p class="text-muted">No conversation yet. Admin actions can be used to progress this case.</p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($actions)): ?>
                    <div class="complaint-section complaint-action-log">
                        <h5 class="fw-semibold">Action Log</h5>
                        <div class="list-group">
                            <?php foreach ($actions as $action): ?>
                                <div class="list-group-item border-0 rounded-4 mb-3 shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= htmlspecialchars(str_replace('_', ' ', ucfirst($action->action))) ?></strong>
                                            <div class="small text-muted">By <?= htmlspecialchars($action->first_name . ' ' . $action->last_name) ?> · <?= date('M d, Y H:i', strtotime($action->created_at)) ?></div>
                                        </div>
                                    </div>
                                    <?php if (!empty($action->notes)): ?>
                                        <p class="mb-0 mt-2 text-muted"><?= nl2br(htmlspecialchars($action->notes)) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-xl-4 complaint-sidebar">
            <div class="card complaint-action-card">
                <h5 class="fw-semibold mb-3">Resolution Actions</h5>
                <?php if ($isFinalized): ?>
                    <div class="complaint-finalized-notice" role="status">
                        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                        <div>
                            <strong>This complaint has already been finalized.</strong>
                            <span>Reopen it before recording another resolution action.</span>
                        </div>
                    </div>
                <?php endif; ?>
                <form action="/ani-mart/public/admin/complaints/action" method="POST">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="complaint_id" value="<?= htmlspecialchars($complaint->id) ?>">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Choose Action</label>
                        <select name="action" class="form-select modern-input" required <?= $isFinalized ? 'disabled' : '' ?>>
                            <option value="">Select an action</option>
                            <option value="approved">Approve and Resolve</option>
                            <option value="rejected">Reject Complaint</option>
                            <option value="request_more_evidence">Request More Evidence</option>
                            <option value="warn_farmer">Warn Farmer</option>
                            <option value="suspend_farmer">Suspend Farmer</option>
                            <option value="restore_trust">Restore Trust</option>
                            <option value="close">Close Case</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Admin Notes</label>
                        <textarea name="notes" class="form-control modern-input" rows="4" maxlength="1200" placeholder="Add investigation notes, instructions, or evidence requirements." <?= $isFinalized ? 'disabled' : '' ?>></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3" <?= $isFinalized ? 'disabled' : '' ?>>Record Action</button>
                </form>

                <?php if ($isFinalized): ?>
                    <div class="complaint-reopen-panel">
                        <div>
                            <strong>Need to change the decision?</strong>
                            <span>Reopening returns this case to Under Review. Previous actions remain visible.</span>
                        </div>
                        <form action="/ani-mart/public/admin/complaints/reopen" method="POST" onsubmit="return confirm('Reopen this complaint for additional review?');">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <input type="hidden" name="complaint_id" value="<?= htmlspecialchars($complaint->id) ?>">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> Reopen Complaint
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card complaint-summary-card">
                <h5 class="fw-semibold mb-3">Case Summary</h5>
                <div class="complaint-summary-list">
                <div class="complaint-summary-item">
                    <div class="text-muted small">Order Total</div>
                    <div>₱<?= number_format($complaint->total_amount ?? 0, 2) ?></div>
                </div>
                <div class="complaint-summary-item">
                    <div class="text-muted small">Seller Trust Score</div>
                    <div><?= htmlspecialchars($complaint->trust_score ?? 0) ?>%</div>
                </div>
                <div class="complaint-summary-item">
                    <div class="text-muted small">Assigned Admin</div>
                    <div><?= htmlspecialchars($complaint->admin_first_name ? $complaint->admin_first_name . ' ' . $complaint->admin_last_name : 'Unassigned') ?></div>
                </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
