<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\AuditLogService;
use App\Services\CloudinaryService;

class MediaAdminController extends Controller
{
    public function destroy(): void
    {
        csrf_verify();
        $user = Session::get('user');
        if (!$user || strtolower((string) ($user['role'] ?? '')) !== 'admin') {
            http_response_code(403);
            echo '403 Unauthorized';
            return;
        }

        $assetId = (int) ($_POST['asset_id'] ?? 0);
        $reason = trim((string) ($_POST['reason'] ?? ''));
        if ($assetId <= 0 || $reason === '') {
            Session::flash('error', 'Select an image and provide a permanent-deletion reason.');
            $this->redirect((string) ($_SERVER['HTTP_REFERER'] ?? '/ani-mart/public/admin'));
        }

        $service = new CloudinaryService();
        $asset = $service->findById($assetId);
        if (!$asset) {
            Session::flash('error', 'Image asset not found.');
            $this->redirect((string) ($_SERVER['HTTP_REFERER'] ?? '/ani-mart/public/admin'));
        }

        $result = $service->delete((string) $asset->public_id, ['delivery_type' => (string) $asset->delivery_type]);
        $deleted = in_array((string) ($result['result'] ?? ''), ['ok', 'not found'], true);
        if ($deleted) {
            $this->removeDatabaseReference($asset);
        }

        AuditLogService::log(
            $deleted ? 'IMAGE_PERMANENTLY_DELETED' : 'IMAGE_PERMANENT_DELETE_FAILED',
            'image_asset',
            $assetId,
            ['description' => $reason, 'new_value' => ['entity_type' => $asset->entity_type, 'entity_id' => $asset->entity_id]],
            (int) $user['id'],
            '/ani-mart/public/admin/media/delete',
            $deleted ? 'success' : 'failed'
        );

        Session::flash($deleted ? 'success' : 'error', $deleted ? 'The image was permanently deleted.' : 'Cloudinary could not delete the image. It has been queued for retry.');
        $this->redirect((string) ($_SERVER['HTTP_REFERER'] ?? '/ani-mart/public/admin'));
    }

    private function removeDatabaseReference(object $asset): void
    {
        $db = Database::connection();
        $rawUrl = (string) $asset->secure_url;
        $protectedUrl = '/ani-mart/public/media/assets/' . (int) $asset->id;
        $entityId = (int) ($asset->entity_id ?? 0);

        switch ((string) ($asset->entity_type ?? '')) {
            case 'report':
                $db->prepare('DELETE FROM report_evidence WHERE report_id = ? AND (public_id = ? OR image_url IN (?, ?))')
                    ->execute([$entityId, $asset->public_id, $rawUrl, $protectedUrl]);
                break;
            case 'complaint':
                $db->prepare('DELETE FROM complaint_evidence WHERE complaint_id = ? AND image_url IN (?, ?)')
                    ->execute([$entityId, $rawUrl, $protectedUrl]);
                break;
            case 'return_request':
                $db->prepare('DELETE FROM return_evidence WHERE return_request_id = ? AND image_url IN (?, ?)')
                    ->execute([$entityId, $rawUrl, $protectedUrl]);
                break;
            case 'community_post':
                $db->prepare('DELETE FROM community_post_images WHERE post_id = ? AND image_url IN (?, ?)')
                    ->execute([$entityId, $rawUrl, $protectedUrl]);
                break;
            case 'seller_profile':
                $db->prepare('DELETE FROM farm_profile_images WHERE seller_profile_id = ? AND image_path IN (?, ?)')
                    ->execute([$entityId, $rawUrl, $protectedUrl]);
                $db->prepare('UPDATE seller_profiles SET farm_banner = NULL WHERE id = ? AND farm_banner IN (?, ?)')
                    ->execute([$entityId, $rawUrl, $protectedUrl]);
                $db->prepare('UPDATE seller_profiles SET farm_logo = NULL WHERE id = ? AND farm_logo IN (?, ?)')
                    ->execute([$entityId, $rawUrl, $protectedUrl]);
                break;
            case 'review':
                $db->prepare('UPDATE reviews SET review_photo = NULL WHERE id = ? AND review_photo IN (?, ?)')
                    ->execute([$entityId, $rawUrl, $protectedUrl]);
                break;
            case 'user_profile':
                $db->prepare('UPDATE users SET profile_image = NULL WHERE id = ? AND profile_image IN (?, ?)')
                    ->execute([$entityId, $rawUrl, $protectedUrl]);
                break;
            case 'farmer_application':
                $db->prepare('UPDATE farmer_applications SET rsbsa_card_path = NULL, rsbsa_card_path_encrypted = NULL WHERE id = ? AND rsbsa_card_path IN (?, ?)')
                    ->execute([$entityId, $rawUrl, $protectedUrl]);
                break;
            case 'order':
                $db->prepare('UPDATE orders SET proof_image = NULL WHERE id = ? AND proof_image IN (?, ?)')
                    ->execute([$entityId, $rawUrl, $protectedUrl]);
                break;
        }
    }
}
