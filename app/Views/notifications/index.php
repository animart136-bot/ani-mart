<?php

use App\Core\Session;

$user = Session::get('user');
$notifications = $notifications ?? [];
$unreadCount = $unreadCount ?? 0;

$typeCounts = [];

foreach ($notifications as $notification) {
    $type = $notification['type'] ?? 'system';
    $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
}

$pageStyles[] = '/ani-mart/public/assets/css/pages/notifications.css?v=' . filemtime(__DIR__ . '/../../../public/assets/css/pages/notifications.css');

ob_start();

?>

<div class="activity-feed-page app-page-spacing">

    <div class="container">

        <div class="hero mb-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="hero-badge">Notifications</span>
                    <h1 class="hero-title mt-3 mb-2">Marketplace Activity Feed</h1>
                    <p class="hero-subtitle">A live timeline of orders, harvest updates, trust signals, reviews, and community activity relevant to you.</p>
                </div>
                <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                    <div class="activity-summary" aria-label="<?= (int) $unreadCount ?> unread notifications">
                        <span class="activity-summary-icon"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                        <span class="activity-summary-copy">
                            <strong id="activityUnreadCount"><?= (int) $unreadCount ?></strong>
                            <span>Unread notifications</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="activity-filters mb-4" id="activityFilters" aria-label="Notification filters">

            <button class="activity-filter active" type="button" data-filter="all">
                All
                <span><?= count($notifications) ?></span>
            </button>

            <?php foreach ($typeCounts as $type => $count): ?>

                <button class="activity-filter" type="button" data-filter="<?= htmlspecialchars($type) ?>">
                    <?= htmlspecialchars(ucfirst($type)) ?>
                    <span><?= (int) $count ?></span>
                </button>

            <?php endforeach; ?>

        </div>

        <div class="activity-shell" id="activityShell">

            <?php if (empty($notifications)): ?>

                <div class="activity-empty text-center py-5">

                    <i class="bi bi-bell-slash"></i>
                    <h2 class="h5 mt-3">No activity yet</h2>
                    <p class="text-muted mb-0">
                        New orders, harvest alerts, trust updates, and marketplace events will appear here.
                    </p>

                </div>

            <?php else: ?>

                <div class="activity-timeline" id="activityTimeline">

                    <?php foreach ($notifications as $notification): ?>

                        <?php
                            $isUnread = (int) ($notification['is_read'] ?? 0) === 0;
                            $url = $notification['redirect_url'] ?: ($notification['url'] ?: '#');
                            $createdAtMilliseconds = (int) ($notification['created_at_timestamp'] ?? 0);
                            $createdAtSeconds = $createdAtMilliseconds > 0
                                ? (int) floor($createdAtMilliseconds / 1000)
                                : strtotime((string) ($notification['created_at'] ?? ''));
                            $createdAtDisplay = $createdAtSeconds
                                ? date('M j, Y · g:i A', $createdAtSeconds) . ' PHT'
                                : 'Date unavailable';
                        ?>

                        <a
                            href="<?= htmlspecialchars($url) ?>"
                            class="activity-item <?= $isUnread ? 'is-unread' : '' ?>"
                            data-type="<?= htmlspecialchars($notification['type']) ?>"
                            data-id="<?= (int) $notification['id'] ?>"
                            data-created-at="<?= (int) ($notification['created_at_timestamp'] ?? 0) ?>"
                            title="Open notification">

                            <span class="activity-dot notification-icon-<?= htmlspecialchars($notification['color']) ?>">
                                <i class="bi <?= htmlspecialchars($notification['icon']) ?>"></i>
                            </span>

                            <span class="activity-content">

                                <span class="activity-meta">
                                    <span><?= htmlspecialchars($notification['type_label']) ?></span>
                                    <time class="activity-time" datetime="<?= htmlspecialchars((string) ($notification['created_at'] ?? '')) ?>"><?= htmlspecialchars($createdAtDisplay) ?></time>
                                </span>

                                <strong><?= htmlspecialchars($notification['title']) ?></strong>

                                <span><?= htmlspecialchars($notification['message']) ?></span>

                            </span>

                            <?php if ($isUnread): ?>

                                <span class="activity-unread">New</span>

                            <?php endif; ?>

                        </a>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<script>
const notificationCsrfToken = <?= json_encode(csrf_token()) ?>;
const activityFilters = document.getElementById('activityFilters');
const activityShell = document.getElementById('activityShell');
const activityUnreadCount = document.getElementById('activityUnreadCount');
let activeActivityFilter = 'all';
let notificationRefreshInProgress = false;

function activityEscape(value) {
    return String(value || '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[character]));
}

function formatActivityDateTime(timestamp) {
    const createdAt = Number(timestamp);
    if (!createdAt) return 'Date unavailable';

    const date = new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila',
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    }).format(new Date(createdAt));
    const time = new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    }).format(new Date(createdAt));

    return `${date} · ${time} PHT`;
}

function applyActivityFilter() {
    document.querySelectorAll('.activity-filter').forEach(button => {
        button.classList.toggle('active', button.dataset.filter === activeActivityFilter);
    });

    document.querySelectorAll('.activity-item').forEach(item => {
        item.classList.toggle(
            'd-none',
            activeActivityFilter !== 'all' && item.dataset.type !== activeActivityFilter
        );
    });
}

function renderActivityFilters(notifications) {
    const counts = {};
    notifications.forEach(notification => {
        const type = notification.type || 'system';
        counts[type] = (counts[type] || 0) + 1;
    });

    if (activeActivityFilter !== 'all' && !counts[activeActivityFilter]) {
        activeActivityFilter = 'all';
    }

    activityFilters.innerHTML = [
        `<button class="activity-filter" type="button" data-filter="all">All <span>${notifications.length}</span></button>`,
        ...Object.entries(counts).map(([type, count]) =>
            `<button class="activity-filter" type="button" data-filter="${activityEscape(type)}">${activityEscape(type.charAt(0).toUpperCase() + type.slice(1))} <span>${count}</span></button>`
        )
    ].join('');

    applyActivityFilter();
}

function renderActivityTimeline(notifications) {
    if (!notifications.length) {
        activityShell.innerHTML = `
            <div class="activity-empty text-center py-5">
                <i class="bi bi-bell-slash"></i>
                <h2 class="h5 mt-3">No activity yet</h2>
                <p class="text-muted mb-0">New marketplace events will appear here automatically.</p>
            </div>`;
        return;
    }

    activityShell.innerHTML = `<div class="activity-timeline" id="activityTimeline">${notifications.map(notification => {
        const unread = Number(notification.is_read) === 0;
        const url = notification.redirect_url || notification.url || '#';
        return `
            <a href="${activityEscape(url)}" class="activity-item ${unread ? 'is-unread' : ''}"
                data-type="${activityEscape(notification.type || 'system')}"
                data-id="${Number(notification.id)}"
                data-created-at="${Number(notification.created_at_timestamp || 0)}"
                title="Open notification">
                <span class="activity-dot notification-icon-${activityEscape(notification.color || 'secondary')}">
                    <i class="bi ${activityEscape(notification.icon || 'bi-bell')}"></i>
                </span>
                <span class="activity-content">
                    <span class="activity-meta">
                        <span>${activityEscape(notification.type_label || notification.type)}</span>
                        <time class="activity-time" datetime="${activityEscape(notification.created_at || '')}">${activityEscape(formatActivityDateTime(notification.created_at_timestamp))}</time>
                    </span>
                    <strong>${activityEscape(notification.title)}</strong>
                    <span>${activityEscape(notification.message)}</span>
                </span>
                ${unread ? '<span class="activity-unread">New</span>' : ''}
            </a>`;
    }).join('')}</div>`;

    applyActivityFilter();
}

async function refreshActivityFeed() {
    if (notificationRefreshInProgress || document.hidden) return;
    notificationRefreshInProgress = true;

    try {
        const response = await fetch('/ani-mart/public/notifications/fetch?limit=60', {
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        });
        const data = await response.json();

        if (!response.ok || !data.success) return;

        activityUnreadCount.textContent = Number(data.unread_count || 0);
        renderActivityFilters(data.notifications || []);
        renderActivityTimeline(data.notifications || []);
    } catch (error) {
        console.error('Unable to refresh notifications:', error);
    } finally {
        notificationRefreshInProgress = false;
    }
}

activityFilters.addEventListener('click', event => {
    const button = event.target.closest('.activity-filter');
    if (!button) return;
    activeActivityFilter = button.dataset.filter || 'all';
    applyActivityFilter();
});

activityShell.addEventListener('click', event => {
    const item = event.target.closest('.activity-item');
    if (!item) return;

    const formData = new FormData();
    formData.append('_token', notificationCsrfToken);
    formData.append('notification_id', item.dataset.id);

    fetch('/ani-mart/public/notifications/read', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    });
});

document.addEventListener('visibilitychange', () => {
    if (!document.hidden) refreshActivityFeed();
});

refreshActivityFeed();
window.setInterval(refreshActivityFeed, 5000);
</script>



<?php

$content = ob_get_clean();

require ANI_MART_ROOT . '/app/Views/layouts/main.php';
