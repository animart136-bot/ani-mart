<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\AuditLogService;
use App\Services\CloudinaryService;

class MediaController extends Controller
{
    public function show($id): void
    {
        $user = Session::get('user');
        if (!$user || empty($user['id'])) {
            http_response_code(401);
            echo 'Authentication required.';
            return;
        }

        $service = new CloudinaryService();
        $asset = $service->findById((int) $id);

        if (!$asset || ($asset->visibility ?? '') !== CloudinaryService::VISIBILITY_SENSITIVE || ($asset->status ?? '') !== 'active') {
            http_response_code(404);
            echo 'Protected image not found.';
            return;
        }

        if (!$this->canView($asset, $user)) {
            AuditLogService::log(
                'SENSITIVE_IMAGE_ACCESS_DENIED',
                'image_asset',
                (int) $asset->id,
                ['description' => 'Unauthorized protected image access attempt.'],
                (int) $user['id'],
                '/ani-mart/public/media/assets/' . (int) $asset->id,
                'failed'
            );
            http_response_code(403);
            echo 'You are not authorized to view this image.';
            return;
        }

        if (strtolower((string) ($user['role'] ?? '')) === 'admin') {
            AuditLogService::log(
                'SENSITIVE_IMAGE_VIEWED',
                'image_asset',
                (int) $asset->id,
                ['description' => 'Administrator opened protected image evidence.'],
                (int) $user['id'],
                '/ani-mart/public/media/assets/' . (int) $asset->id
            );
        }

        header('Cache-Control: private, no-store, max-age=0');
        header('Referrer-Policy: no-referrer');
        header('Location: ' . $service->signedDownloadUrl($asset, 300));
        exit;
    }

    private function canView(object $asset, array $user): bool
    {
        $userId = (int) $user['id'];
        if (strtolower((string) ($user['role'] ?? '')) === 'admin' || (int) ($asset->owner_user_id ?? 0) === $userId) {
            return true;
        }

        $entityId = (int) ($asset->entity_id ?? 0);
        if ($entityId <= 0) {
            return false;
        }

        $db = Database::connection();
        switch ((string) ($asset->entity_type ?? '')) {
            case 'complaint':
                $stmt = $db->prepare('SELECT 1 FROM complaints c LEFT JOIN seller_profiles sp ON sp.id = c.seller_profile_id WHERE c.id = ? AND (c.customer_id = ? OR sp.user_id = ?) LIMIT 1');
                $stmt->execute([$entityId, $userId, $userId]);
                return (bool) $stmt->fetchColumn();

            case 'return_request':
                $stmt = $db->prepare('SELECT 1 FROM return_requests WHERE id = ? AND (customer_id = ? OR seller_user_id = ?) LIMIT 1');
                $stmt->execute([$entityId, $userId, $userId]);
                return (bool) $stmt->fetchColumn();

            case 'report':
                $stmt = $db->prepare('SELECT 1 FROM reports WHERE id = ? AND reporter_id = ? LIMIT 1');
                $stmt->execute([$entityId, $userId]);
                return (bool) $stmt->fetchColumn();

            case 'farmer_application':
                $stmt = $db->prepare('SELECT 1 FROM farmer_applications WHERE id = ? AND user_id = ? LIMIT 1');
                $stmt->execute([$entityId, $userId]);
                return (bool) $stmt->fetchColumn();

            case 'order':
                $stmt = $db->prepare('SELECT 1 FROM orders o WHERE o.id = ? AND (o.user_id = ? OR EXISTS (SELECT 1 FROM order_items oi INNER JOIN seller_profiles sp ON sp.id = oi.seller_profile_id WHERE oi.order_id = o.id AND sp.user_id = ?)) LIMIT 1');
                $stmt->execute([$entityId, $userId, $userId]);
                return (bool) $stmt->fetchColumn();
        }

        return false;
    }
}
