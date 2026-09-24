<?php

$title = $title ?? 'Report Details';
$report = $report ?? (object) [];
$evidence = $evidence ?? [];
$actions = $actions ?? [];
$appeals = $appeals ?? [];
$bodyClass = $bodyClass ?? 'trust-safety-report-show';
$isBug = ($report->target_type ?? '') === 'bug';
$isFeatureRequest = ($report->target_type ?? '') === 'feature';
$caseLabel = $isBug ? 'Bug Report' : ($isFeatureRequest ? 'Feature Request' : 'Report');
$caseIcon = $isBug ? 'bi-bug' : ($isFeatureRequest ? 'bi-lightbulb' : 'bi-flag');
$detailsTitle = $isBug ? 'Bug Details' : ($isFeatureRequest ? 'Feature Request Details' : 'Report Details');
$targetName = ucfirst(str_replace('_', ' ', (string) ($report->target_type ?? 'unknown')));
$evidenceCount = is_array($evidence) ? count($evidence) : 0;
$lastAction = !empty($actions) ? $actions[0] : null;
$reporterName = trim(($report->reporter_first_name ?? '') . ' ' . ($report->reporter_last_name ?? '')) ?: 'User #' . (int) ($report->reporter_id ?? 0);
$assignedAdminName = trim(($report->assigned_admin_first_name ?? '') . ' ' . ($report->assigned_admin_last_name ?? '')) ?: 'Unassigned';
$resolverName = trim(($report->resolver_first_name ?? '') . ' ' . ($report->resolver_last_name ?? '')) ?: 'Not resolved';
$isFinalized = \App\Services\TrustCasePolicy::isFinal('report', $report->status ?? '');
$submittedAt = !empty($report->created_at) ? new DateTimeImmutable($report->created_at) : null;
$caseEndAt = !empty($report->resolved_at) ? new DateTimeImmutable($report->resolved_at) : new DateTimeImmutable();
$caseAgeInterval = $submittedAt ? $submittedAt->diff($caseEndAt) : null;
$caseAge = $caseAgeInterval
    ? ($caseAgeInterval->days > 0 ? $caseAgeInterval->days . ' day' . ($caseAgeInterval->days === 1 ? '' : 's') : max(1, $caseAgeInterval->h) . ' hour' . ($caseAgeInterval->h === 1 ? '' : 's'))
    : 'Unknown';
$targetRoute = match ($report->target_type ?? '') {
    'product' => !empty($report->target_id) ? '/ani-mart/public/admin/products/view?id=' . (int) $report->target_id : null,
    'seller' => '/ani-mart/public/admin/farmers',
    'community_post' => !empty($report->target_id) ? '/ani-mart/public/community/show?id=' . (int) $report->target_id : null,
    default => null,
};
$statusRank = match ($report->status ?? 'submitted') {
    'submitted', 'pending_review' => 1,
    'assigned' => 2,
    'investigation', 'need_more_info' => 3,
    'resolved', 'rejected' => 4,
    default => 1,
};
$rawDescription = (string) ($report->description ?? '');
$targetReference = '';
$reportNarrative = $rawDescription;
if (preg_match('/^Reported item:\s*(.+?)(?:\R\R|\R|$)(.*)$/s', $rawDescription, $descriptionParts)) {
    $targetReference = trim($descriptionParts[1]);
    $reportNarrative = trim($descriptionParts[2]);
}

function statusText($status)
{
    return e(\App\Services\StatusPresenter::label($status));
}

$pageStyles[] = '/ani-mart/public/assets/css/pages/report-details.css?v=' . filemtime(__DIR__ . '/../../../../public/assets/css/pages/report-details.css');

ob_start();
?>

<main class="container app-page-spacing report-detail-page">
    <?php render_component('page-hero', [
        'class' => 'admin-page-hero',
        'id' => 'report-review-title',
        'eyebrow' => $caseLabel,
        'title' => $caseLabel . ' #' . (int) $report->id,
        'subtitle' => 'Review the reported issue, supporting evidence, moderation actions, and resolution status in one place.',
        'backHref' => app_url('admin/trust-center/reports'),
        'backLabel' => 'Reports',
        'stats' => [['label' => 'Status', 'value' => \App\Services\StatusPresenter::label($report->status ?? 'submitted')]],
    ]); ?>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="report-stat-card">
                <span class="report-stat-label">Status</span>
                <strong class="report-stat-value text-capitalize"><?= statusText($report->status ?? 'submitted') ?></strong>
                <span class="report-stat-tag <?= e(\App\Services\StatusPresenter::badgeClass($report->status ?? 'submitted')) ?>"><?= statusText($report->status ?? 'submitted') ?></span>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="report-stat-card">
                <span class="report-stat-label">Priority</span>
                <strong class="report-stat-value text-capitalize"><?= htmlspecialchars($report->priority ?? 'medium') ?></strong>
                <span class="report-stat-tag bg-<?= ($report->priority ?? 'medium') === 'critical' ? 'danger' : (($report->priority ?? 'medium') === 'high' ? 'warning text-dark' : 'secondary') ?>"><?= htmlspecialchars($report->priority ?? 'medium') ?></span>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="report-stat-card">
                <span class="report-stat-label">Target</span>
                <strong class="report-stat-value"><?= htmlspecialchars($targetName) ?></strong>
                <span class="report-stat-tag bg-light text-dark"><?= !empty($report->target_id) ? 'ID #' . (int) $report->target_id : 'Manual reference' ?></span>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="report-stat-card">
                <span class="report-stat-label">Evidence</span>
                <strong class="report-stat-value"><?= (int) $evidenceCount ?></strong>
                <span class="report-stat-tag bg-info-subtle text-info-emphasis">
                    <?= $evidenceCount === 1 ? 'Item attached' : 'Items attached' ?>
                </span>
            </div>
        </div>
    </div>

    <section class="card report-progress-card" aria-labelledby="case-progress-title">
        <div class="report-section-heading">
            <div>
                <span>Case workflow</span>
                <h2 id="case-progress-title">Investigation progress</h2>
            </div>
            <div class="report-age-chip"><i class="bi bi-clock" aria-hidden="true"></i> <?= htmlspecialchars($caseAge) ?> <?= $isFinalized ? 'to resolution' : 'open' ?></div>
        </div>
        <div class="report-progress-steps">
            <?php foreach ([1 => ['Submitted', 'Report received'], 2 => ['Assigned', 'Administrator owner'], 3 => ['Investigation', 'Evidence and context review'], 4 => [$isFinalized ? ucfirst($report->status) : 'Decision', $isFinalized ? 'Case finalized' : 'Pending outcome']] as $rank => $step): ?>
                <div class="report-progress-step <?= $statusRank > $rank ? 'is-complete' : ($statusRank === $rank ? 'is-current' : '') ?>">
                    <span><?= $statusRank > $rank ? '<i class="bi bi-check-lg" aria-hidden="true"></i>' : $rank ?></span>
                    <div><strong><?= htmlspecialchars($step[0]) ?></strong><small><?= htmlspecialchars($step[1]) ?></small></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="row g-4 align-items-start report-detail-layout">
        <div class="col-xl-8">
            <div class="card report-detail-card report-case-summary-card">
                <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi <?= $caseIcon ?> fs-4 <?= $isBug ? 'text-danger' : ($isFeatureRequest ? 'text-warning' : 'text-primary') ?>"></i>
                            <span class="badge bg-<?= $isBug ? 'danger' : ($isFeatureRequest ? 'warning text-dark' : 'primary') ?>"><?= htmlspecialchars($caseLabel) ?></span>
                        </div>
                        <h2 class="h4 fw-bold mb-1">Case summary</h2>
                    </div>
                    <div class="text-end">
                        <span class="badge <?= e(\App\Services\StatusPresenter::badgeClass($report->status ?? 'submitted')) ?> text-capitalize"><?= statusText($report->status ?? 'submitted') ?></span>
                        <div class="text-muted small mt-2">Submitted <?= date('M j, Y g:i A', strtotime($report->created_at)) ?></div>
                    </div>
                </div>

                <div class="case-summary-grid">
                    <div>
                        <div class="text-muted small">Report reason</div>
                        <div class="fw-semibold"><?= htmlspecialchars($report->reason ?? 'No reason provided') ?></div>
                    </div>
                    <div>
                        <div class="text-muted small">Reporter</div>
                        <div class="fw-semibold"><?= htmlspecialchars($reporterName) ?></div>
                        <small class="text-muted text-capitalize"><?= htmlspecialchars($report->reporter_role ?? 'user') ?></small>
                    </div>
                    <div>
                        <div class="text-muted small">Assigned admin</div>
                        <div class="fw-semibold"><?= htmlspecialchars($assignedAdminName) ?></div>
                    </div>
                    <div>
                        <div class="text-muted small">Last updated</div>
                        <div class="fw-semibold"><?= !empty($report->updated_at) ? date('M j, Y g:i A', strtotime($report->updated_at)) : 'Not available' ?></div>
                    </div>
                </div>
            </div>

            <div class="card report-detail-card">
                <div class="report-section-heading report-section-heading-compact">
                    <div>
                        <span>People and subject</span>
                        <h2>Reporter & target context</h2>
                    </div>
                    <?php if ($targetRoute): ?>
                        <a href="<?= htmlspecialchars($targetRoute) ?>" class="btn btn-sm btn-outline-success">Open target <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i></a>
                    <?php endif; ?>
                </div>
                <div class="report-context-grid">
                    <div class="report-context-item">
                        <span class="report-context-icon"><i class="bi bi-person" aria-hidden="true"></i></span>
                        <div>
                            <small>Submitted by</small>
                            <strong><?= htmlspecialchars($reporterName) ?></strong>
                            <p><?= htmlspecialchars($report->reporter_email ?? 'No email available') ?> · <?= htmlspecialchars(ucfirst($report->reporter_role ?? 'user')) ?></p>
                        </div>
                    </div>
                    <div class="report-context-item">
                        <span class="report-context-icon"><i class="bi <?= $caseIcon ?>" aria-hidden="true"></i></span>
                        <div>
                            <small>Reported target</small>
                            <strong><?= htmlspecialchars($report->target_label ?? ($targetReference ?: $targetName)) ?></strong>
                            <p><?= htmlspecialchars($targetName) ?><?= !empty($report->target_id) ? ' · Record #' . (int) $report->target_id : ' · Manually identified' ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card report-detail-card">
                <h5 class="fw-semibold mb-3"><?= htmlspecialchars($detailsTitle) ?></h5>
                <?php if ($isBug): ?>
                    <p class="text-muted mb-3">Review the affected page or feature, the reported behavior, and any screenshots before reproducing the issue.</p>
                <?php elseif ($isFeatureRequest): ?>
                    <p class="text-muted mb-3">Review the requested improvement, the user need it addresses, and its potential benefit to Ani-Mart.</p>
                <?php endif; ?>
                <?php if ($targetReference !== ''): ?>
                    <div class="report-target-reference">
                        <span>Reported item or location</span>
                        <strong><?= htmlspecialchars($targetReference) ?></strong>
                    </div>
                <?php endif; ?>
                <div class="text-muted lh-lg detail-copy"><?= nl2br(htmlspecialchars($reportNarrative !== '' ? $reportNarrative : 'No description provided.')) ?></div>
            </div>

            <div class="card report-detail-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-semibold mb-0">Evidence</h5>
                    <span class="badge text-bg-light"><?= (int) $evidenceCount ?> file<?= $evidenceCount === 1 ? '' : 's' ?></span>
                </div>
                <?php render_component('evidence-gallery', [
                    'items' => $evidence,
                    'allowPermanentDelete' => true,
                    'emptyText' => 'No evidence images were provided for this report.',
                    'cardClass' => 'evidence-card',
                    'metaClass' => 'evidence-meta',
                ]); ?>
            </div>

            <div class="card report-detail-card">
                <h5 class="fw-semibold mb-3"><?= $isBug || $isFeatureRequest ? 'Review & implementation notes' : 'Admin notes' ?></h5>
                <div class="admin-note-box">
                    <?= nl2br(htmlspecialchars($report->admin_notes ?? 'No admin notes yet.')) ?>
                </div>
            </div>

            <div class="card report-detail-card">
                <h5 class="fw-semibold mb-3">Investigation timeline</h5>
                <?php if (!empty($actions)): ?>
                    <div class="timeline">
                        <?php foreach ($actions as $action): ?>
                            <div class="timeline-item mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
                                    <div>
                                        <strong><?= htmlspecialchars(str_replace('_', ' ', $action->action)) ?></strong>
                                        <div class="small text-muted">
                                            <?= htmlspecialchars(trim(($action->admin_first_name ?? '') . ' ' . ($action->admin_last_name ?? '')) ?: 'Admin #' . (int) $action->admin_id) ?>
                                        </div>
                                    </div>
                                    <div class="small text-muted"><?= date('M j, Y H:i', strtotime($action->created_at)) ?></div>
                                </div>
                                <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($action->notes ?? 'No notes.')) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No actions have been taken yet.</p>
                <?php endif; ?>
                <div class="timeline-item timeline-item-submitted">
                    <div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
                        <div><strong>Report submitted</strong><div class="small text-muted"><?= htmlspecialchars($reporterName) ?></div></div>
                        <div class="small text-muted"><?= !empty($report->created_at) ? date('M j, Y H:i', strtotime($report->created_at)) : 'Unknown' ?></div>
                    </div>
                    <p class="mb-0 text-muted">The report entered the Trust & Safety review queue.</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 report-detail-sidebar">
            <div class="card report-detail-card report-action-card">
                <h5 class="fw-semibold mb-3">Quick actions</h5>
                <p class="report-action-intro">Choose the next case outcome. Your investigation notes become part of the permanent action history.</p>
                <form method="POST" action="/ani-mart/public/admin/trust-center/reports/action">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="report_id" value="<?= (int) $report->id ?>">
                    <div class="mb-3">
                        <label class="form-label">Action</label>
                        <select name="action" class="form-select" required>
                            <option value="">Select action</option>
                            <option value="request_more_info"><?= $isBug || $isFeatureRequest ? 'Request more details' : 'Request more evidence' ?></option>
                            <?php if (!$isBug && !$isFeatureRequest): ?>
                                <option value="warn_user">Warn user</option>
                            <?php endif; ?>
                            <option value="reject_report"><?= $isFeatureRequest ? 'Decline request' : ($isBug ? 'Close as not reproducible' : 'Reject report') ?></option>
                            <option value="resolve_report"><?= $isFeatureRequest ? 'Mark reviewed / planned' : ($isBug ? 'Mark fixed / resolved' : 'Resolve report') ?></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Investigation notes</label>
                        <textarea name="notes" rows="4" maxlength="1000" class="form-control" placeholder="Summarize what you reviewed and why this action is appropriate."></textarea>
                        <div class="form-text">Include evidence reviewed, findings, and any follow-up required.</div>
                    </div>
                    <button class="btn btn-primary w-100">Submit action</button>
                </form>
            </div>

            <div class="card report-detail-card">
                <h5 class="fw-semibold mb-3">Review checklist</h5>
                <ul class="report-review-checklist">
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i><span>Confirm the reported target matches the description.</span></li>
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i><span>Review every evidence attachment at full size.</span></li>
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i><span>Check previous actions and any submitted appeals.</span></li>
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i><span>Write clear notes before recording the decision.</span></li>
                </ul>
            </div>

            <div class="card report-detail-card">
                <h5 class="fw-semibold mb-3">Case details</h5>
                <dl class="case-detail-list">
                    <div>
                        <dt>Reporter</dt>
                        <dd><?= htmlspecialchars($reporterName) ?></dd>
                        <?php if (!empty($report->reporter_email)): ?>
                            <small class="text-muted"><?= htmlspecialchars($report->reporter_email) ?></small>
                        <?php endif; ?>
                    </div>
                    <div>
                        <dt>Case type</dt>
                        <dd><?= htmlspecialchars($caseLabel) ?></dd>
                    </div>
                    <div>
                        <dt>Report reason</dt>
                        <dd><?= htmlspecialchars($report->reason ?? 'No reason provided') ?></dd>
                    </div>
                    <div>
                        <dt>Target type</dt>
                        <dd><?= htmlspecialchars($targetName) ?></dd>
                    </div>
                    <div>
                        <dt>Target ID</dt>
                        <dd>#<?= (int) ($report->target_id ?? 0) ?></dd>
                    </div>
                    <div>
                        <dt>Submission date</dt>
                        <dd><?= !empty($report->created_at) ? date('M j, Y g:i A', strtotime($report->created_at)) : 'Not available' ?></dd>
                    </div>
                    <?php if (!empty($report->resolved_at)): ?>
                        <div>
                            <dt>Resolution date</dt>
                            <dd><?= date('M j, Y g:i A', strtotime($report->resolved_at)) ?></dd>
                        </div>
                        <div>
                            <dt>Resolved by</dt>
                            <dd><?= htmlspecialchars($resolverName) ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($lastAction)): ?>
                        <div>
                            <dt>Latest action</dt>
                            <dd><?= htmlspecialchars(str_replace('_', ' ', $lastAction->action)) ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>

            <div class="card report-detail-card">
                <h5 class="fw-semibold mb-3">Appeals</h5>
                <?php if (!empty($appeals)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($appeals as $appeal): ?>
                            <li class="list-group-item px-0 py-3">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <strong>Appeal #<?= (int) $appeal->id ?></strong>
                                        <div class="small text-muted mb-1">Submitted by <?= htmlspecialchars(trim(($appeal->first_name ?? '') . ' ' . ($appeal->last_name ?? '')) ?: 'User #' . (int) $appeal->user_id) ?> · <?= date('M j, Y', strtotime($appeal->created_at)) ?></div>
                                        <p class="small text-muted mb-1"><?= nl2br(htmlspecialchars($appeal->reason)) ?></p>
                                    </div>
                                    <span class="badge bg-<?= $appeal->status === 'accepted' ? 'success' : ($appeal->status === 'rejected' ? 'danger' : 'secondary') ?> text-capitalize"><?= htmlspecialchars($appeal->status) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No appeals have been submitted for this report.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
