<?php

namespace App\Controllers;

use App\Services\TrustCasePolicy;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Helpers\UploadValidator;
use App\Services\AuditLogService;
use App\Services\CloudinaryService;
use App\Services\NotificationService;
use App\Services\ReturnService;
use App\Services\OrderWorkflowSchemaService;
use DomainException;
use Throwable;

class ReturnController extends Controller
{
    public function __construct()
    {
        (new OrderWorkflowSchemaService())->ensure();
    }

    public function create()
    {
        $user = $this->customerUser();
        $orderId = (int) ($_GET['order_id'] ?? 0);
        $db = Database::connection();
        $stmt = $db->prepare("SELECT o.*, sp.shop_name
            FROM orders o
            INNER JOIN order_items oi ON oi.order_id = o.id
            INNER JOIN seller_profiles sp ON sp.id = oi.seller_profile_id
            LEFT JOIN return_requests rr ON rr.order_id = o.id
            WHERE o.id = ? AND o.user_id = ? AND o.status IN ('delivered','completed') AND rr.id IS NULL
            LIMIT 1");
        $stmt->execute([$orderId, $user['id']]);
        $order = $stmt->fetch();
        if (!$order || !\App\Services\MarketplaceRulesService::canRequestReturn((string) $order->status, (string) ($order->delivered_at ?? $order->completed_at ?? ''))) {
            Session::flash('error', 'This order is no longer eligible for a return because the 24-hour return window has expired.');
            return $this->redirect('/ani-mart/public/orders');
        }

        $items = $db->prepare('SELECT oi.*, p.name AS product_name, p.image FROM order_items oi INNER JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ? ORDER BY oi.id');
        $items->execute([$orderId]);
        return $this->view('returns/create', ['order' => $order, 'items' => $items->fetchAll(), 'title' => 'Request a Return']);
    }

    public function store()
    {
        csrf_verify();
        $user = $this->customerUser();
        $evidenceFiles = $this->normalizedFiles($_FILES['evidence_images'] ?? []);
        if ($evidenceFiles === [] || !(new UploadValidator())->validate($evidenceFiles[0])['ok']) {
            Session::flash('error', 'Please attach at least one valid JPG, PNG, or WebP evidence image.');
            return $this->redirect('/ani-mart/public/customer/returns/create?order_id=' . (int) ($_POST['order_id'] ?? 0));
        }

        try {
            $return = (new ReturnService())->create(
                (int) ($_POST['order_id'] ?? 0),
                (int) $user['id'],
                (string) ($_POST['reason'] ?? ''),
                is_array($_POST['quantities'] ?? null) ? $_POST['quantities'] : []
            );
            $this->storeEvidence((int) $return->return_id, (int) $user['id'], 'customer', $evidenceFiles, (string) ($_POST['evidence_caption'] ?? ''));

            $number = $return->order_number ?? $return->id;
            $this->notifyBoth($return, 'Return Request Submitted',
                "Your return request for order #{$number}, totaling ₱" . number_format((float) $return->requested_refund_amount, 2) . ', was recorded.',
                'New Return Request', "A customer requested a return for order #{$number}. Review the products, evidence, and refund amount.");
            AuditLogService::log('RETURN_REQUESTED', 'return_request', (int) $return->return_id, ['order_id' => (int) $return->id, 'requested_refund' => (float) $return->requested_refund_amount], (int) $user['id'], '/ani-mart/public/customer/returns/store', 'success');
            Session::flash('success', 'Your return request and evidence were submitted.');
            return $this->redirect('/ani-mart/public/customer/returns/show?id=' . (int) $return->return_id);
        } catch (DomainException $exception) {
            Session::flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            Session::flash('error', 'The return request could not be recorded. Please try again.');
        }
        return $this->redirect('/ani-mart/public/customer/returns/create?order_id=' . (int) ($_POST['order_id'] ?? 0));
    }

    public function customerShow()
    {
        $user = $this->customerUser();
        $data = $this->returnData((int) ($_GET['id'] ?? 0), 'customer', (int) $user['id']);
        if (!$data) return $this->redirect('/ani-mart/public/orders');
        return $this->view('returns/show', $data + ['role' => 'customer', 'title' => 'Return & Refund Record']);
    }

    public function farmerShow()
    {
        $user = $this->farmerUser();
        $data = $this->returnData((int) ($_GET['id'] ?? 0), 'farmer', (int) $user['id']);
        if (!$data) return $this->redirect('/ani-mart/public/farmer/orders');
        return $this->view('returns/show', $data + ['role' => 'farmer', 'title' => 'Return & Refund Record']);
    }

    public function decide()
    {
        csrf_verify();
        $user = $this->farmerUser();
        $returnId = (int) ($_POST['return_id'] ?? 0);
        try {
            $amountRaw = trim((string) ($_POST['approved_refund_amount'] ?? ''));
            $return = (new ReturnService())->decide($returnId, (int) $user['id'], strtolower((string) ($_POST['decision'] ?? '')), (string) ($_POST['decision_reason'] ?? ''), $amountRaw === '' ? null : (float) $amountRaw);
            $this->storeEvidence($returnId, (int) $user['id'], 'farmer', $this->normalizedFiles($_FILES['evidence_images'] ?? []), (string) ($_POST['evidence_caption'] ?? 'Decision evidence'));
            $approved = $return->status === 'approved';
            NotificationService::send((int) $return->customer_id, $approved ? 'Return Approved' : 'Return Rejected',
                ($approved ? 'The seller approved your return.' : 'The seller rejected your return.') . ' Reason: ' . $return->decision_reason,
                'order', $approved ? 'bi-check-circle' : 'bi-x-circle', $approved ? 'success' : 'danger', '/ani-mart/public/customer/returns/show?id=' . $returnId, 'return_request', $returnId);
            NotificationService::send((int) $user['id'], 'Return Decision Recorded', 'Your decision and reason were added to the return history.', 'order', 'bi-journal-check', 'info', '/ani-mart/public/farmer/returns/show?id=' . $returnId, 'return_request', $returnId);
            AuditLogService::log('RETURN_' . strtoupper((string) $return->status), 'return_request', $returnId, ['reason' => $return->decision_reason, 'approved_refund' => $return->approved_refund_amount], (int) $user['id'], '/ani-mart/public/farmer/returns/decision', 'success');
            Session::flash('success', 'The return decision was recorded.');
        } catch (DomainException $exception) {
            Session::flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            Session::flash('error', 'The return decision could not be recorded.');
        }
        return $this->redirect('/ani-mart/public/farmer/returns/show?id=' . $returnId);
    }

    public function resolve()
    {
        csrf_verify();
        $user = $this->farmerUser();
        $returnId = (int) ($_POST['return_id'] ?? 0);
        try {
            $return = (new ReturnService())->resolve($returnId, (int) $user['id'], is_array($_POST['received_quantities'] ?? null) ? $_POST['received_quantities'] : [], (float) ($_POST['refunded_amount'] ?? 0), (string) ($_POST['refund_method'] ?? 'none'), (string) ($_POST['refund_reference'] ?? ''), (string) ($_POST['resolution_note'] ?? ''));
            $this->storeEvidence($returnId, (int) $user['id'], 'farmer', $this->normalizedFiles($_FILES['evidence_images'] ?? []), (string) ($_POST['evidence_caption'] ?? 'Resolution evidence'));
            NotificationService::send((int) $return->customer_id, 'Return Resolved', 'Your return was resolved. Refunded amount: ₱' . number_format((float) $return->refunded_amount, 2) . ' via ' . str_replace('_', ' ', $return->refund_method) . '.', 'order', 'bi-cash-coin', 'success', '/ani-mart/public/customer/returns/show?id=' . $returnId, 'return_request', $returnId);
            NotificationService::send((int) $user['id'], 'Return Resolution Recorded', 'Returned quantities and the refund record were saved.', 'order', 'bi-journal-check', 'info', '/ani-mart/public/farmer/returns/show?id=' . $returnId, 'return_request', $returnId);
            AuditLogService::log('RETURN_RESOLVED', 'return_request', $returnId, ['refunded_amount' => (float) $return->refunded_amount, 'refund_method' => $return->refund_method], (int) $user['id'], '/ani-mart/public/farmer/returns/resolve', 'success');
            (new CloudinaryService())->scheduleEntityDeletion('return_request', $returnId, TrustCasePolicy::retentionDays('return_request'));
            Session::flash('success', 'Returned products and refund details were recorded.');
        } catch (DomainException $exception) {
            Session::flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            Session::flash('error', 'The return resolution could not be recorded.');
        }
        return $this->redirect('/ani-mart/public/farmer/returns/show?id=' . $returnId);
    }

    private function returnData(int $id, string $role, int $userId): ?array
    {
        $db = Database::connection();
        $column = $role === 'farmer' ? 'rr.seller_user_id' : 'rr.customer_id';
        $stmt = $db->prepare("SELECT rr.*, o.order_number, o.status AS order_status, sp.shop_name,
                CONCAT(u.first_name, ' ', u.last_name) AS customer_name
            FROM return_requests rr INNER JOIN orders o ON o.id = rr.order_id
            INNER JOIN seller_profiles sp ON sp.id = rr.seller_profile_id
            INNER JOIN users u ON u.id = rr.customer_id
            WHERE rr.id = ? AND {$column} = ? LIMIT 1");
        $stmt->execute([$id, $userId]);
        $return = $stmt->fetch();
        if (!$return) return null;
        $return = hydrate_sensitive_fields($return, 'return_requests', [
            'reason', 'refund_reference', 'decision_reason', 'resolution_note',
        ]);
        $items = $db->prepare('SELECT * FROM return_request_items WHERE return_request_id = ? ORDER BY id');
        $items->execute([$id]);
        $evidence = $db->prepare('SELECT * FROM return_evidence WHERE return_request_id = ? ORDER BY created_at, id');
        $evidence->execute([$id]);
        $history = $db->prepare('SELECT * FROM return_resolution_history WHERE return_request_id = ? ORDER BY created_at, id');
        $history->execute([$id]);
        $historyRows = array_map(
            fn($row) => hydrate_sensitive_fields($row, 'return_resolution_history', ['note']),
            $history->fetchAll()
        );
        return ['return' => $return, 'items' => $items->fetchAll(), 'evidence' => $evidence->fetchAll(), 'history' => $historyRows];
    }

    private function storeEvidence(int $returnId, int $userId, string $role, array $files, string $caption): void
    {
        $cloudinary = new CloudinaryService();
        $db = Database::connection();
        $insert = $db->prepare('INSERT INTO return_evidence (return_request_id, user_id, uploader_role, image_url, caption, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        foreach (array_slice($files, 0, 5) as $file) {
            $uploaded = $cloudinary->uploadSensitive($file, 'ani-mart/return-evidence', [
                'entity_type' => 'return_request',
                'entity_id' => $returnId,
                'owner_user_id' => $userId,
                'retention_days' => TrustCasePolicy::retentionDays('return_request'),
            ]);
            if ($uploaded && !empty($uploaded['secure_url'])) {
                $insert->execute([$returnId, $userId, $role, $uploaded['secure_url'], mb_substr(trim($caption), 0, 500)]);
            }
        }
    }

    private function normalizedFiles(array $upload): array
    {
        if (!isset($upload['name'])) return [];
        $names = is_array($upload['name']) ? $upload['name'] : [$upload['name']];
        $files = [];
        foreach ($names as $index => $name) {
            $error = is_array($upload['error'] ?? null) ? ($upload['error'][$index] ?? UPLOAD_ERR_NO_FILE) : ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) continue;
            $files[] = ['name' => $name, 'tmp_name' => is_array($upload['tmp_name']) ? ($upload['tmp_name'][$index] ?? '') : $upload['tmp_name'], 'size' => is_array($upload['size']) ? ($upload['size'][$index] ?? 0) : $upload['size'], 'type' => is_array($upload['type']) ? ($upload['type'][$index] ?? '') : $upload['type'], 'error' => $error];
        }
        return $files;
    }

    private function notifyBoth(object $return, string $customerTitle, string $customerMessage, string $sellerTitle, string $sellerMessage): void
    {
        NotificationService::send((int) $return->user_id, $customerTitle, $customerMessage, 'order', 'bi-arrow-return-left', 'info', '/ani-mart/public/customer/returns/show?id=' . $return->return_id, 'return_request', $return->return_id);
        NotificationService::send((int) $return->seller_user_id, $sellerTitle, $sellerMessage, 'order', 'bi-arrow-return-left', 'warning', '/ani-mart/public/farmer/returns/show?id=' . $return->return_id, 'return_request', $return->return_id);
    }

    private function customerUser(): array
    {
        $user = Session::get('user');
        if (!$user || !in_array(strtolower((string) ($user['role'] ?? '')), ['customer', 'farmer'], true)) {
            header('Location: /ani-mart/public/login'); exit;
        }
        return $user;
    }

    private function farmerUser(): array
    {
        $user = Session::get('user');
        if (!$user || strtolower((string) ($user['role'] ?? '')) !== 'farmer') {
            header('Location: /ani-mart/public/login'); exit;
        }
        return $user;
    }
}
