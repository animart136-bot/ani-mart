<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\CommunityComment;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Services\SellerProfileService;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Session::get('user');

        if (!$user) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $notification = new Notification();
        $items = $notification->timeline($user['id']);

        $this->view('notifications/index', [
            'title' => 'Marketplace Activity Feed',
            'notifications' => array_map([$this, 'formatNotification'], $items),
            'unreadCount' => $notification->unreadCount($user['id']),
            'bodyClass' => 'notifications-page'
        ]);
    }

    public function fetch()
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $user = Session::get('user');

        if (!$user) {
            echo json_encode(['success' => false, 'notifications' => [], 'unread_count' => 0]);
            exit;
        }

        $notification = new Notification();
        $requestedLimit = (int) ($_GET['limit'] ?? 10);
        $limit = max(1, min(60, $requestedLimit));
        $notifications = $limit > 10
            ? $notification->timeline($user['id'], $limit)
            : $notification->latest($user['id']);
        $unreadCount = $notification->unreadCount($user['id']);
        $unreadOrderCount = $notification->unreadCountByType($user['id'], 'order');

        echo json_encode([
            'success' => true,
            'notifications' => array_map([$this, 'formatNotification'], $notifications),
            'unread_count' => $unreadCount,
            'unread_order_count' => $unreadOrderCount
        ]);
        exit;
    }

    public function read()
    {
        csrf_verify();

        $user = Session::get('user');

        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false]);
            exit;
        }

        $notificationId = $_POST['notification_id'] ?? 0;

        if (!$notificationId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false]);
            exit;
        }

        $notification = new Notification();
        $notification->markAsRead($notificationId, $user['id']);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'unread_count' => $notification->unreadCount($user['id'])
        ]);
        exit;
    }

    public function redirect($path = null)
    {
        $user = Session::get('user');

        if (!$user) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $notificationId = (int) ($_GET['id'] ?? 0);

        if (!$notificationId) {
            header('Location: /ani-mart/public/notifications');
            exit;
        }

        $notification = (new Notification())->findById($notificationId);

        if (!$notification || (int) $notification->user_id !== (int) $user['id']) {
            \App\Helpers\ErrorResponse::unauthorized();
        }

        (new Notification())->markAsRead($notificationId, $user['id']);

        $actionUrl = ($notification->action_url ?? $notification->url ?? $notification->link ?? null)
            ?: $this->fallbackUrl($notification->type);

        if ($notification->type === 'comment' && !empty($notification->target_id)) {
            $comment = (new CommunityComment())->find($notification->target_id);

            if ($comment) {
                $actionUrl = "/ani-mart/public/community/show?id={$comment->post_id}#comment{$comment->id}";
            }
        }

        // Notifications created before user-facing report pages existed can
        // contain an admin-only report URL. Do not strand their recipient on
        // an authorization error; take them to their own report or appeal.
        if (
            strtolower((string) ($user['role'] ?? '')) !== 'admin'
            && str_starts_with($actionUrl, '/ani-mart/public/admin/trust-center/')
        ) {
            $actionUrl = ($notification->title ?? '') === 'Report Rejected'
                ? '/ani-mart/public/reports-complaints#appeals'
                : '/ani-mart/public/reports-complaints#reports';
        }

        if (
            strtolower($user['role'] ?? '') === 'admin'
            && in_array(($notification->title ?? ''), ['New Farmer Application', 'New Seller Application'], true)
        ) {
            $actionUrl = '/ani-mart/public/admin/applications';
        }

        if (
            strtolower($user['role'] ?? '') === 'admin'
            && ($notification->type ?? '') === 'product'
        ) {
            $actionUrl = !empty($notification->target_id)
                ? '/ani-mart/public/admin/products/view?id=' . (int) $notification->target_id
                : '/ani-mart/public/admin/products';
        }

        $this->activateNotificationShop($notification, $user, $actionUrl);

        header('Location: ' . $actionUrl);
        exit;
    }

    public function markAllRead()
    {
        csrf_verify();
        
        $user = Session::get('user');

        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false]);
            exit;
        }

        $db = Database::connection();

        $stmt = $db->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?
        ");

        $stmt->execute([$user['id']]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Create Notification (Helper Method)
    |--------------------------------------------------------------------------
    */

    public static function create(
        $userId,
        $type,
        $title,
        $message,
        $icon = 'bi-bell',
        $color = 'primary',
        $url = null,
        $targetType = null,
        $targetId = null,
        $actionUrl = null
    )
    {
        return NotificationService::send(
            $userId,
            $title,
            $message,
            $type,
            $icon,
            $color,
            $url,
            $targetType,
            $targetId,
            $actionUrl
        );
    }

    private function formatNotification($notification)
    {
        $createdAt = $notification->created_at ?? null;
        $type = $notification->type ?? 'system';
        $actionUrl = ($notification->action_url ?? $notification->url ?? $notification->link ?? null) ?: $this->fallbackUrl($type);

        if ($type === 'comment' && !empty($notification->target_id)) {
            $comment = (new CommunityComment())->find($notification->target_id);

            if ($comment) {
                $actionUrl = "/ani-mart/public/community/show?id={$comment->post_id}#comment{$comment->id}";
            }
        }

        if (($notification->target_type ?? null) === 'community_post' && !empty($notification->target_id)) {
            $actionUrl = "/ani-mart/public/community/show?id=" . (int) $notification->target_id;
        }

        return [
            'id' => (int) ($notification->id ?? 0),
            'user_id' => (int) ($notification->user_id ?? 0),
            'title' => $notification->title ?? '',
            'message' => $notification->message ?? '',
            'type' => $type,
            'type_label' => $this->typeLabel($type),
            'icon' => $notification->icon ?? NotificationService::styleFor($type)['icon'],
            'color' => $notification->color ?? NotificationService::styleFor($type)['color'],
            'url' => ($notification->url ?? $notification->link ?? null) ?: $this->fallbackUrl($type),
            'action_url' => $actionUrl,
            'redirect_url' => in_array($notification->target_type ?? null, ['comment', 'community_post'], true)
                ? $actionUrl
                : '/ani-mart/public/notifications/redirect?id=' . ($notification->id ?? 0),
            'target_type' => $notification->target_type ?? $type,
            'target_id' => (int) ($notification->target_id ?? 0),
            'is_read' => (int) ($notification->is_read ?? 0),
            'created_at' => $createdAt,
            'created_at_timestamp' => isset($notification->created_at_timestamp)
                ? (int) $notification->created_at_timestamp
                : ($createdAt && strtotime($createdAt) ? strtotime($createdAt) * 1000 : null),
            'time_ago' => $this->timeAgo($createdAt)
        ];
    }

    private function fallbackUrl($type)
    {
        $user = Session::get('user');
        $role = strtolower($user['role'] ?? '');

        if ($type === 'order') {
            if ($role === 'farmer') {
                return '/ani-mart/public/farmer/orders';
            }

            if ($role === 'admin') {
                return '/ani-mart/public/dashboard';
            }

            return '/ani-mart/public/orders';
        }

        $urls = [
            'product' => $role === 'farmer'
                ? '/ani-mart/public/farmer/products'
                : '/ani-mart/public/marketplace',
            'review' => $role === 'farmer'
                ? '/ani-mart/public/farmer/products'
                : '/ani-mart/public/orders',
            'harvest' => '/ani-mart/public/marketplace',
            'trust' => $role === 'admin'
                ? '/ani-mart/public/admin/trust-center'
                : '/ani-mart/public/reports-complaints',
            'complaint' => $role === 'admin'
                ? '/ani-mart/public/admin/risk-monitoring'
                : '/ani-mart/public/orders',
            'verification' => $role === 'admin'
                ? '/ani-mart/public/admin/verifications'
                : '/ani-mart/public/farmer/products',
            'message' => '/ani-mart/public/messages',
            'promotion' => '/ani-mart/public/marketplace',
            'system' => '/ani-mart/public/notifications'
        ];

        return $urls[$type] ?? '/ani-mart/public/notifications';
    }

    /**
     * Shop-scoped seller notifications must open in the shop that owns the
     * referenced record, not whichever shop happens to be active already.
     */
    private function activateNotificationShop(object $notification, array $user, string $actionUrl): void
    {
        if (!in_array(strtolower((string) ($user['role'] ?? '')), ['farmer', 'seller'], true)) {
            return;
        }

        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        $shopId = $this->shopIdFromActionUrl($actionUrl);
        if ($shopId > 0 && !SellerProfileService::isOwnedByUser($shopId, $userId)) {
            $shopId = 0;
        }

        $targetType = (string) ($notification->target_type ?? '');
        $targetId = (int) ($notification->target_id ?? 0);

        if ($shopId <= 0 && $targetId > 0) {
            $db = Database::connection();

            if ($targetType === 'product') {
                $stmt = $db->prepare("
                    SELECT products.seller_profile_id
                    FROM products
                    INNER JOIN seller_profiles
                        ON seller_profiles.id = products.seller_profile_id
                    WHERE products.id = ?
                        AND seller_profiles.user_id = ?
                    LIMIT 1
                ");
                $stmt->execute([$targetId, $userId]);
                $shopId = (int) ($stmt->fetchColumn() ?: 0);
            } elseif ($targetType === 'return_request') {
                $stmt = $db->prepare("
                    SELECT return_requests.seller_profile_id
                    FROM return_requests
                    INNER JOIN seller_profiles
                        ON seller_profiles.id = return_requests.seller_profile_id
                    WHERE return_requests.id = ?
                        AND seller_profiles.user_id = ?
                    LIMIT 1
                ");
                $stmt->execute([$targetId, $userId]);
                $shopId = (int) ($stmt->fetchColumn() ?: 0);
            } elseif ($targetType === 'order') {
                $stmt = $db->prepare("
                    SELECT DISTINCT order_items.seller_profile_id
                    FROM order_items
                    INNER JOIN seller_profiles
                        ON seller_profiles.id = order_items.seller_profile_id
                    WHERE order_items.order_id = ?
                        AND seller_profiles.user_id = ?
                ");
                $stmt->execute([$targetId, $userId]);
                $ownedOrderShops = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));

                // Old order notifications did not store a shop ID. They are
                // safe to infer only when this seller has one shop in the order.
                if (count($ownedOrderShops) === 1) {
                    $shopId = $ownedOrderShops[0];
                }
            }
        }

        if ($shopId > 0 && SellerProfileService::isOwnedByUser($shopId, $userId)) {
            $previousShopId = (int) (SellerProfileService::activeShopId() ?? 0);
            SellerProfileService::setActiveShopId($shopId);

            if ($previousShopId > 0 && $previousShopId !== $shopId) {
                $shop = SellerProfileService::findByUserAndId($userId, $shopId);
                Session::flash(
                    'success',
                    'Switched to ' . ($shop->shop_name ?? 'the correct shop') . ' for this notification.'
                );
            }
        }
    }

    private function shopIdFromActionUrl(string $actionUrl): int
    {
        $query = parse_url($actionUrl, PHP_URL_QUERY);
        if (!is_string($query) || $query === '') {
            return 0;
        }

        parse_str($query, $parameters);
        return max(0, (int) ($parameters['shop_id'] ?? 0));
    }

    private function typeLabel($type)
    {
        $labels = [
            'order' => 'Order',
            'product' => 'Product',
            'review' => 'Review',
            'harvest' => 'Harvest',
            'trust' => 'Trust',
            'complaint' => 'Complaint',
            'comment' => 'Comment',
            'verification' => 'Verification',
            'message' => 'Message',
            'promotion' => 'Promotion',
            'system' => 'System'
        ];

        return $labels[$type] ?? 'System';
    }

    private function timeAgo($dateString)
    {
        if (!$dateString) {
            return '';
        }

        $timestamp = strtotime($dateString);

        if (!$timestamp) {
            return '';
        }

        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'Just now';
        }

        if ($diff < 3600) {
            $minutes = (int) floor($diff / 60);
            return $minutes . ' min ago';
        }

        if ($diff < 86400) {
            $hours = (int) floor($diff / 3600);
            return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
        }

        if ($diff < 172800) {
            return 'Yesterday';
        }

        if ($diff < 604800) {
            $days = (int) floor($diff / 86400);
            return $days . ' days ago';
        }

        return date('M j, Y', $timestamp);
    }
}
