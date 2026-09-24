<?php

use App\Core\Session;

$user = Session::get('user');

$cartCount = $cartCount ?? 0;
$applicationStatus = $applicationStatus ?? null;

$currentNavPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$navIsActive = static function (string ...$paths) use ($currentNavPath): bool {
    foreach ($paths as $path) {
        if ($currentNavPath === $path || str_starts_with($currentNavPath, rtrim($path, '/') . '/')) {
            return true;
        }
    }

    return false;
};

?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">

    <div class="container-fluid px-lg-5">

        <!-- BRAND -->

        <a
            class="navbar-brand"
            aria-label="Ani-Mart home"
            href="/ani-mart/public/">

            <img
                src="/ani-mart/public/assets/img/ani-mart-logo.png?v=<?= filemtime(__DIR__ . '/../../../public/assets/img/ani-mart-logo.png') ?>"
                class="navbar-brand-logo"
                alt="Ani-Mart">

        </a>

        <!-- NAVIGATION -->

        <div
            class="navbar-collapse show"
            id="navbarContent">

            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">

                <!-- MARKETPLACE -->

                <li class="nav-item">

                    <a
                        class="nav-link nav-icon-link<?= $navIsActive('/ani-mart/public/marketplace') ? ' active' : '' ?>"
                        href="/ani-mart/public/marketplace"
                        <?= $navIsActive('/ani-mart/public/marketplace') ? 'aria-current="page"' : '' ?>
                        aria-label="Marketplace"
                        title="Marketplace">

                        <i class="bi bi-shop fs-4"></i>

                    </a>

                </li>

                <li class="nav-item">
                    <a class="nav-link nav-icon-link<?= $navIsActive('/ani-mart/public/community') ? ' active' : '' ?>" href="/ani-mart/public/community" <?= $navIsActive('/ani-mart/public/community') ? 'aria-current="page"' : '' ?> aria-label="Community Hub" title="Community Hub">
                        <i class="bi bi-globe2 fs-4"></i>
                    </a>
                </li>

                <?php if ($user): ?>

                    <?php
                    $role =
                        strtolower(
                            $user['role'] ?? ''
                        );
                    $primaryWorkspacePath = $role === 'farmer'
                        ? '/ani-mart/public/seller-center'
                        : '/ani-mart/public/dashboard';
                    $primaryWorkspaceLabel = $role === 'farmer'
                        ? 'Seller Centre'
                        : 'Dashboard';
                    $storefrontPath = null;
                    if ($role === 'farmer' && !empty($activeShop)) {
                        $storefrontPath = !empty($activeShop->slug)
                            ? '/ani-mart/public/farm/' . rawurlencode((string) $activeShop->slug)
                            : '/ani-mart/public/marketplace';
                    }
                    ?>

                    <!-- DASHBOARD -->

                    <li class="nav-item">

                        <a
                            class="nav-link nav-icon-link<?= $navIsActive($primaryWorkspacePath) ? ' active' : '' ?>"
                            href="<?= $primaryWorkspacePath ?>"
                            <?= $navIsActive($primaryWorkspacePath) ? 'aria-current="page"' : '' ?>
                            aria-label="<?= $primaryWorkspaceLabel ?>"
                            title="<?= $primaryWorkspaceLabel ?>">

                            <i class="bi bi-grid-1x2 fs-4"></i>

                        </a>

                    </li>

                    <!-- MESSAGES -->

                    <li class="nav-item">

                        <a
                            class="nav-link nav-icon-link<?= $navIsActive('/ani-mart/public/messages') ? ' active' : '' ?>"
                            href="/ani-mart/public/messages"
                            <?= $navIsActive('/ani-mart/public/messages') ? 'aria-current="page"' : '' ?>
                            aria-label="Messages"
                            title="Messages">

                            <span class="nav-badge-target">

                                <i class="bi bi-chat-left-text fs-4"></i>

                                <span
                                    id="messageBadge"
                                    class="nav-floating-badge badge rounded-pill bg-danger d-none">

                                    0

                                </span>

                            </span>

                        </a>

                    </li>

                    <!-- FARMER -->

                    <?php if ($role === 'farmer'): ?>

                        <?php include __DIR__ . '/shop_switcher.php'; ?>

                    <?php endif; ?>

                    <!-- SHOPPING CART


                         Customers and farmers can shop.


                         Farmers can buy from other sellers; own-product


                         restrictions must be enforced by the product-card/cart backend. -->


                    <?php if (in_array($role, ['customer', 'farmer'], true)): ?>



                        <li class="nav-item">



                            <a


                                href="/ani-mart/public/cart"


                                class="nav-icon-link<?= $navIsActive('/ani-mart/public/cart', '/ani-mart/public/checkout') ? ' active' : '' ?>"


                                title="Cart"


                                aria-label="Cart"
                                <?= $navIsActive('/ani-mart/public/cart', '/ani-mart/public/checkout') ? 'aria-current="page"' : '' ?>>



                                <span class="nav-badge-target">



                                    <i class="bi bi-cart2 fs-4"></i>



                                    <span


                                        id="cartBadge"


                                        class="nav-floating-badge badge rounded-pill bg-danger">



                                        <?= (int) $cartCount ?>



                                    </span>



                                </span>



                            </a>



                        </li>



                    <?php endif; ?>

                    <!-- NOTIFICATIONS -->

                    <li class="nav-item dropdown">

                        <a
                            class="nav-link nav-icon-link position-relative<?= $navIsActive('/ani-mart/public/notifications') ? ' active' : '' ?>"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            id="notificationDropdown"
                            aria-label="Notifications"
                            aria-expanded="false"
                            title="Notifications">

                            <span class="nav-badge-target">

                                <i class="bi bi-bell fs-4"></i>

                                <span
                                    id="notificationBadge"
                                    class="nav-floating-badge badge rounded-pill bg-danger d-none">

                                    0

                                </span>

                            </span>

                        </a>

                        <ul
                            class="dropdown-menu dropdown-menu-end border-0 shadow-lg notification-dropdown"
                            aria-labelledby="notificationDropdown">

                            <li class="dropdown-header d-flex justify-content-between align-items-center">

                                <span class="fw-bold">Notifications</span>

                                <button
                                    type="button"
                                    id="markAllReadBtn"
                                    class="btn btn-sm btn-link text-decoration-none p-0">

                                    Mark all read

                                </button>

                            </li>

                            <li><hr class="dropdown-divider"></li>

                            <li id="notificationList">

                                <div class="text-center py-4">

                                    <i class="bi bi-bell-slash fs-1 text-muted mb-2 d-block"></i>

                                    <small class="text-muted">No notifications</small>

                                </div>

                            </li>

                            <li><hr class="dropdown-divider"></li>

                            <li>

                                <a
                                    class="dropdown-item text-center fw-semibold text-success"
                                    href="/ani-mart/public/notifications">

                                    View All Notifications

                                </a>

                            </li>

                        </ul>

                    </li>

                    <!-- PROFILE -->

                    <li class="nav-item dropdown">

                        <a
                            class="nav-link nav-icon-link d-flex align-items-center<?= $navIsActive('/ani-mart/public/profile') ? ' active' : '' ?>"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            aria-label="Account menu"
                            title="Account">

                            <div class="profile-avatar">

                                <?php if (!empty($user['profile_image'])): ?>

                                    <img
                                        src="<?= htmlspecialchars($user['profile_image']) ?>"
                                        alt="Profile photo">

                                <?php else: ?>

                                    <?= strtoupper(
                                        substr(
                                            $user['first_name'] ?? 'U',
                                            0,
                                            1
                                        )
                                    ) ?>

                                <?php endif; ?>

                            </div>

                        </a>

                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-4 profile-dropdown">

                            <li class="dropdown-header">
                                <div class="fw-semibold">
                                    <?= htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')) ?>
                                </div>
                                <small class="text-muted text-capitalize">
                                    <?= htmlspecialchars($role) ?>
                                </small>
                            </li>

                            <li><hr class="dropdown-divider"></li>

                            <?php if ($role === 'farmer'): ?>

                                <li>
                                    <a class="dropdown-item<?= $currentNavPath === '/ani-mart/public/seller-center' ? ' active' : '' ?>" href="/ani-mart/public/seller-center" <?= $currentNavPath === '/ani-mart/public/seller-center' ? 'aria-current="page"' : '' ?>>
                                        <i class="bi bi-shop-window me-2"></i>
                                        Seller Centre
                                    </a>
                                </li>

                                <?php if ($storefrontPath): ?>
                                    <li>
                                        <a class="dropdown-item<?= $currentNavPath === parse_url($storefrontPath, PHP_URL_PATH) ? ' active' : '' ?>" href="<?= htmlspecialchars($storefrontPath, ENT_QUOTES, 'UTF-8') ?>" <?= $currentNavPath === parse_url($storefrontPath, PHP_URL_PATH) ? 'aria-current="page"' : '' ?>>
                                            <i class="bi bi-bag-check me-2"></i>
                                            Storefront
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <li><hr class="dropdown-divider"></li>

                                <li class="mobile-shop-menu">
                                    <div class="dropdown-header">Switch Shop</div>

                                <?php if (!empty($userShops)): ?>
                                    <?php foreach ($userShops as $shop): ?>
                                        <li class="mobile-shop-menu">
                                            <form method="POST" action="/ani-mart/public/seller-center/switch" class="m-0">
                                                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                                <button type="submit" name="shop_id" value="<?= (int) $shop->id ?>" class="dropdown-item">
                                                    <?= htmlspecialchars($shop->shop_name) ?>
                                                    <?php if ($shop->id === ($activeShop->id ?? 0)): ?>
                                                        <span class="badge bg-success ms-2">Active</span>
                                                    <?php endif; ?>
                                                </button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="mobile-shop-menu px-3 py-2 text-muted small">No shops found.</li>
                                <?php endif; ?>

                                <li class="mobile-shop-menu">
                                    <a class="dropdown-item<?= $navIsActive('/ani-mart/public/seller-center/shops') ? ' active' : '' ?>" href="/ani-mart/public/seller-center/shops/create" <?= $navIsActive('/ani-mart/public/seller-center/shops') ? 'aria-current="page"' : '' ?>>
                                        <i class="bi bi-plus-lg me-2"></i>
                                        Create New Shop
                                    </a>
                                </li>

                                <li class="mobile-shop-menu"><hr class="dropdown-divider"></li>

                            <?php endif; ?>

                            <?php if ($role === 'farmer'): ?>

                                <li>
                                    <a class="dropdown-item<?= $navIsActive('/ani-mart/public/reviews') ? ' active' : '' ?>" href="/ani-mart/public/reviews" <?= $navIsActive('/ani-mart/public/reviews') ? 'aria-current="page"' : '' ?>>
                                        <i class="bi bi-star me-2"></i>
                                        My Reviews
                                    </a>
                                </li>

                                <li class="mobile-shop-menu">
                                    <a class="dropdown-item<?= $navIsActive('/ani-mart/public/seller-center/reviews') ? ' active' : '' ?>" href="/ani-mart/public/seller-center/reviews" <?= $navIsActive('/ani-mart/public/seller-center/reviews') ? 'aria-current="page"' : '' ?>>
                                        <i class="bi bi-shop me-2"></i>
                                        Shop Reviews
                                    </a>
                                </li>

                            <?php endif; ?>

                            <!-- CUSTOMER -->

                            <?php if ($role === 'customer'): ?>

                                <li>

                                    <a
                                        class="dropdown-item<?= $navIsActive('/ani-mart/public/orders') ? ' active' : '' ?>"
                                        href="/ani-mart/public/orders">

                                        <i class="bi bi-journal-check me-2"></i>

                                        My Orders

                                    </a>

                                </li>

                                <li>

                                    <a
                                        class="dropdown-item<?= $navIsActive('/ani-mart/public/reviews') ? ' active' : '' ?>"
                                        href="/ani-mart/public/reviews">

                                        <i class="bi bi-star me-2"></i>

                                        My Reviews

                                    </a>

                                </li>

                                <li>

                                    <a
                                        class="dropdown-item<?= $navIsActive('/ani-mart/public/reports-complaints') ? ' active' : '' ?>"
                                        href="/ani-mart/public/reports-complaints">

                                        <i class="bi bi-shield-exclamation me-2"></i>

                                        Resolution Center

                                    </a>

                                </li>

                                <?php if ($applicationStatus === null): ?>

                                    <li>

                                        <a
                                            class="dropdown-item<?= $navIsActive('/ani-mart/public/farmer/apply') ? ' active' : '' ?>"
                                            href="/ani-mart/public/farmer/apply">

                                            <i class="bi bi-person me-2"></i>

                                            Apply as Seller

                                        </a>

                                    </li>

                                <?php elseif (in_array($applicationStatus, ['pending', 'incomplete', 'pending_official_verification', 'needs_correction'], true)): ?>

                                    <li>

                                        <a
                                            class="dropdown-item text-warning"
                                            href="/ani-mart/public/farmer/apply?edit=1"
                                        >

                                            <i class="bi bi-hourglass-split me-2"></i>

                                            Application Pending

                                        </a>

                                    </li>

                                <?php elseif ($applicationStatus === 'approved'): ?>

                                    <li>

                                        <span class="dropdown-item text-success">

                                            <i class="bi bi-check-circle-fill me-2"></i>

                                            Application Approved

                                        </span>

                                    </li>

                                <?php elseif ($applicationStatus === 'rejected'): ?>

                                    <li>

                                        <span class="dropdown-item text-danger">

                                            <i class="bi bi-x-circle-fill me-2"></i>

                                            Application Rejected

                                        </span>

                                    </li>

                                <?php endif; ?>

                            <?php endif; ?>

                            <!-- FARMER -->

                            <?php if ($role === 'farmer'): ?>

                                <li>

                                    <a
                                        class="dropdown-item<?= $navIsActive('/ani-mart/public/dashboard') ? ' active' : '' ?>"
                                        href="/ani-mart/public/dashboard">

                                        <i class="bi bi-grid-1x2 me-2"></i>

                                        Customer Dashboard

                                    </a>

                                </li>

                                <li>

                                    <a
                                        class="dropdown-item<?= $navIsActive('/ani-mart/public/farmer/orders') ? ' active' : '' ?>"
                                        href="/ani-mart/public/farmer/orders">

                                        <i class="bi bi-journal-check me-2"></i>

                                        Seller Orders

                                    </a>

                                </li>

                                <li>

                                    <a
                                        class="dropdown-item<?= $navIsActive('/ani-mart/public/customer/orders') ? ' active' : '' ?>"
                                        href="/ani-mart/public/customer/orders">

                                        <i class="bi bi-bag-check me-2"></i>

                                        My Purchases

                                    </a>

                                </li>

                                <li>

                                    <a
                                        class="dropdown-item<?= $navIsActive('/ani-mart/public/reports-complaints') ? ' active' : '' ?>"
                                        href="/ani-mart/public/reports-complaints">

                                        <i class="bi bi-shield-exclamation me-2"></i>

                                        Resolution Center

                                    </a>

                                </li>

                            <?php endif; ?>

                            <!-- ADMIN -->

                            <?php if ($role === 'admin'): ?>

                                <li>

                                    <a
                                        class="dropdown-item<?= $navIsActive('/ani-mart/public/dashboard') ? ' active' : '' ?>"
                                        href="/ani-mart/public/dashboard">

                                        <i class="bi bi-grid-1x2 me-2"></i>

                                        Admin Dashboard

                                    </a>

                                </li>

                                <li>
                                    <hr class="dropdown-divider">
                                </li>

                                <li>

                                    <a
                                        class="dropdown-item<?= $navIsActive('/ani-mart/public/admin/trust-center') ? ' active' : '' ?>"
                                        href="/ani-mart/public/admin/trust-center">

                                        <i class="bi bi-shield-exclamation me-2"></i>

                                        Trust & Safety Center

                                    </a>

                                </li>

                                <li>

                                    <a
                                        class="dropdown-item"
                                        href="/ani-mart/public/admin/complaints">

                                        <i class="bi bi-exclamation-triangle me-2"></i>

                                        Dispute Resolution

                                    </a>

                                </li>

                                <li>

                                    <a
                                        class="dropdown-item"
                                        href="/ani-mart/public/admin/applications">

                                        <i class="bi bi-person-plus me-2"></i>

                                        Seller Applications

                                    </a>

                                </li>

                                <li>
                                    <a class="dropdown-item<?= $navIsActive('/ani-mart/public/admin/categories') ? ' active' : '' ?>" href="/ani-mart/public/admin/categories">
                                        <i class="bi bi-tags me-2"></i>
                                        Product Categories
                                    </a>
                                </li>

                                <li>
                                    <a class="dropdown-item<?= $navIsActive('/ani-mart/public/admin/users') ? ' active' : '' ?>" href="/ani-mart/public/admin/users">
                                        <i class="bi bi-person-lock me-2"></i>
                                        User Access
                                    </a>
                                </li>

                                <li>

                                    <a
                                        class="dropdown-item<?= $navIsActive('/ani-mart/public/admin/reference-data/prices') ? ' active' : '' ?>"
                                        href="/ani-mart/public/admin/reference-data/prices">

                                        <i class="bi bi-currency-exchange me-2"></i>

                                        Price References

                                    </a>

                                </li>

                                <li>

                                    <a
                                        class="dropdown-item"
                                        href="/ani-mart/public/admin/audit-logs">

                                        <i class="bi bi-shield-lock me-2"></i>

                                        Audit Logs

                                    </a>

                                </li>

                            <?php endif; ?>

                            <li>

                                <a
                                    class="dropdown-item"
                                    href="/ani-mart/public/profile">

                                    <i class="bi bi-person-circle me-2"></i>

                                    My Profile

                                </a>

                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            <li>

                                <a
                                    class="dropdown-item text-danger"
                                    href="/ani-mart/public/logout"
                                    data-confirm="Are you sure you want to logout?"
                                    data-confirm-title="Logout"
                                    data-confirm-text="Logout"
                                    data-confirm-class="btn-danger">

                                    <i class="bi bi-box-arrow-right me-2"></i>

                                    Logout

                                </a>

                            </li>

                        </ul>

                    </li>

                <?php else: ?>

                    <li class="nav-item">
    <a
        class="btn btn-success rounded-pill px-4 d-flex align-items-center justify-content-center"
        href="/ani-mart/public/login"
        aria-label="Login"
        title="Login"
        style="width: 44px; height: 44px; padding: 0;">
        <i class="bi bi-person-fill fs-4" aria-hidden="true"></i>
    </a>
</li>

                <?php endif; ?>

            </ul>

        </div>

    </div>

</nav>

<?php if ($user): ?>

<script>
    // Notification system
    const navbarNotificationCsrfToken = <?= json_encode(csrf_token()) ?>;

    function fetchNotifications() {
        fetch('/ani-mart/public/notifications/fetch')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationBadge(data.unread_count);
                    updateOrderNotificationBadge(data.unread_order_count || 0);
                    renderNotifications(data.notifications);
                }
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

    function fetchMessageCount() {
        fetch('/ani-mart/public/messages/unread')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateMessageBadge(data.unread_message_count || 0);
                }
            })
            .catch(error => console.error('Error fetching message count:', error));
    }

    function updateNotificationBadge(count) {
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            badge.textContent = count;
            badge.classList.toggle('d-none', count === 0);
        }
    }

    function updateMessageBadge(count) {
        const badge = document.getElementById('messageBadge');
        if (badge) {
            badge.textContent = Number(count) > 99 ? '99+' : count;
            badge.classList.toggle('d-none', Number(count) === 0);
        }
    }

    function updateOrderNotificationBadge(count) {
        document.querySelectorAll('.order-notification-badge').forEach(badge => {
            badge.textContent = Number(count) > 99 ? '99+' : count;
            badge.classList.toggle('d-none', Number(count) === 0);
        });
    }

    function renderNotifications(notifications) {
        const list = document.getElementById('notificationList');
        if (!list) return;

        if (!notifications || notifications.length === 0) {
            list.innerHTML = `
                <div class="text-center py-4">
                    <i class="bi bi-bell-slash fs-1 text-muted mb-2 d-block"></i>
                    <small class="text-muted">No notifications</small>
                </div>
            `;
            return;
        }

        list.innerHTML = notifications.map(notification => `
            <a href="${escapeAttribute(notification.redirect_url || notification.url || '#')}" class="dropdown-item notification-item ${Number(notification.is_read) ? 'read' : 'unread'}" data-id="${Number(notification.id)}" data-created-at="${Number(notification.created_at_timestamp || 0)}" data-redirect-url="${escapeAttribute(notification.redirect_url || notification.url || '#')}">
                <div class="notification-row">
                    <div class="notification-icon notification-icon-${escapeAttribute(notification.color || 'secondary')}">
                        <i class="bi ${escapeAttribute(notification.icon || 'bi-bell')}"></i>
                    </div>
                    <div class="notification-copy">
                        <div class="notification-title">${escapeHtml(notification.title)}</div>
                        <small class="notification-message">${escapeHtml(notification.message)}</small>
                        <div class="notification-time">${escapeHtml(formatNotificationDateTime(notification.created_at_timestamp))}</div>
                    </div>
                </div>
            </a>
        `).join('');

        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function(e) {
                const targetUrl = this.dataset.redirectUrl || this.getAttribute('href');

                if (targetUrl) {
                    e.preventDefault();
                    markAsRead(Number(this.dataset.id), targetUrl);
                }
            });
        });

        updateNavbarNotificationTimes();
    }

    function getNotificationIcon(type) {
        const icons = {
            'order': 'bi-cart-check',
            'status': 'bi-info-circle',
            'system': 'bi-gear',
            'verification': 'bi-patch-check'
        };
        return icons[type] || 'bi-bell';
    }

    function formatNotificationDateTime(timestamp) {
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

    function updateNavbarNotificationTimes() {
        document.querySelectorAll('.notification-item[data-created-at]').forEach(item => {
            const label = item.querySelector('.notification-time');
            if (label) label.textContent = formatNotificationDateTime(item.dataset.createdAt);
        });
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[character]));
    }

    function escapeAttribute(value) {
        return escapeHtml(value).replace(/`/g, '&#096;');
    }

    function markAsRead(notificationId, targetUrl = null) {
        const formData = new FormData();
        formData.append('_token', navbarNotificationCsrfToken);
        formData.append('notification_id', notificationId);

        fetch('/ani-mart/public/notifications/read', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (targetUrl) {
                window.location.href = targetUrl;
                return;
            }

            if (data.success) {
                fetchNotifications();
            }
        })
        .catch(error => {
            console.error('Error marking as read:', error);

            if (targetUrl) {
                window.location.href = targetUrl;
            }
        });
    }

    document.getElementById('markAllReadBtn')?.addEventListener('click', function() {
        const formData = new FormData();
        formData.append('_token', navbarNotificationCsrfToken);

        fetch('/ani-mart/public/notifications/mark-all-read', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(0);
                updateOrderNotificationBadge(0);
                fetchNotifications();
            }
        })
        .catch(error => console.error('Error marking all as read:', error));
    });

    fetchNotifications();
    fetchMessageCount();
    setInterval(fetchNotifications, 5000);
    setInterval(fetchMessageCount, 5000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            fetchNotifications();
            fetchMessageCount();
        }
    });
</script>



<?php endif; ?>
