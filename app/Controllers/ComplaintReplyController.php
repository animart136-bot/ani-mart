<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\Complaint;
use App\Models\ComplaintReply;
use App\Models\SellerProfile;
use App\Services\NotificationService;
use App\Services\SellerProfileService;

class ComplaintReplyController extends Controller
{
    public function store()
    {
        
        csrf_verify();

        $user = Session::get('user');

        if (!$user) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $complaintId = (int) ($_POST['complaint_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        if (!$complaintId || $message === '') {
            Session::flash('error', 'Please add a message before posting.');
            header('Location: /ani-mart/public/customer/complaints');
            exit;
        }

        $complaint = (new Complaint())->find($complaintId);

        if (!$complaint) {
            Session::flash('error', 'Unable to find the dispute.');
            header('Location: /ani-mart/public/customer/complaints');
            exit;
        }

        $authorized = false;
        $recipientId = null;
        $recipientRole = null;

        if ($user['role'] === 'customer' && $complaint->customer_id === $user['id']) {
            $authorized = true;
            $recipientId = $complaint->seller_user_id;
            $recipientRole = 'farmer';
        }

        if ($user['role'] === 'farmer') {
            if ((int) $complaint->seller_user_id === (int) $user['id']) {
                $authorized = true;
                $recipientId = $complaint->customer_id;
                $recipientRole = 'customer';
            }
        }

        if (!$authorized) {
            Session::flash('error', 'You are not authorized to reply to this dispute.');
            header('Location: /ani-mart/public/customer/complaints');
            exit;
        }

        (new ComplaintReply())->create($complaintId, $user['id'], mb_substr($message, 0, 1000));

        $db = Database::connection();
        $db->prepare("UPDATE complaints SET updated_at = NOW() WHERE id = ?")->execute([$complaintId]);

        if ($recipientId) {
            $redirectUrl = $recipientRole === 'farmer'
                ? '/ani-mart/public/farmer/complaints/show?id=' . $complaintId
                : '/ani-mart/public/customer/complaints/show?id=' . $complaintId;

            NotificationService::send(
                $recipientId,
                'Dispute Conversation Update',
                ($user['role'] === 'customer')
                    ? "The customer posted a new reply on dispute #{$complaint->id}."
                    : "The farmer replied to dispute #{$complaint->id}.",
                'complaint',
                'bi-chat-left-text',
                'warning',
                $redirectUrl,
                'complaint',
                $complaintId,
                $redirectUrl
            );
        }

        NotificationService::notifyAdmins(
            'Dispute Conversation Update',
            ($user['role'] === 'customer')
                ? "The customer posted a new reply on dispute #{$complaint->id}."
                : "The seller posted a new reply on dispute #{$complaint->id}.",
            'complaint',
            '/ani-mart/public/admin/complaints/show?id=' . $complaintId,
            'complaint',
            $complaintId,
            '/ani-mart/public/admin/complaints/show?id=' . $complaintId
        );

        $redirect = $user['role'] === 'farmer'
            ? '/ani-mart/public/farmer/complaints/show?id=' . $complaintId
            : '/ani-mart/public/customer/complaints/show?id=' . $complaintId;

        header('Location: ' . $redirect);
        exit;
    }
}
