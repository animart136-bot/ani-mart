<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\CloudinaryService;
use App\Services\NotificationService;
use App\Models\Report;
use App\Models\ReportEvidence;

class ReportController extends Controller
{
    public function create()
    {
        $user = Session::get('user');

        if (!$user) {
            $this->redirect('/ani-mart/public/login');
        }

        $db = Database::connection();
        $targets = [
            'product' => $db->query("SELECT p.id, CONCAT(p.name, ' — ', COALESCE(sp.shop_name, 'Unknown farm')) AS label FROM products p LEFT JOIN seller_profiles sp ON sp.id = p.seller_profile_id WHERE p.is_active = 1 ORDER BY p.name ASC LIMIT 100")->fetchAll(),
            'seller' => $db->query("SELECT id, shop_name AS label FROM seller_profiles ORDER BY shop_name ASC LIMIT 100")->fetchAll(),
            'customer' => $db->prepare("SELECT id, TRIM(CONCAT(first_name, ' ', last_name)) AS label FROM users WHERE role = 'customer' AND id != ? ORDER BY first_name, last_name LIMIT 100"),
        ];

        $targets['customer']->execute([$user['id']]);
        $targets['customer'] = $targets['customer']->fetchAll();

        return $this->view('trust-center/reports/create', [
            'title' => 'File a Report',
            'bodyClass' => 'report-create-page',
            'reportTargets' => $targets,
            'pageCss' => '/ani-mart/public/assets/css/trust-safety.css',
        ]);
    }

    public function store()
    {
        csrf_verify();
        
        $user = Session::get('user');

        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $reporterId = $user['id'];
        $targetType = trim($_POST['target_type'] ?? '');
        $targetId = intval($_POST['target_id'] ?? 0) ?: null;
        $targetReference = trim($_POST['target_reference'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority = trim($_POST['priority'] ?? 'medium');

        $allowedTypes = [
            'product',
            'seller',
            'customer',
            'message',
            'bug',
            'feature'
        ];

        $allowedPriorities = ['low', 'medium', 'high', 'critical'];

        if (!$targetType || !in_array($targetType, $allowedTypes, true)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid report type.']);
            exit;
        }

        if ($targetReference === '' || $reason === '' || $description === '') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please identify what you are reporting and complete all fields.']);
            exit;
        }

        $priority = in_array($priority, $allowedPriorities, true) ? $priority : 'medium';

        $reportModel = new Report();
        $reportId = $reportModel->create([
            'reporter_id' => $reporterId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'reason' => $reason,
            'description' => mb_substr("Reported item: {$targetReference}\n\n{$description}", 0, 2000),
            'priority' => $priority
        ]);

        $evidenceUrls = [];
        if (!empty($_FILES['evidence_images'])) {
            $uploadFiles = $_FILES['evidence_images'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $cloudinary = new CloudinaryService();
            $count = count($uploadFiles['name']);
            $saved = 0;

            for ($index = 0; $index < $count && $saved < 5; $index++) {
                $error = $uploadFiles['error'][$index] ?? UPLOAD_ERR_NO_FILE;
                if ($error !== UPLOAD_ERR_OK) {
                    continue;
                }
                $extension = strtolower(pathinfo($uploadFiles['name'][$index], PATHINFO_EXTENSION));
                if (!in_array($extension, $allowedExtensions, true)) {
                    continue;
                }
                $tmpName = $uploadFiles['tmp_name'][$index];
                if (!is_uploaded_file($tmpName)) {
                    continue;
                }
                $result = $cloudinary->uploadSensitive([
                    'name' => $uploadFiles['name'][$index] ?? '',
                    'tmp_name' => $tmpName,
                    'size' => $uploadFiles['size'][$index] ?? 0,
                    'type' => $uploadFiles['type'][$index] ?? '',
                    'error' => $uploadFiles['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                ], 'ani-mart/reports', [
                    'entity_type' => 'report',
                    'entity_id' => (int) $reportId,
                    'owner_user_id' => (int) $reporterId,
                    'retention_days' => 365,
                ]);
                if (!empty($result['secure_url'])) {
                    $evidenceUrls[] = $result;
                    $saved++;
                }
            }

            foreach ($evidenceUrls as $item) {
                (new ReportEvidence())->create($reportId, $item['secure_url'], $item['public_id']);
            }
        }

        $submissionLabel = $targetType === 'bug'
            ? 'Bug Report'
            : ($targetType === 'feature' ? 'Feature Request' : 'Trust & Safety Report');

        NotificationService::notifyAdmins(
            "New {$submissionLabel}",
            sprintf('%s submitted a new %s.', $user['first_name'] ?? 'A user', strtolower($submissionLabel)),
            'trust',
            '/ani-mart/public/admin/trust-center/reports/show?id=' . $reportId,
            'report',
            $reportId,
            '/ani-mart/public/admin/trust-center/reports/show?id=' . $reportId
        );

        Session::flash('success', 'Your report was submitted successfully. The Trust & Safety team will review it shortly.');

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'report_id' => $reportId,
            'redirect_url' => '/ani-mart/public/reports-complaints'
        ]);
        exit;
    }
}
