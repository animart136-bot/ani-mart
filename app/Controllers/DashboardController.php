<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\FarmerRiskService;
use App\Services\BadgeCalculationService;
use App\Services\DashboardAnalyticsService;
use App\Services\TransparencyAlertService;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Session::get('user');

        if (!$user) {

            header(
                'Location: /ani-mart/public/login'
            );

            exit;
        }

        $role =
            strtolower(
                $user['role'] ?? ''
            );

        $db =
            Database::connection();

        /*
        |--------------------------------------------------------------------------
        | ADMIN DASHBOARD
        |--------------------------------------------------------------------------
        */

        if ($role === 'admin') {

            $applications = 0;
            $farmers = 0;
            $pendingProducts = 0;
            $transparencyAlertCount = 0;

            $recentFarmers = [];
            $lowTrustFarmers = [];
            $recentHarvestChanges = [];
            $riskSummary = [
                'high'   => 0,
                'medium' => 0,
                'low'    => 0
            ];
            $topRiskFarmers = [];
            $recentRiskAlerts = [];
            $badgeDistribution = [];

            try {

                $applications =
                    $db->query("

                        SELECT COUNT(*) total

                        FROM farmer_applications

                        WHERE status IN ('pending', 'pending_official_verification')

                    ")
                    ->fetch()
                    ->total ?? 0;

            } catch (\Throwable $e) {
            }

            try {

                $pendingProducts =
                    $db->query("

                        SELECT COUNT(*) total

                        FROM products

                        WHERE approval_status IN ('pending', 'pending_admin_review', 'needs_correction')

                    ")
                    ->fetch()
                    ->total ?? 0;

            } catch (\Throwable $e) {
            }

            try {

                $farmers =
                    $db->query("

                        SELECT COUNT(*) total

                        FROM seller_profiles

                    ")
                    ->fetch()
                    ->total ?? 0;

            } catch (\Throwable $e) {
            }

            try {

                $transparencyAlertCount = (new TransparencyAlertService())->count();

            } catch (\Throwable $e) {
            }

            try {

                $stmt = $db->query("

                    SELECT
                        seller_profiles.*,
                        users.first_name,
                        users.last_name

                    FROM seller_profiles

                    LEFT JOIN users
                        ON users.id =
                        seller_profiles.user_id

                    ORDER BY seller_profiles.id DESC

                    LIMIT 5

                ");

                $recentFarmers =
                    $stmt->fetchAll();

            } catch (\Throwable $e) {
            }

            try {

                $stmt = $db->query("

                    SELECT
                        seller_profiles.*,
                        users.first_name,
                        users.last_name

                    FROM seller_profiles

                    LEFT JOIN users
                        ON users.id =
                        seller_profiles.user_id

                    WHERE seller_profiles.trust_score < 80

                    ORDER BY seller_profiles.trust_score ASC

                    LIMIT 5

                ");

                $lowTrustFarmers =
                    $stmt->fetchAll();

            } catch (\Throwable $e) {
            }

            try {

                $stmt = $db->query("

                    SELECT
                        product_activity_logs.*,
                        products.name AS product_name,
                        seller_profiles.shop_name,
                        users.first_name,
                        users.last_name

                    FROM product_activity_logs

                    LEFT JOIN products
                        ON products.id =
                        product_activity_logs.product_id

                    LEFT JOIN seller_profiles
                        ON seller_profiles.id =
                        products.seller_profile_id

                    LEFT JOIN users
                        ON users.id =
                        seller_profiles.user_id

                    WHERE product_activity_logs.action =
                        'Harvest Date Changed'

                    ORDER BY product_activity_logs.created_at DESC

                    LIMIT 5

                ");

                $recentHarvestChanges =
                    $stmt->fetchAll();

            } catch (\Throwable $e) {
            }

            try {

                $riskService =
                    new FarmerRiskService();

                $riskSummary =
                    $riskService->summary();

                $topRiskFarmers =
                    $riskService->topSuspicious(5);

                $recentRiskAlerts =
                    $riskService->recentAlerts(5);

            } catch (\Throwable $e) {
            }

            try {

                $badgeService =
                    new BadgeCalculationService();

                $badgeDistribution =
                    $badgeService->getDistribution();

            } catch (\Throwable $e) {
            }

            return $this->view(
                'admin/dashboard/index',
                [
                    'role' => $role,
                    'applications' => $applications,
                    'farmers' => $farmers,
                    'pendingProducts' => $pendingProducts,
                    'recentFarmers' => $recentFarmers,
                    'lowTrustFarmers' => $lowTrustFarmers,
                    'recentHarvestChanges' => $recentHarvestChanges,
                    'transparencyAlertCount' => $transparencyAlertCount,
                    'riskSummary' => $riskSummary,
                    'topRiskFarmers' => $topRiskFarmers,
                    'recentRiskAlerts' => $recentRiskAlerts,
                    'badgeDistribution' => $badgeDistribution,
                    'pageCss' => '/ani-mart/public/assets/css/pages/admin-dashboard.css',
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CUSTOMER DASHBOARD
        |
        | Sellers use Seller Centre for shop management, so their general
        | dashboard represents their activity as an Ani-Mart customer.
        |--------------------------------------------------------------------------
        */

        $stats = [

            'following' => 0,
            'orders' => 0,
            'total_spent' => 0,
            'pending_orders' => 0

        ];

        $recentOrders = [];

        try {
            $stmt = $db->prepare("

                SELECT COUNT(*) total

                FROM seller_followers

                WHERE customer_id = ?

            ");

            $stmt->execute([
                $user['id']
            ]);

            $stats['following'] =
                $stmt->fetch()->total ?? 0;
        } catch (\Throwable $e) {
            $stats['following'] = 0;
        }

        $stmt = $db->prepare("

            SELECT COUNT(*) total

            FROM orders

            WHERE user_id = ?

        ");

        $stmt->execute([
            $user['id']
        ]);

        $stats['orders'] =
            $stmt->fetch()->total ?? 0;

        $stmt = $db->prepare("

            SELECT COUNT(*) total

            FROM orders

            WHERE user_id = ?

            AND status = 'pending'

        ");

        $stmt->execute([
            $user['id']
        ]);

        $stats['pending_orders'] =
            $stmt->fetch()->total ?? 0;

        $stmt = $db->prepare("

            SELECT SUM(total_amount) total

            FROM orders

            WHERE user_id = ?

        ");

        $stmt->execute([
            $user['id']
        ]);

        $stats['total_spent'] =
            $stmt->fetch()->total ?? 0;

        $stmt = $db->prepare(" 

            SELECT *

            FROM orders

            WHERE user_id = ?

            ORDER BY id DESC

            LIMIT 5

        ");

        $stmt->execute([
            $user['id']
        ]);

        $recentOrders =
            $stmt->fetchAll();

        $dashboardAnalytics = (new DashboardAnalyticsService())->forRole('customer', (int) $user['id']);

        return $this->view(
            'dashboard/index',
            [
                'role' => 'customer',
                'stats' => $stats,
                'recentOrders' => $recentOrders,
                'dashboardAnalytics' => $dashboardAnalytics,
                'pageCss' => '/ani-mart/public/assets/css/pages/dashboard.css',
            ]
        );
    }
}
