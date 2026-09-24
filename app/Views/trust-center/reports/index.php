<?php

$title = $title ?? 'Moderation Reports';
$bodyClass = $bodyClass ?? 'trust-safety-reports';

ob_start();
?>

<main class="container app-page-spacing">
    <section class="admin-page-hero" aria-labelledby="moderation-reports-title">
        <div class="admin-page-hero-copy">
            <span class="hero-badge">Moderation Queue</span>
            <h1 class="hero-title" id="moderation-reports-title">Moderation Reports</h1>
            <p class="hero-subtitle">Search, filter, and investigate reports across Ani-Mart.</p>
        </div>
        <div class="admin-page-hero-actions">
            <a href="/ani-mart/public/reports/create" class="btn btn-light"><i class="bi bi-plus-lg me-1"></i> Submit a Report</a>
        </div>
    </section>

    <div class="card rounded-4 shadow-sm p-4 mb-4">
        <form class="row g-3" method="GET" action="/ani-mart/public/admin/trust-center/reports">
            <div class="col-sm-6 col-lg-3">
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" class="form-control" placeholder="Search report number, reason, description">
            </div>
            <div class="col-sm-6 col-lg-2">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['submitted','pending_review','assigned','investigation','need_more_info','resolved','rejected'] as $status): ?>
                        <option value="<?= $status ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <select name="priority" class="form-select">
                    <option value="">All Priorities</option>
                    <?php foreach (['low','medium','high','critical'] as $priority): ?>
                        <option value="<?= $priority ?>" <?= ($filters['priority'] ?? '') === $priority ? 'selected' : '' ?>><?= ucfirst($priority) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <select name="target_type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach (['product','order','seller','customer','community_post','comment','message','bug','feature'] as $type): ?>
                        <option value="<?= $type ?>" <?= ($filters['target_type'] ?? '') === $type ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $type)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <select name="sort" class="form-select">
                    <option value="newest" <?= ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="oldest" <?= ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                </select>
            </div>
            <div class="col-12 col-lg-1 d-grid">
                <button class="btn btn-success">Go</button>
            </div>
        </form>
    </div>

    <div class="card rounded-4 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Reason</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Reporter</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">No reports found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?= (int) $report->id ?></td>
                                <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $report->target_type))) ?></td>
                                <td><?= htmlspecialchars($report->reason) ?></td>
                                <td><span class="badge bg-<?= $report->priority === 'critical' ? 'danger' : ($report->priority === 'high' ? 'warning text-dark' : ($report->priority === 'medium' ? 'info text-dark' : 'secondary')) ?>"><?= ucfirst($report->priority) ?></span></td>
                                <td><span class="badge bg-<?= $report->status === 'resolved' ? 'success' : ($report->status === 'rejected' ? 'danger' : ($report->status === 'submitted' ? 'secondary' : 'warning text-dark')) ?>"><?= htmlspecialchars(str_replace('_', ' ', ucfirst($report->status))) ?></span></td>
                                <td>User #<?= (int) $report->reporter_id ?></td>
                                <td><?= date('M j, Y', strtotime($report->created_at)) ?></td>
                                <td><a href="/ani-mart/public/admin/trust-center/reports/show?id=<?= (int) $report->id ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
