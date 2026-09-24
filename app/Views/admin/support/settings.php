<?php

$title = $title ?? 'Support Center Settings';
$bodyClass = $bodyClass ?? 'admin-page';
$broadcasts = $broadcasts ?? [];

ob_start();
?>

<div class="container app-page-spacing">

    <div class="admin-hero mb-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="hero-badge">Support Settings</span>
                <h1 class="hero-title mt-4">Admin Support Center Settings</h1>
                <p class="hero-subtitle mt-3">
                    Configure ticket priorities, notification behavior, quick replies, and broadcast defaults.
                </p>
            </div>
            <div class="col-lg-4 text-center">
                <div class="hero-icon">
                    <i class="bi bi-gear-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-5 p-4 mb-5">
        <form method="POST" action="/ani-mart/public/admin/support/settings/save">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="mb-4">
                        <label class="form-label">Default Ticket Priority</label>
                        <select class="form-select rounded-4" name="default_priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="mb-4">
                        <label class="form-label">Notifications</label>
                        <select class="form-select rounded-4" name="notification_mode">
                            <option value="email">Email + Dashboard</option>
                            <option value="dashboard" selected>Dashboard Only</option>
                            <option value="sms">SMS (if configured)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Default Reply Template</label>
                <textarea class="form-control rounded-4" rows="4" placeholder="Enter default response text..."></textarea>
            </div>

            <button class="btn btn-primary rounded-pill">Save Settings</button>
        </form>
    </div>

    <div class="card border-0 shadow-sm rounded-5 p-4 mb-5">
        <h5 class="fw-bold mb-3">System Announcement</h5>
        <p class="text-muted">Publish a notification to a selected audience. Public announcements may also be pinned in the community.</p>
        <form method="POST" action="/ani-mart/public/admin/support/settings/announcement">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label">Announcement Title</label>
                        <input type="text" name="announcement_title" maxlength="255" class="form-control rounded-4" required>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label">Community category (optional)</label>
                        <select name="announcement_category_id" class="form-select rounded-4">
                            <option value="">Notification only</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category->id ?>"><?= htmlspecialchars($category->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-6"><div class="mb-3"><label class="form-label">Audience</label><select name="announcement_audience" class="form-select rounded-4"><option value="everyone">Everyone</option><option value="customers">Customers only</option><option value="farmers">Sellers only</option><option value="admins">Administrators only</option></select></div></div>
                <div class="col-lg-6"><div class="mb-3"><label class="form-label">Announcement type</label><select name="announcement_type" class="form-select rounded-4"><option value="announcement">General announcement</option><option value="maintenance">Maintenance</option><option value="emergency">Emergency</option><option value="weather_advisory">Weather advisory</option><option value="marketplace_news">Marketplace news</option><option value="agricultural_tip">Agricultural tip</option><option value="feature_update">Feature update</option></select></div></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Announcement Content</label>
                <textarea name="announcement_body" rows="4" maxlength="5000" class="form-control rounded-4" required></textarea>
            </div>
            <button class="btn btn-success rounded-pill">Publish System Announcement</button>
        </form>
    </div>

    <div class="card border-0 shadow-sm rounded-5 p-4 mb-5">
        <h5 class="fw-bold mb-3">Announcement History</h5>
        <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Title</th><th>Audience</th><th>Type</th><th>Published</th></tr></thead><tbody>
        <?php if (!$broadcasts): ?><tr><td colspan="4" class="text-center text-muted py-4">No announcements published yet.</td></tr><?php endif; ?>
        <?php foreach (array_slice($broadcasts, 0, 25) as $broadcast): ?><tr><td><strong><?= htmlspecialchars($broadcast->title ?? '') ?></strong><small class="d-block text-muted"><?= htmlspecialchars(mb_strimwidth((string) ($broadcast->body ?? ''), 0, 100, '…')) ?></small></td><td class="text-capitalize"><?= htmlspecialchars($broadcast->audience ?? 'everyone') ?></td><td><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($broadcast->message_type ?? $broadcast->broadcast_type ?? 'announcement')))) ?></td><td><?= !empty($broadcast->sent_at) ? date('M j, Y g:i A', strtotime($broadcast->sent_at)) : 'Draft' ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </div>

    <div class="card border-0 shadow-sm rounded-5 p-4 mb-5">
        <h5 class="fw-bold mb-3">New User Welcome Message</h5>
        <p class="text-muted">Send a personal welcome notification to users who joined recently.</p>
        <form method="POST" action="/ani-mart/public/admin/support/settings/welcome">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Audience</label>
                        <select name="welcome_role" class="form-select rounded-4">
                            <option value="everyone">All new users</option>
                            <option value="customers">New customers</option>
                            <option value="farmers">New farmers</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Registered Within</label>
                        <select name="welcome_days" class="form-select rounded-4">
                            <option value="7">Last 7 days</option>
                            <option value="14">Last 14 days</option>
                            <option value="30">Last 30 days</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Welcome Message</label>
                <textarea name="welcome_message" rows="4" class="form-control rounded-4" required>Welcome to Ani-Mart! We’re glad you joined our community — explore fresh produce, farm stories, and local marketplace updates.</textarea>
            </div>
            <button class="btn btn-primary rounded-pill">Send Welcome Messages</button>
        </form>
    </div>

</div>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
?>
