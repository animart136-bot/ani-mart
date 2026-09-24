<?php

$title = $title ?? 'Support Center';
$bodyClass = $bodyClass ?? 'admin-page';

$conversations = $conversations ?? [];
$stats = $stats ?? [];
$templates = $templates ?? [];
$broadcasts = $broadcasts ?? [];
$activeTab = $activeTab ?? 'inbox';
$search = $search ?? '';

ob_start();
?>

<div class="container app-page-spacing">

    <div class="admin-hero mb-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="hero-badge">Ani-Mart Support Operations</span>
                <h1 class="hero-title mt-4">Admin Support Center</h1>
                <p class="hero-subtitle mt-3">
                    Manage tickets, conversations, assignments, and broadcast messages from one unified hub.
                </p>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="admin-stat-card">
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-envelope-open-fill"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Open Conversations</small>
                    <h2 class="fw-bold mb-0"><?= (int) ($stats['open'] ?? 0) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="admin-stat-card">
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Pending</small>
                    <h2 class="fw-bold mb-0"><?= (int) ($stats['pending'] ?? 0) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="admin-stat-card">
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-check2-circle"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Resolved</small>
                    <h2 class="fw-bold mb-0"><?= (int) ($stats['resolved'] ?? 0) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <nav class="nav nav-pills gap-2 flex-wrap">
            <a class="nav-link <?= $activeTab === 'inbox' ? 'active' : '' ?>" href="/ani-mart/public/admin/support">Inbox</a>
            <a class="nav-link <?= $activeTab === 'complaints' ? 'active' : '' ?>" href="/ani-mart/public/admin/support?tab=complaints">Complaints</a>
            <a class="nav-link <?= $activeTab === 'bug_reports' ? 'active' : '' ?>" href="/ani-mart/public/admin/support?tab=bug_reports">Bug Reports</a>
            <a class="nav-link <?= $activeTab === 'verification' ? 'active' : '' ?>" href="/ani-mart/public/admin/support?tab=verification">Verifications</a>
            <a class="nav-link <?= $activeTab === 'community_reports' ? 'active' : '' ?>" href="/ani-mart/public/admin/support?tab=community_reports">Community</a>
            <a class="nav-link <?= $activeTab === 'archived' ? 'active' : '' ?>" href="/ani-mart/public/admin/support?tab=archived">Archived</a>
            <a class="nav-link <?= $activeTab === 'settings' ? 'active' : '' ?>" href="/ani-mart/public/admin/support/settings">Settings</a>
        </nav>
    </div>

    <div class="card border-0 shadow-sm rounded-5 p-4 mb-5">
        <div class="row align-items-center gx-3">
            <div class="col-lg-6">
                <form class="d-flex" method="GET" action="/ani-mart/public/admin/support">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">
                    <div class="input-group">
                        <input
                            type="search"
                            name="search"
                            class="form-control rounded-start-pill"
                            aria-label="Search support conversations"
                            placeholder="Search conversations, customers, farmers..."
                            value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary rounded-end-pill" type="submit" aria-label="Search">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
                <a href="/ani-mart/public/admin/support/settings" class="btn btn-outline-secondary rounded-pill">
                    <i class="bi bi-gear me-2"></i> Support Settings
                </a>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-5">
        <div class="card-body p-4">
            <div class="table-responsive dropdown-visible">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Assigned</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Updated</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($conversations)): ?>
                            <?php foreach ($conversations as $conversation): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($conversation->subject ?? 'No subject') ?></strong>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars(
                                                $conversation->customer_first_name
                                                ? trim($conversation->customer_first_name . ' ' . $conversation->customer_last_name)
                                                : ($conversation->farmer_first_name
                                                    ? trim($conversation->farmer_first_name . ' ' . $conversation->farmer_last_name)
                                                    : 'Unknown reporter')
                                            ) ?>
                                        </div>
                                    </td>
                                    <td class="text-capitalize">
                                        <?= htmlspecialchars(str_replace('_', ' ', $conversation->conversation_type)) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars(trim(($conversation->assigned_first_name ?? '') . ' ' . ($conversation->assigned_last_name ?? '')) ?: 'Unassigned') ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $conversation->status === 'resolved' ? 'success' : ($conversation->status === 'pending' ? 'warning text-dark' : ($conversation->status === 'rejected' ? 'danger' : 'secondary')) ?>">
                                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $conversation->status))) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $conversation->priority === 'high' ? 'danger' : ($conversation->priority === 'urgent' ? 'dark' : 'secondary') ?> text-white">
                                            <?= htmlspecialchars(ucfirst($conversation->priority)) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars(date('M d, Y H:i', strtotime($conversation->last_message_at ?? $conversation->created_at))) ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="/ani-mart/public/admin/support/conversation?id=<?= (int) $conversation->id ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    No support conversations found.
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
