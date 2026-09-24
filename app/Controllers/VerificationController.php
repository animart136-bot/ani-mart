<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\AuditLogService;
use App\Services\BadgeCalculationService;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Index - Verification Management Page
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $user = Session::get('user');

        if (!$user || strtolower($user['role'] ?? '') !== 'admin') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $db = Database::connection();
        $badgeService = new BadgeCalculationService();

        // Get all sellers with their badge status
        $stmt = $db->query("
            SELECT 
                sp.*,
                u.first_name,
                u.last_name,
                u.email,
                u.is_verified,
                (
                    SELECT COUNT(DISTINCT o.id)
                    FROM orders o
                    INNER JOIN order_items oi ON oi.order_id = o.id
                    WHERE oi.seller_profile_id = sp.id
                    AND o.status IN ('completed', 'delivered')
                ) AS completed_orders,
                (
                    SELECT COUNT(*)
                    FROM complaints
                    WHERE seller_profile_id = sp.id
                ) AS total_complaints,
                (
                    SELECT COUNT(*)
                    FROM complaints
                    WHERE seller_profile_id = sp.id
                    AND status IN ('pending', 'under_review', 'needs_more_evidence', 'waiting_farmer_response')
                ) AS unresolved_complaints,
                (
                    SELECT COUNT(*)
                    FROM products
                    WHERE seller_profile_id = sp.id
                    AND harvest_photo IS NOT NULL
                    AND harvest_photo != ''
                    AND is_active = 1
                ) AS products_with_harvest
            FROM seller_profiles sp
            INNER JOIN users u ON u.id = sp.user_id
            ORDER BY sp.verification_badge DESC, sp.created_at DESC
        ");

        $sellers = $stmt->fetchAll();

        // Get badge distribution
        $distribution = $badgeService->getDistribution();

        return $this->view(
            'admin/verifications/index',
            compact('sellers', 'distribution')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Promote Badge
    |--------------------------------------------------------------------------
    */

    public function approveShop()
    {
        $admin = Session::get('user');

        if (!$admin || strtolower($admin['role'] ?? '') !== 'admin') {
            return $this->redirect('/ani-mart/public/login');
        }

        csrf_verify();

        $sellerId = (int) ($_POST['seller_id'] ?? 0);
        if (!$sellerId) {
            Session::flash('error', 'Invalid shop request.');
            return $this->redirect('/ani-mart/public/admin/verifications');
        }

        $db = Database::connection();
        $stmt = $db->prepare("UPDATE seller_profiles SET status = 'approved' WHERE id = ? AND status = 'pending'");
        $stmt->execute([$sellerId]);

        if ($stmt->rowCount() < 1) {
            Session::flash('error', 'Shop was not found or is no longer pending.');
            return $this->redirect('/ani-mart/public/admin/verifications');
        }

        AuditLogService::log(
            'seller_shop_approved',
            'seller_profile',
            $sellerId,
            ['status' => 'approved'],
            (int) $admin['id'],
            '/ani-mart/public/admin/verifications/approve-shop',
            'success'
        );

        Session::flash('success', 'Shop approved successfully.');
        return $this->redirect('/ani-mart/public/admin/verifications');
    }

    public function promote()
    {
        $user = Session::get('user');

        if (!$user || strtolower($user['role'] ?? '') !== 'admin') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        csrf_verify();

        $sellerId = $_POST['seller_id'] ?? 0;
        $newBadge = $_POST['badge'] ?? '';

        if (!$sellerId || !in_array($newBadge, ['new', 'verified', 'trusted'])) {
            Session::flash('error', 'Invalid request');
            header('Location: /ani-mart/public/admin/verifications');
            exit;
        }

        $db = Database::connection();

        if ($newBadge === 'verified') {
            $stmt = $db->prepare("SELECT government_id FROM seller_profiles WHERE id = ? AND government_id IS NOT NULL AND government_id <> '' LIMIT 1");
            $stmt->execute([$sellerId]);
            if (!$stmt->fetch()) {
                Session::flash('error', 'The Local Farmer badge requires submitted farmer documentation.');
                header('Location: /ani-mart/public/admin/verifications');
                exit;
            }
        }

        if ($newBadge === 'trusted' && (new BadgeCalculationService())->calculateBadge($sellerId) !== BadgeCalculationService::BADGE_TRUSTED) {
            Session::flash('error', 'Trusted Seller requires a trust score above 80 and no unresolved complaints or reports.');
            header('Location: /ani-mart/public/admin/verifications');
            exit;
        }

        $stmt = $db->prepare("
            UPDATE seller_profiles
            SET verification_badge = ?
            WHERE id = ?
        ");

        $stmt->execute([$newBadge, $sellerId]);

        AuditLogService::log(
            'seller_badge_promoted',
            'seller_profile',
            (int) $sellerId,
            ['badge' => $newBadge],
            (int) ($user['id'] ?? 0),
            '/ani-mart/public/admin/verifications/promote',
            'success'
        );

        Session::flash('success', 'Badge promoted successfully');
        header('Location: /ani-mart/public/admin/verifications');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Demote Badge
    |--------------------------------------------------------------------------
    */

    public function demote()
    {
        $user = Session::get('user');

        if (!$user || strtolower($user['role'] ?? '') !== 'admin') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        csrf_verify();

        $sellerId = $_POST['seller_id'] ?? 0;
        $newBadge = $_POST['badge'] ?? '';

        if (!$sellerId || !in_array($newBadge, ['new', 'verified', 'trusted'])) {
            Session::flash('error', 'Invalid request');
            header('Location: /ani-mart/public/admin/verifications');
            exit;
        }

        $db = Database::connection();

        $stmt = $db->prepare("
            UPDATE seller_profiles
            SET verification_badge = ?
            WHERE id = ?
        ");

        $stmt->execute([$newBadge, $sellerId]);

        AuditLogService::log(
            'seller_badge_demoted',
            'seller_profile',
            (int) $sellerId,
            ['badge' => $newBadge],
            (int) ($user['id'] ?? 0),
            '/ani-mart/public/admin/verifications/demote',
            'success'
        );

        Session::flash('success', 'Badge demoted successfully');
        header('Location: /ani-mart/public/admin/verifications');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Recalculate All Badges
    |--------------------------------------------------------------------------
    */

    public function recalculate()
    {
        $user = Session::get('user');

        if (!$user || strtolower($user['role'] ?? '') !== 'admin') {
            header('Location: /ani-mart/public/login');
            exit;
        }

csrf_verify();

        $badgeService = new BadgeCalculationService();
        $updated = $badgeService->recalculateAll();

        Session::flash('success', "Recalculated badges for {$updated} sellers");
        header('Location: /ani-mart/public/admin/verifications');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Recalculate Single Badge
    |--------------------------------------------------------------------------
    */

    public function recalculateSingle()
    {
        $user = Session::get('user');

        if (!$user || strtolower($user['role'] ?? '') !== 'admin') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        csrf_verify();

        $sellerId = $_POST['seller_id'] ?? 0;

        if (!$sellerId) {
            Session::flash('error', 'Invalid request');
            header('Location: /ani-mart/public/admin/verifications');
            exit;
        }

        $badgeService = new BadgeCalculationService();
        $newBadge = $badgeService->calculateBadge($sellerId);

        $db = Database::connection();

        $stmt = $db->prepare("
            UPDATE seller_profiles
            SET verification_badge = ?
            WHERE id = ?
        ");

        $stmt->execute([$newBadge, $sellerId]);

        AuditLogService::log(
            'seller_badge_recalculated',
            'seller_profile',
            (int) $sellerId,
            ['badge' => $newBadge],
            (int) ($user['id'] ?? 0),
            '/ani-mart/public/admin/verifications/recalculate-single',
            'success'
        );

        Session::flash('success', 'Badge recalculated successfully');
        header('Location: /ani-mart/public/admin/verifications');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | View Seller Details
    |--------------------------------------------------------------------------
    */

    public function viewSeller()
    {
        $user = Session::get('user');

        if (!$user || strtolower($user['role'] ?? '') !== 'admin') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $sellerId = $_GET['id'] ?? 0;

        if (!$sellerId) {
            header('Location: /ani-mart/public/admin/verifications');
            exit;
        }

        $db = Database::connection();

        $stmt = $db->prepare("
            SELECT 
                sp.*,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                u.phone_encrypted,
                u.created_at AS user_created_at
            FROM seller_profiles sp
            INNER JOIN users u ON u.id = sp.user_id
            WHERE sp.id = ?
            LIMIT 1
        ");

        $stmt->execute([$sellerId]);
        $seller = $stmt->fetch();

        if (!$seller) {
            Session::flash('error', 'Seller not found');
            header('Location: /ani-mart/public/admin/verifications');
            exit;
        }

        // Get seller's orders
        $stmt = $db->prepare("
            SELECT 
                o.*,
                COUNT(DISTINCT oi.id) AS item_count
            FROM orders o
            INNER JOIN order_items oi ON oi.order_id = o.id
            WHERE oi.seller_profile_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT 20
        ");

        $stmt->execute([$sellerId]);
        $orders = $stmt->fetchAll();

        // Get seller's complaints
        $stmt = $db->prepare("
            SELECT *
            FROM complaints
            WHERE seller_profile_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ");

        $stmt->execute([$sellerId]);
        $complaints = $stmt->fetchAll();

        // Get seller's products with harvest info
        $stmt = $db->prepare("
            SELECT 
                p.*,
                c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.seller_profile_id = ?
            ORDER BY p.created_at DESC
            LIMIT 20
        ");

        $stmt->execute([$sellerId]);
        $products = $stmt->fetchAll();

        return $this->view(
            'admin/verification_detail/index',
            compact('seller', 'orders', 'complaints', 'products')
        );
    }
}
