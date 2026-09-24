<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Notification;

class NotificationService
{
    public static function resolveProductDecision(
        int $productId,
        int $sellerUserId,
        string $productName,
        string $decision,
        string $note = ''
    ): void {
        $db = Database::connection();
        $columns = $db->query('SHOW COLUMNS FROM notifications')->fetchAll();
        $columnNames = array_map(static fn($column) => $column->Field, $columns);

        $details = [
            'approved' => [
                'title' => 'Product Approved',
                'seller_message' => 'Your product "' . $productName . '" was approved and is now visible in the marketplace.',
                'admin_message' => 'Product "' . $productName . '" has been approved. The pending review is complete.',
                'icon' => 'bi-check-circle',
                'color' => 'success',
                'seller_url' => '/ani-mart/public/farmer/products',
            ],
            'rejected' => [
                'title' => 'Product Not Approved',
                'seller_message' => 'Your product "' . $productName . '" was not approved.' . ($note !== '' ? ' Admin note: ' . $note : ' Review the listing before submitting it again.'),
                'admin_message' => 'Product "' . $productName . '" has been rejected. The pending review is complete.',
                'icon' => 'bi-x-circle',
                'color' => 'danger',
                'seller_url' => '/ani-mart/public/farmer/products/edit?id=' . $productId,
            ],
            'needs_correction' => [
                'title' => 'Product Needs Correction',
                'seller_message' => 'Your product "' . $productName . '" needs correction.' . ($note !== '' ? ' Admin note: ' . $note : ' Review the product information before resubmitting.'),
                'admin_message' => 'Corrections were requested for product "' . $productName . '". The pending review is complete until the seller resubmits it.',
                'icon' => 'bi-pencil-square',
                'color' => 'warning',
                'seller_url' => '/ani-mart/public/farmer/products/edit?id=' . $productId,
            ],
            'superseded' => [
                'title' => 'Previous Product Submission Closed',
                'seller_message' => 'A newer submission for "' . $productName . '" was approved. This previous pending submission was closed to prevent duplicate marketplace listings.',
                'admin_message' => 'The previous pending submission for "' . $productName . '" was replaced by a newer approved submission. No further review is needed.',
                'icon' => 'bi-check2-all',
                'color' => 'secondary',
                'seller_url' => '/ani-mart/public/farmer/products',
            ],
        ][$decision] ?? null;

        if (!$details) {
            return;
        }

        if (!in_array('target_type', $columnNames, true) || !in_array('target_id', $columnNames, true)) {
            self::send(
                $sellerUserId,
                $details['title'],
                $details['seller_message'],
                'product',
                $details['icon'],
                $details['color'],
                $details['seller_url']
            );
            return;
        }

        $adminUrl = '/ani-mart/public/admin/products/view?id=' . $productId;
        $adminSet = ['n.title = ?', 'n.message = ?', 'n.is_read = 1', 'n.created_at = NOW()'];
        $adminValues = [$details['title'], $details['admin_message']];
        foreach (['icon' => $details['icon'], 'color' => $details['color'], 'url' => $adminUrl, 'action_url' => $adminUrl] as $column => $value) {
            if (in_array($column, $columnNames, true)) {
                $adminSet[] = 'n.' . $column . ' = ?';
                $adminValues[] = $value;
            }
        }
        $adminValues[] = $productId;
        $adminUpdate = $db->prepare("UPDATE notifications n INNER JOIN users u ON u.id = n.user_id
            SET " . implode(', ', $adminSet) . "
            WHERE n.target_type = 'product' AND n.target_id = ? AND LOWER(u.role) = 'admin' AND n.title LIKE '%Pending Approval%'");
        $adminUpdate->execute($adminValues);

        $sellerSet = ['title = ?', 'message = ?', 'is_read = 0', 'created_at = NOW()'];
        $sellerValues = [$details['title'], $details['seller_message']];
        foreach (['icon' => $details['icon'], 'color' => $details['color'], 'url' => $details['seller_url'], 'action_url' => $details['seller_url']] as $column => $value) {
            if (in_array($column, $columnNames, true)) {
                $sellerSet[] = $column . ' = ?';
                $sellerValues[] = $value;
            }
        }
        array_push($sellerValues, $sellerUserId, $productId);
        $sellerUpdate = $db->prepare("UPDATE notifications
            SET " . implode(', ', $sellerSet) . "
            WHERE user_id = ? AND target_type = 'product' AND target_id = ? AND title LIKE '%Pending Approval%'");
        $sellerUpdate->execute($sellerValues);

        if ($sellerUpdate->rowCount() === 0) {
            self::send(
                $sellerUserId,
                $details['title'],
                $details['seller_message'],
                'product',
                $details['icon'],
                $details['color'],
                $details['seller_url'],
                'product',
                $productId,
                $details['seller_url']
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Send Notification
    |--------------------------------------------------------------------------
    */
    public static function send(
        $userId,
        $title,
        $message,
        $type = 'system',
        $icon = null,
        $color = null,
        $url = null,
        $targetType = null,
        $targetId = null,
        $actionUrl = null
    )
    {
        // The notifications table stores a fixed set of categories.  Keep an
        // unexpected caller value from turning a completed business action
        // into a database exception (for example, a visual color such as
        // "warning" passed where the category is expected).
        $validTypes = ['order', 'product', 'review', 'harvest', 'trust', 'complaint', 'verification', 'promotion', 'system', 'message', 'comment'];
        if (!in_array($type, $validTypes, true)) {
            $type = 'system';
        }

        $notification =
            new Notification();

        $style = self::styleFor($type);

        return $notification->create([

            'user_id' => $userId,

            'title' => $title,

            'message' => $message,

            'type' => $type,

            'icon' => $icon ?? $style['icon'],

            'color' => $color ?? $style['color'],

            'url' => $url,

            'target_type' => $targetType ?? $type,

            'target_id' => $targetId ? (int) $targetId : null,

            'action_url' => $actionUrl ?? $url
        ]);
    }

    /**
     * Send a community notification when the recipient has not disabled them.
     */
    public static function community(
        $userId,
        $title,
        $message,
        $icon,
        $color,
        $url,
        $targetType,
        $targetId
    ) {
        $statement = Database::connection()->prepare(
            'SELECT community_notifications FROM message_settings WHERE user_id = ? LIMIT 1'
        );
        $statement->execute([(int) $userId]);
        $setting = $statement->fetchColumn();

        if ($setting !== false && (int) $setting === 0) {
            return false;
        }

        return self::send(
            $userId,
            $title,
            $message,
            'promotion',
            $icon,
            $color,
            $url,
            $targetType,
            $targetId,
            $url
        );
    }

    public static function order($userId, $title, $message, $url = '/ani-mart/public/orders', $targetType = null, $targetId = null, $actionUrl = null)
    {
        return self::send($userId, $title, $message, 'order', 'bi-bag-check', 'success', $url, $targetType, $targetId, $actionUrl);
    }

    public static function harvest($userId, $title, $message, $url = '/ani-mart/public/marketplace', $targetType = null, $targetId = null, $actionUrl = null)
    {
        return self::send($userId, $title, $message, 'harvest', 'bi-flower1', 'success', $url, $targetType, $targetId, $actionUrl);
    }

    public static function trust($userId, $title, $message, $url = '/ani-mart/public/admin/transparency', $targetType = null, $targetId = null, $actionUrl = null)
    {
        return self::send($userId, $title, $message, 'trust', 'bi-shield-check', 'info', $url, $targetType, $targetId, $actionUrl);
    }

    public static function complaint($userId, $title, $message, $url = '/ani-mart/public/orders', $targetType = null, $targetId = null, $actionUrl = null)
    {
        return self::send($userId, $title, $message, 'complaint', 'bi-exclamation-triangle', 'danger', $url, $targetType, $targetId, $actionUrl);
    }

    /**
     * Notify every active administrator instead of relying on a seeded user ID.
     *
     * Admin accounts differ between installations, so using a fixed ID can leave
     * the Trust & Resolution team unaware of newly submitted cases.
     */
    public static function notifyAdmins(
        $title,
        $message,
        $type = 'system',
        $url = null,
        $targetType = null,
        $targetId = null,
        $actionUrl = null
    ) {
        $statement = Database::connection()->prepare(
            "SELECT id FROM users WHERE LOWER(role) = 'admin'"
        );
        $statement->execute();

        foreach ($statement->fetchAll() as $admin) {
            self::send(
                $admin->id,
                $title,
                $message,
                $type,
                null,
                null,
                $url,
                $targetType,
                $targetId,
                $actionUrl
            );
        }
    }

    public static function review($userId, $title, $message, $url = '/ani-mart/public/farmer/products', $targetType = null, $targetId = null, $actionUrl = null)
    {
        return self::send($userId, $title, $message, 'review', 'bi-star-fill', 'warning', $url, $targetType, $targetId, $actionUrl);
    }

    public static function smartTransparencyUpdate($userId, $message, $url = '/ani-mart/public/admin/transparency')
    {
        return self::trust(
            $userId,
            'Transparency Update',
            $message,
            $url
        );
    }

    public static function styleFor($type)
    {
        $styles = [
            'order' => ['icon' => 'bi-cart-check', 'color' => 'success'],
            'product' => ['icon' => 'bi-basket', 'color' => 'primary'],
            'review' => ['icon' => 'bi-star-fill', 'color' => 'warning'],
            'harvest' => ['icon' => 'bi-flower1', 'color' => 'success'],
            'trust' => ['icon' => 'bi-shield-check', 'color' => 'info'],
            'complaint' => ['icon' => 'bi-exclamation-triangle', 'color' => 'danger'],
            'comment' => ['icon' => 'bi-chat-left-text', 'color' => 'info'],
            'verification' => ['icon' => 'bi-patch-check', 'color' => 'success'],
            'message' => ['icon' => 'bi-chat-left-text', 'color' => 'info'],
            'promotion' => ['icon' => 'bi-megaphone', 'color' => 'primary'],
            'system' => ['icon' => 'bi-bell', 'color' => 'secondary']
        ];

        return $styles[$type] ?? $styles['system'];
    }
}
