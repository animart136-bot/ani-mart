<?php
$title = 'User Access | Ani-Mart Administration';
$bodyClass = 'admin-controls-page';
$pageCss = '/ani-mart/public/assets/css/pages/admin-controls.css';
$users = $users ?? [];
$search = $search ?? '';
$roleFilter = $roleFilter ?? '';
$statusFilter = $statusFilter ?? '';
ob_start();
?>
<main class="container admin-controls admin-users-page app-page-spacing">
    <div class="admin-hero mb-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="hero-badge">Access Control</span>
                <h1 class="hero-title mt-4">User Accounts</h1>
                <p class="hero-subtitle mt-3">Suspend customer or seller access with a recorded reason, then restore it when the issue is resolved.</p>
                <a href="/ani-mart/public/dashboard" class="admin-dashboard-back-link"><i class="bi bi-arrow-left" aria-hidden="true"></i> Admin dashboard</a>
            </div>
        </div>
    </div>

    <section class="admin-control-card">
        <form method="get" action="/ani-mart/public/admin/users" class="row g-3 align-items-end mb-4">
            <div class="col-lg-6"><label class="form-label" for="userSearch">Search users</label><input
                    class="form-control" id="userSearch" name="search" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Name or email"></div>
            <div class="col-md-2"><label class="form-label" for="roleFilter">Role</label><select class="form-select"
                    id="roleFilter" name="role">
                    <option value="">All roles</option>
                    <option value="customer" <?= $roleFilter === 'customer' ? 'selected' : '' ?>>Customers</option>
                    <option value="farmer" <?= $roleFilter === 'farmer' ? 'selected' : '' ?>>Sellers</option>
                </select></div>
            <div class="col-md-2"><label class="form-label" for="statusFilter">Access</label><select class="form-select"
                    id="statusFilter" name="status">
                    <option value="">All statuses</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    <option value="deleted" <?= $statusFilter === 'deleted' ? 'selected' : '' ?>>Deleted</option>
                </select></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Apply filters</button></div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle admin-responsive-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Shop</th>
                        <th>Joined</th>
                        <th>Access</th>
                        <th>Control</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$users): ?>
                        <tr class="admin-table-empty">
                            <td colspan="6" class="text-center text-muted py-5">No matching users found.</td>
                        </tr><?php endif; ?>
                    <?php foreach ($users as $account): ?>
                        <?php $accountStatus = $account->account_status ?? 'active';
                        $isSuspended = $accountStatus === 'suspended';
                        $isDeleted = $accountStatus === 'deleted'; ?>
                        <tr>
                            <td data-label="User">
                                <strong><?= htmlspecialchars(trim((string) (($account->first_name ?? '') . ' ' . ($account->last_name ?? ''))) ?: 'Unnamed user') ?></strong><small
                                    class="d-block text-muted"><?= htmlspecialchars($account->email ?? '') ?></small></td>
                            <td data-label="Role"><span
                                    class="badge text-bg-light text-capitalize"><?= htmlspecialchars($account->role ?? 'customer') ?></span>
                            </td>
                            <td data-label="Shop"><?= htmlspecialchars($account->shop_name ?? '—') ?></td>
                            <td data-label="Joined">
                                <?= !empty($account->created_at) ? date('M j, Y', strtotime($account->created_at)) : '—' ?>
                            </td>
                            <td data-label="Access"><span
                                    class="badge <?= $isDeleted ? 'text-bg-secondary' : ($isSuspended ? 'text-bg-danger' : 'text-bg-success') ?>"><?= $isDeleted ? 'Deleted' : ($isSuspended ? 'Suspended' : 'Active') ?></span><?php if ($isSuspended): ?><small
                                        class="d-block text-muted mt-1"><?= htmlspecialchars($account->suspension_reason ?? '') ?></small><?php endif; ?>
                            </td>
                            <td data-label="Control">
                                <?php if ($isDeleted): ?>
                                    <span class="text-muted small">No actions available</span>
                                <?php elseif ($isSuspended): ?>
                                    <form method="post" action="/ani-mart/public/admin/users/status"><input type="hidden"
                                            name="_token" value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden"
                                            name="user_id" value="<?= (int) $account->id ?>"><input type="hidden" name="status"
                                            value="active"><button class="btn btn-sm btn-outline-success">Restore
                                            access</button></form>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#suspendUser<?= (int) $account->id ?>">Suspend</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!$isSuspended && !$isDeleted): ?>
                            <tr class="collapse suspension-row" id="suspendUser<?= (int) $account->id ?>">
                                <td colspan="6" data-label="Suspend user">
                                    <form method="post" action="/ani-mart/public/admin/users/status"
                                        class="d-flex flex-wrap gap-2 align-items-center"><input type="hidden" name="_token"
                                            value="<?= htmlspecialchars(csrf_token()) ?>"><input type="hidden" name="user_id"
                                            value="<?= (int) $account->id ?>"><input type="hidden" name="status"
                                            value="suspended"><label class="visually-hidden"
                                            for="reason<?= (int) $account->id ?>">Suspension reason</label><input
                                            class="form-control flex-grow-1" id="reason<?= (int) $account->id ?>" name="reason"
                                            minlength="5" maxlength="500" placeholder="Required suspension reason"
                                            required><button class="btn btn-danger">Confirm suspension</button></form>
                                </td>
                            </tr><?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<?php $content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php'; ?>