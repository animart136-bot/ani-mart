<?php

$title = 'Trust & Resolution Center';
$bodyClass = 'admin-complaints-page';
$pageStyles[] = '/ani-mart/public/assets/css/pages/admin-complaints.css?v=' .
    filemtime(__DIR__ . '/../../../../public/assets/css/pages/admin-complaints.css');

$complaintCount = count($complaints ?? []);
$activeCount = 0;
$highPriorityCount = 0;

foreach ($complaints ?? [] as $complaint) {
    if (!\App\Services\TrustCasePolicy::isFinal('complaint', $complaint->status)) {
        $activeCount++;
    }

    if ($complaint->priority === 'high') {
        $highPriorityCount++;
    }
}

ob_start();

?>

<main class="container admin-complaints-shell app-page-spacing">
    <section class="complaints-hero mb-4" aria-labelledby="complaints-title">
        <div class="complaints-hero-copy">
            <span class="complaints-eyebrow">
                Trust & Resolution Center
            </span>
            <h1 id="complaints-title">Complaint Management</h1>
            <p>Review marketplace disputes, prioritize urgent cases, and keep every resolution clear and accountable.</p>
            <a href="/ani-mart/public/dashboard" class="complaints-back-link">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Admin dashboard
            </a>
        </div>

        <div class="complaints-hero-stats" aria-label="Complaint summary for the current results">
            <div>
                <span>Cases shown</span>
                <strong><?= number_format($complaintCount) ?></strong>
            </div>
            <div>
                <span>Active</span>
                <strong><?= number_format($activeCount) ?></strong>
            </div>
            <div>
                <span>High priority</span>
                <strong><?= number_format($highPriorityCount) ?></strong>
            </div>
        </div>
    </section>

    <section class="complaints-filter-panel mb-4" aria-labelledby="complaint-filters-title">
        <div class="complaints-section-heading">
            <div>
                <span>Case queue</span>
                <h2 id="complaint-filters-title">Find a complaint</h2>
            </div>
            <?php if (array_filter($filters ?? [], static fn ($value) => $value !== '' && $value !== 'newest')): ?>
                <a href="/ani-mart/public/admin/complaints" class="complaints-clear-link">
                    <i class="bi bi-x-circle" aria-hidden="true"></i> Clear filters
                </a>
            <?php endif; ?>
        </div>

        <form class="complaints-filter-grid" method="GET" action="/ani-mart/public/admin/complaints">
            <div class="complaints-search-field">
                <label for="complaint-search">Search</label>
                <div class="complaints-input-wrap">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="complaint-search" name="search" class="form-control" placeholder="ID, order, customer, or shop" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
            </div>
            <div>
                <label for="complaint-status">Status</label>
                <select id="complaint-status" name="status" class="form-select">
                <option value="">All Statuses</option>
                <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="under_review" <?= ($filters['status'] ?? '') === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                <option value="needs_more_evidence" <?= ($filters['status'] ?? '') === 'needs_more_evidence' ? 'selected' : '' ?>>Needs Evidence</option>
                <option value="resolved" <?= ($filters['status'] ?? '') === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                <option value="rejected" <?= ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="closed" <?= ($filters['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option>
            </select>
            </div>
            <div>
                <label for="complaint-priority">Priority</label>
                <select id="complaint-priority" name="priority" class="form-select">
                <option value="">All Priorities</option>
                <option value="high" <?= ($filters['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
                <option value="medium" <?= ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium</option>
                <option value="low" <?= ($filters['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
            </select>
            </div>
            <div>
                <label for="complaint-sort">Sort by</label>
                <select id="complaint-sort" name="sort" class="form-select">
                <option value="newest" <?= ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Newest</option>
                <option value="oldest" <?= ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                <option value="high_priority" <?= ($filters['sort'] ?? '') === 'high_priority' ? 'selected' : '' ?>>High Priority</option>
            </select>
            </div>
            <button type="submit" class="btn complaints-filter-button">
                <i class="bi bi-funnel" aria-hidden="true"></i> Apply filters
            </button>
        </form>
    </section>

    <section class="complaints-table-card" aria-labelledby="complaints-results-title">
        <div class="complaints-results-heading">
            <div>
                <span>Resolution queue</span>
                <h2 id="complaints-results-title">Complaints</h2>
            </div>
            <span class="complaints-result-count"><?= number_format($complaintCount) ?> <?= $complaintCount === 1 ? 'case' : 'cases' ?></span>
        </div>

        <?php if (!empty($complaints)): ?>
        <div class="table-responsive complaints-table-wrap">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Seller</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Updated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($complaints as $complaint): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($complaint->id) ?></td>
                            <td><?= htmlspecialchars($complaint->order_number) ?></td>
                            <td><?= htmlspecialchars($complaint->customer_first_name . ' ' . $complaint->customer_last_name) ?></td>
                            <td><?= htmlspecialchars($complaint->shop_name) ?></td>
                            <td><?= htmlspecialchars($complaint->category) ?></td>
                            <td>
                                <span class="badge <?= e(\App\Services\StatusPresenter::badgeClass($complaint->status)) ?> rounded-pill complaints-status-badge">
                                    <?= e(\App\Services\StatusPresenter::label($complaint->status)) ?>
                                </span>
                            </td>
                            <td><span class="complaints-priority complaints-priority-<?= htmlspecialchars($complaint->priority) ?>"><?= ucfirst(htmlspecialchars($complaint->priority)) ?></span></td>
                            <td><?= date('M d, Y', strtotime($complaint->updated_at ?? $complaint->created_at)) ?></td>
                            <td>
                                <a href="/ani-mart/public/admin/complaints/show?id=<?= htmlspecialchars($complaint->id) ?>" class="btn btn-sm complaints-review-button">Review <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="complaints-empty-state text-center">
            <span><i class="bi bi-file-earmark-check" aria-hidden="true"></i></span>
            <h3>No disputes available</h3>
            <p class="text-muted">No disputes match the current filters.</p>
            <?php if (array_filter($filters ?? [], static fn ($value) => $value !== '' && $value !== 'newest')): ?>
                <a href="/ani-mart/public/admin/complaints" class="btn complaints-filter-button">View all complaints</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>
</main>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
