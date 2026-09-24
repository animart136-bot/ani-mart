<?php
$title = 'Seller Applications';
$applications = is_countable($applications ?? []) ? $applications : [];
$historyByApplication = $historyByApplication ?? [];
ob_start();
?>
<div class="container app-page-spacing">
    <div class="admin-hero mb-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="hero-badge">Application Verification</span>
                <h1 class="hero-title mt-4">Seller Applications</h1>
                <p class="hero-subtitle mt-3">Approve qualified sellers.</p>
                <a href="/ani-mart/public/dashboard" class="admin-dashboard-back-link"><i class="bi bi-arrow-left" aria-hidden="true"></i> Admin dashboard</a>
            </div>
        </div>
    </div>
    <div class="applications-panel">
        <div class="applications-header">
            <div><span class="section-badge">Verification Center</span>
                <h2 class="section-title">Application review queue</h2>
            </div>
            <div class="applications-count"><span><?= count($applications) ?></span> Applications</div>
        </div>
        <div class="table-responsive">
            <table class="table applications-table align-middle">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Application Status</th>
                        <th>Farmer Documentation</th>
                        <th>Submission</th>
                        <th>Status</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody><?php foreach ($applications as $app):
                    $applicationStatus = strtolower((string) ($app->application_status ?? $app->status ?? 'pending'));
                    $recordStatus = strtolower((string) ($app->status ?? 'pending'));
                    $applicationStatusClass = match ($applicationStatus) {
                        'approved' => 'bg-success-subtle text-success',
                        'rejected' => 'bg-danger-subtle text-danger',
                        'needs_correction', 'incomplete' => 'bg-warning-subtle text-dark',
                        'pending_official_verification' => 'bg-primary-subtle text-primary',
                        default => 'bg-info-subtle text-dark',
                    };
                    $recordStatusClass = match ($recordStatus) {
                        'approved' => 'bg-success-subtle text-success',
                        'rejected' => 'bg-danger-subtle text-danger',
                        'needs_correction', 'incomplete' => 'bg-warning-subtle text-dark',
                        'pending_official_verification' => 'bg-primary-subtle text-primary',
                        default => 'bg-secondary-subtle text-dark',
                    };
                    ?>
                        <tr>
                            <td data-label="Applicant">
                                <div class="fw-bold"><?= htmlspecialchars(trim(($app->first_name ?? '') . ' ' . ($app->last_name ?? '')) ?: 'Unknown applicant') ?></div><small class="text-muted"><?= htmlspecialchars($app->farm_name ?? 'Unnamed farm') ?></small>
                            </td>
                            <td data-label="Application Status"><span class="badge <?= $applicationStatusClass ?> rounded-pill px-3 py-2"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $applicationStatus))) ?></span></td>
                            <td data-label="Farmer Documentation"><span class="badge bg-secondary-subtle text-dark rounded-pill px-3 py-2"><?= !empty($app->rsbsa_card_path) || !empty($app->rsbsa_number) ? 'Submitted' : 'Not submitted' ?></span></td>
                            <td data-label="Submission" class="small text-muted"><?= htmlspecialchars($app->submitted_at ?? 'Not available') ?></td>
                            <td data-label="Status"><span class="badge <?= $recordStatusClass ?> rounded-pill px-3 py-2"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $recordStatus))) ?></span></td>
                            <td data-label="Actions">
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-outline-dark btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#applicationModal<?= (int) $app->id ?>"><i class="bi bi-search me-1"></i> Details</button>
                                </div>
                            </td>
                        </tr>
                        <div class="modal fade" id="applicationModal<?= (int) $app->id ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content border-0 rounded-4">
                                    <div class="modal-header border-0">
                                        <div>
                                            <h5 class="fw-bold mb-1">Application verification</h5>
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <small class="text-muted"><?= htmlspecialchars($app->farm_name ?? '') ?></small>
                                                <span class="badge <?= $recordStatusClass ?> rounded-pill"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $recordStatus))) ?></span>
                                            </div>
                                        </div><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-4">
                                            <div class="col-md-5"><?php if (!empty($app->rsbsa_card_path)): ?><img src="<?= htmlspecialchars($app->rsbsa_card_path) ?>" class="img-fluid rounded-3 border" alt="Uploaded RSBSA card"><?php else: ?><div class="alert alert-secondary mb-0">Regular seller application — no RSBSA card submitted.</div><?php endif; ?></div>
                                            <div class="col-md-7">
                                                <dl class="row small mb-0">
                                                    <dt class="col-5">Applicant</dt>
                                                    <dd class="col-7"><?= htmlspecialchars(trim(($app->first_name ?? '') . ' ' . ($app->middle_name ?? '') . ' ' . ($app->last_name ?? '') . ' ' . ($app->name_extension ?? ''))) ?></dd>
                                                    <dt class="col-5">Date of birth</dt>
                                                    <dd class="col-7"><?= htmlspecialchars($app->date_of_birth ?? 'Not provided') ?></dd>
                                                    <dt class="col-5">Sex</dt>
                                                    <dd class="col-7"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $app->sex ?? 'Not provided'))) ?></dd>
                                                    <dt class="col-5">RSBSA Number</dt>
                                                    <dd class="col-7"><?= htmlspecialchars($app->rsbsa_number ?? 'Not provided') ?></dd>
                                                    <?php if (!empty($app->extracted_name)): ?>
                                                        <dt class="col-5">Card name</dt>
                                                        <dd class="col-7"><?= htmlspecialchars($app->extracted_name) ?></dd>
                                                    <?php endif; ?>
                                                </dl>
                                            </div>
                                            <?php if (!empty($app->rsbsa_card_path) || !empty($app->rsbsa_number)): ?>
                                                <?php
                                                $nameReviewStatus = (string) ($app->ai_review_status ?? 'submitted');
                                                $nameReviewClass = match ($nameReviewStatus) {
                                                    'reference_match' => 'alert-success',
                                                    'information_mismatch' => 'alert-danger',
                                                    'name_review_required', 'name_not_readable' => 'alert-warning',
                                                    default => 'alert-info',
                                                };
                                                ?>
                                                <div class="col-12">
                                                    <div class="alert <?= $nameReviewClass ?> mb-0 small">
                                                        <strong>RSBSA name check:</strong>
                                                        <?= htmlspecialchars($app->ai_review_summary ?? 'Run the card name check before granting RSBSA recognition.') ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (($app->status ?? '') !== 'approved' && ($app->status ?? '') !== 'rejected'): ?>
                                                <div class="col-12 border-top pt-3">
                                                    <h6 class="fw-bold">Seller application decision</h6>
                                                    <p class="small text-muted">Approval grants seller access. Submitted RSBSA documentation may support the Local Farmer badge, but it is not treated as official government verification.</p>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <form method="POST" action="/ani-mart/public/admin/applications/approve">
                                                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                                            <input type="hidden" name="id" value="<?= (int) $app->id ?>">
                                                            <button class="btn btn-success btn-sm">Approve as Seller</button>
                                                        </form>
                                                        <form method="POST" action="/ani-mart/public/admin/applications/reject">
                                                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                                            <input type="hidden" name="id" value="<?= (int) $app->id ?>">
                                                            <button class="btn btn-outline-danger btn-sm">Reject Application</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($app->rsbsa_card_path) || !empty($app->rsbsa_number)): ?>
                                                <div class="col-12 border-top pt-3">
                                                    <h6 class="fw-bold">Optional Local Farmer documentation</h6>
                                                    <p class="small text-muted">The uploaded RSBSA document and number are retained as application evidence. A matching first and last name earns RSBSA recognition; a middle-name difference requires review, while a first- or last-name mismatch does not earn the badge.</p>
                                                    <?php if (!empty($app->rsbsa_card_path)): ?>
                                                        <form method="POST" action="/ani-mart/public/admin/applications/reverify">
                                                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                                            <input type="hidden" name="id" value="<?= (int) $app->id ?>">
                                                            <button class="btn btn-outline-primary btn-sm">Check RSBSA Card Name</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="col-12">
                                                <h6 class="fw-bold">Verification history</h6><?php $history = $historyByApplication[$app->id] ?? []; ?><?php if (!$history): ?><p class="small text-muted mb-0">No verification history recorded.</p><?php else: ?><div class="small text-muted"><?php foreach ($history as $entry): ?><div class="border-bottom py-2"><strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $entry->verification_type ?? 'review'))) ?></strong>: <?= htmlspecialchars(ucwords(str_replace('_', ' ', $entry->verification_status ?? 'pending'))) ?><?php if (!empty($entry->notes)): ?>, <?= htmlspecialchars($entry->notes) ?><?php endif; ?> <span>(<?= htmlspecialchars($entry->verified_at ?? '') ?>)</span></div><?php endforeach; ?></div><?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php'; ?>
