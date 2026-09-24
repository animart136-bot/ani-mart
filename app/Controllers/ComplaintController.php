<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\Complaint;
use App\Models\ComplaintEvidence;
use App\Models\ComplaintReply;
use App\Services\CloudinaryService;
use App\Services\NotificationService;
use App\Services\SellerProfileService;

class ComplaintController extends Controller
{
    public function customerIndex()
    {
        $user = Session::get('user');

        if (!$user || $user['role'] !== 'customer') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $complaints = (new Complaint())->forCustomer($user['id']);

        return $this->view('complaints/customer/index', [
            'title' => 'My Disputes',
            'bodyClass' => 'customer-complaints-page',
            'complaints' => $complaints
        ]);
    }

    public function customerCreate()
    {
        $user = Session::get('user');

        if (!$user || $user['role'] !== 'customer') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $complaint = new Complaint();
        $eligibleOrders = $complaint->eligibleOrdersForCustomer($user['id']);
        $selectedOrderId = (int) ($_GET['order_id'] ?? 0);

        return $this->view('complaints/customer/create', [
            'title' => 'File a Dispute',
            'bodyClass' => 'customer-complaint-create-page',
            'orders' => $eligibleOrders,
            'selectedOrderId' => $selectedOrderId
        ]);
    }

    public function customerStore()
    {
        csrf_verify();
        
        $user = Session::get('user');

        if (!$user || $user['role'] !== 'customer') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $orderId = (int) ($_POST['order_id'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';

        if (!$orderId || $category === '' || $description === '') {
            Session::flash('error', 'Please complete all complaint fields.');
            return $this->redirect('/ani-mart/public/customer/complaints/create');
        }

        $db = Database::connection();
        $stmt = $db->prepare("SELECT o.*, oi.seller_profile_id FROM orders o INNER JOIN order_items oi ON oi.order_id = o.id WHERE o.id = ? AND o.user_id = ? LIMIT 1");
        $stmt->execute([$orderId, $user['id']]);
        $order = $stmt->fetch();

        if (!$order || !in_array($order->status, ['delivered', 'completed'], true)) {
            Session::flash('error', 'This order is not eligible for a dispute.');
            return $this->redirect('/ani-mart/public/customer/complaints/create');
        }

        $existing = $db->prepare("SELECT id FROM complaints WHERE order_id = ? AND status NOT IN ('rejected','closed') LIMIT 1");
        $existing->execute([$orderId]);

        if ($existing->fetch()) {
            Session::flash('error', 'A dispute already exists for this order.');
            return $this->redirect('/ani-mart/public/customer/complaints');
        }

        $complaintId = null;
        $complaintModel = new Complaint();
        $complaintModel->create([
            'order_id' => $orderId,
            'customer_id' => $user['id'],
            'seller_profile_id' => $order->seller_profile_id,
            'category' => $category,
            'description' => mb_substr($description, 0, 1200),
            'priority' => in_array($priority, ['low', 'medium', 'high'], true) ? $priority : 'medium'
        ]);

        $complaintId = $db->lastInsertId();

        if (!empty($_FILES['evidence_images'])) {
            $cloudinary = new CloudinaryService();

            foreach ($_FILES['evidence_images']['tmp_name'] as $index => $tmpName) {
                $error = $_FILES['evidence_images']['error'][$index] ?? UPLOAD_ERR_NO_FILE;
                if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($tmpName)) {
                    continue;
                }

                $upload = $cloudinary->uploadSensitive([
                    'name' => $_FILES['evidence_images']['name'][$index] ?? '',
                    'tmp_name' => $tmpName,
                    'size' => $_FILES['evidence_images']['size'][$index] ?? 0,
                    'type' => $_FILES['evidence_images']['type'][$index] ?? '',
                    'error' => $error,
                ], 'ani-mart/complaint-evidence', [
                    'entity_type' => 'complaint',
                    'entity_id' => (int) $complaintId,
                    'owner_user_id' => (int) $user['id'],
                    'retention_days' => 365,
                ]);

                if ($upload && !empty($upload['secure_url'])) {
                    (new ComplaintEvidence())->create($complaintId, $user['id'], 'customer', $upload['secure_url']);
                }
            }
        }

        NotificationService::send(
            $user['id'],
            'Dispute Submitted',
            "Your dispute for order #{$order->order_number} has been submitted. Our resolution team will review it soon.",
            'complaint',
            'bi-exclamation-triangle',
            'danger',
            '/ani-mart/public/customer/complaints',
            'complaint',
            $complaintId,
            '/ani-mart/public/customer/complaints'
        );

        $sellerStatement = $db->prepare(
            'SELECT user_id FROM seller_profiles WHERE id = ? LIMIT 1'
        );
        $sellerStatement->execute([$order->seller_profile_id]);
        $seller = $sellerStatement->fetch();

        if ($seller) {
            NotificationService::send(
                $seller->user_id,
                'New Customer Dispute',
                "A customer filed dispute #{$complaintId} for order #{$order->order_number}. Please review and respond.",
                'complaint',
                'bi-exclamation-triangle',
                'warning',
                '/ani-mart/public/farmer/complaints/show?id=' . $complaintId,
                'complaint',
                $complaintId,
                '/ani-mart/public/farmer/complaints/show?id=' . $complaintId
            );
        }

        NotificationService::notifyAdmins(
            'New Dispute Submitted',
            sprintf(
                '%s filed dispute #%d for order %s.',
                $user['first_name'] ?? 'A customer',
                $complaintId,
                $order->order_number
            ),
            'complaint',
            '/ani-mart/public/admin/complaints/show?id=' . $complaintId,
            'complaint',
            $complaintId,
            '/ani-mart/public/admin/complaints/show?id=' . $complaintId
        );

        Session::flash('success', 'Your dispute was filed successfully.');
        return $this->redirect('/ani-mart/public/customer/complaints');
    }

    public function customerShow()
    {
        $user = Session::get('user');

        if (!$user || $user['role'] !== 'customer') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $complaintId = (int) ($_GET['id'] ?? 0);
        $complaint = (new Complaint())->find($complaintId);

        if (!$complaint || $complaint->customer_id !== $user['id']) {
            header('Location: /ani-mart/public/customer/complaints');
            exit;
        }

        $evidence = (new ComplaintEvidence())->findByComplaint($complaintId);
        $replies = (new ComplaintReply())->findByComplaint($complaintId);

        return $this->view('complaints/customer/show', [
            'title' => "Dispute #{$complaint->id}",
            'bodyClass' => 'customer-complaint-show-page',
            'complaint' => $complaint,
            'evidence' => $evidence,
            'replies' => $replies
        ]);
    }

    public function farmerIndex()
    {
        $user = Session::get('user');

        if (!$user || $user['role'] !== 'farmer') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $seller = SellerProfileService::activeShop($user['id']);

        if (!$seller) {
            header('Location: /ani-mart/public/seller-center/shops');
            exit;
        }

        $complaints = (new Complaint())->forSellerUser($user['id']);

        return $this->view('complaints/farmer/index', [
            'title' => 'Disputes Against My Farm',
            'bodyClass' => 'farmer-complaints-page',
            'complaints' => $complaints,
            'seller' => $seller
        ]);
    }

    public function farmerShow()
    {
        $user = Session::get('user');

        if (!$user || $user['role'] !== 'farmer') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $seller = SellerProfileService::activeShop($user['id']);

        if (!$seller) {
            header('Location: /ani-mart/public/seller-center/shops');
            exit;
        }

        $complaintId = (int) ($_GET['id'] ?? 0);
        $complaint = (new Complaint())->find($complaintId);

        if (!$complaint || (int) $complaint->seller_user_id !== (int) $user['id']) {
            header('Location: /ani-mart/public/farmer/complaints');
            exit;
        }

        $evidence = (new ComplaintEvidence())->findByComplaint($complaintId);
        $replies = (new ComplaintReply())->findByComplaint($complaintId);

        return $this->view('complaints/farmer/show', [
            'title' => "Dispute #{$complaint->id}",
            'bodyClass' => 'farmer-complaint-show-page',
            'complaint' => $complaint,
            'evidence' => $evidence,
            'replies' => $replies,
            'seller' => $seller
        ]);
    }
}
