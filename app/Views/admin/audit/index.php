<?php

$title = 'Security Audit Log';
$logs = $logs ?? [];
$filters = $filters ?? [];

ob_start();
?>

<div class="container app-page-spacing admin-audit-page">
    <div class="admin-hero mb-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="hero-badge">Security Audit Trail</span>
                <h1 class="hero-title mt-4">Admin Audit Log</h1>
                <p class="hero-subtitle mt-3">
                    Review administrator actions and security-sensitive updates without exposing credential material.
                </p>
                <a href="/ani-mart/public/dashboard" class="admin-dashboard-back-link"><i class="bi bi-arrow-left" aria-hidden="true"></i> Admin dashboard</a>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-5 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="/ani-mart/public/admin/audit-logs" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All</option>
                        <option value="success" <?= (($filters['status'] ?? '') === 'success') ? 'selected' : '' ?>>Success</option>
                        <option value="failed" <?= (($filters['status'] ?? '') === 'failed') ? 'selected' : '' ?>>Failed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">From</label>
                    <input type="date" class="form-control" name="from" value="<?= htmlspecialchars((string) ($filters['from'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To</label>
                    <input type="date" class="form-control" name="to" value="<?= htmlspecialchars((string) ($filters['to'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary rounded-pill flex-grow-1" type="submit">Filter</button>
                        <a href="/ani-mart/public/admin/audit-logs" class="btn btn-outline-secondary rounded-circle admin-audit-clear" aria-label="Clear filters" title="Clear filters"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-5 admin-audit-table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0 admin-responsive-table">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            <th>Actor</th>
                            <th>Event</th>
                            <th>Target</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td data-label="Time"><?= htmlspecialchars(date('M d, Y h:i A', strtotime($log->created_at ?? 'now'))) ?></td>
                                    <td data-label="Actor">
                                        <?= htmlspecialchars((string) ($log->actor_name ?? 'System')) ?>
                                        <?php if (!empty($log->user_id)): ?>
                                            <div class="small text-muted">User #<?= (int) $log->user_id ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Event">
                                        <span class="badge bg-dark-subtle text-dark rounded-pill">
                                            <?= htmlspecialchars((string) ($log->action ?? 'unknown')) ?>
                                        </span>
                                    </td>
                                    <td data-label="Target">
                                        <?= htmlspecialchars((string) ($log->entity_type ?? 'n/a')) ?>
                                        <?php if (!empty($log->entity_id)): ?>
                                            <div class="small text-muted">#<?= (int) $log->entity_id ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Status">
                                        <span class="badge rounded-pill bg-success-subtle text-success">
                                            success
                                        </span>
                                    </td>
                                    <td data-label="Details">
                                        <?php $details = []; ?>
                                        <?php if (!empty($log->old_value) || !empty($log->new_value) || !empty($log->description)): ?>
                                            <?php $details['description'] = $log->description ?? ''; ?>
                                            <?php if (!empty($log->old_value)) { $details['old_value'] = $log->old_value; } ?>
                                            <?php if (!empty($log->new_value)) { $details['new_value'] = $log->new_value; } ?>
                                        <?php endif; ?>
                                        <?php if (!empty($details)): ?>
                                            <div class="small text-muted">
                                                <?php foreach ($details as $key => $value): ?>
                                                    <div><strong><?= htmlspecialchars((string) $key) ?>:</strong> <?= htmlspecialchars(is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_SLASHES)) ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="admin-table-empty">
                                <td colspan="6">
                                    <div class="text-center py-5">
                                        <i class="bi bi-shield-check text-success" style="font-size:3rem;"></i>
                                        <h5 class="mt-3">No audit events recorded.</h5>
                                        <p class="text-muted mb-0">Admin actions will appear here once security-sensitive changes are performed.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
?>
