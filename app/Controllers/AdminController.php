<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\AdminAnalyticsService;
use App\Services\AuditLogService;
use App\Services\FarmerRiskService;
use App\Controllers\NotificationController;
use App\Services\SellerApprovalService;
use App\Services\FarmerIdAiVerificationService;
use App\Models\FarmerVerificationLog;
use App\Services\PriceReferenceService;
use App\Services\TransparencyAlertService;
use App\Services\SensitiveDataCipher;
use App\Services\CloudinaryService;
use App\Services\AuthGuard;
use App\Services\SellerApplicationRulesService;

class AdminController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Applications
    |--------------------------------------------------------------------------
    */

    public function applications()
    {
        $this->requireAdmin();

        $db = Database::connection();

        $stmt = $db->query("

            SELECT

                farmer_applications.*,

                users.first_name,
                users.last_name,
                users.email

            FROM farmer_applications

            LEFT JOIN users
                ON users.id = farmer_applications.user_id

            ORDER BY submitted_at DESC

        ");

        $applications =
            $stmt->fetchAll();

        foreach ($applications as $application) {
            foreach ([
                'first_name',
                'middle_name',
                'last_name',
                'name_extension',
                'sex',
                'address',
                'contact_number',
                'date_of_birth',
                'rsbsa_number',
                'extracted_name',
                'extracted_rsbsa_number',
                'rsbsa_card_path',
                'ai_analysis',
                'ai_review_summary',
                'ai_review_notes',
                'rsbsa_verification_notes',
                'verification_reason',
                'rsbsa_reference_number',
                'ai_verification_note',
            ] as $field) {
                $application->{$field} = sensitive_value(
                    $application,
                    $field,
                    'farmer_applications.' . $field,
                    $application->{$field} ?? ''
                );
            }
        }

        $historyByApplication = [];
        $historyStmt = $db->prepare('SELECT verification_type, verification_status, notes, verified_at, admin_id FROM verification_logs WHERE application_id = ? ORDER BY created_at DESC');
        foreach ($applications as $application) {
            $historyStmt->execute([(int) $application->id]);
            $historyByApplication[$application->id] = $historyStmt->fetchAll();
        }

        return $this->view(
            'admin/applications/index',
            compact('applications', 'historyByApplication')
        );
    }

    public function priceReferenceData()
    {
        $this->requireAdmin();
        return $this->view('admin/reference-data/prices', (new PriceReferenceService())->pageData($_GET));
    }

    public function storePriceReferenceData()
    {
        $this->requireAdmin();
        csrf_verify();
        try {
            $result = (new PriceReferenceService())->addManual($_POST);
            Session::flash('success', $result['action'] === 'updated'
                ? 'Today\'s matching reference price was updated.'
                : 'The reference price was saved.');
        } catch (\InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
        }
        return $this->redirect('/ani-mart/public/admin/reference-data/prices');
    }

    public function updatePriceReferenceData()
    {
        $this->requireAdmin();
        csrf_verify();
        $admin = $this->requireAdminUser();
        try {
            $id = (int) ($_POST['id'] ?? 0);
            $result = (new PriceReferenceService())->updateManual($id, $_POST);
            AuditLogService::log('price_reference_updated', 'price_reference', (int) $result['new_record']['id'], [
                'old_value' => $result['old_record'],
                'new_value' => $result['new_record'],
                'description' => (string) $result['new_record']['commodity'],
            ], (int) ($admin['id'] ?? 0), '/ani-mart/public/admin/reference-data/prices/update');
            Session::flash('success', 'The reference price and its update time were changed.');
        } catch (\InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
        }
        return $this->redirect('/ani-mart/public/admin/reference-data/prices');
    }

    public function deletePriceReferenceData()
    {
        $this->requireAdmin();
        csrf_verify();
        $admin = $this->requireAdminUser();
        try {
            $record = (new PriceReferenceService())->deleteManual((int) ($_POST['id'] ?? 0));
            AuditLogService::log('price_reference_deleted', 'price_reference', (int) $record['id'], [
                'old_value' => $record,
                'description' => (string) $record['commodity'],
            ], (int) ($admin['id'] ?? 0), '/ani-mart/public/admin/reference-data/prices/delete');
            Session::flash('success', 'The reference price was deleted.');
        } catch (\InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
        }
        return $this->redirect('/ani-mart/public/admin/reference-data/prices');
    }

    /*
    |--------------------------------------------------------------------------
    | Approve Application
    |--------------------------------------------------------------------------
    */

    public function approve()
    {
        $this->requireAdmin();
        csrf_verify();

        $id =
            $_POST['id'] ?? 0;

        $db =
            Database::connection();

        $stmt = $db->prepare("

            SELECT *

            FROM farmer_applications

            WHERE id = ?

            LIMIT 1

        ");

        $stmt->execute([$id]);

        $application =
            $stmt->fetch();

        if (!$application) {

            header(
                'Location: /ani-mart/public/admin/applications'
            );

            exit;
        }

        $user = Session::get('user');

        (new SellerApprovalService())->approve($application);

        AuditLogService::log(
            'seller_application_approved',
            'seller_application',
            (int) $application->id,
            ['user_id' => (int) $application->user_id, 'status' => 'approved'],
            (int) ($user['id'] ?? 0),
            '/ani-mart/public/admin/applications/approve',
            'success'
        );

        header(
            'Location: /ani-mart/public/admin/applications'
        );

        exit;
    }

    public function reject()
    {
        $this->requireAdmin();
        csrf_verify();

        $id = $_POST['id'] ?? 0;

        $db = Database::connection();

        $stmt = $db->prepare("SELECT * FROM farmer_applications WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $application = $stmt->fetch();

        if (!$application) {
            header('Location: /ani-mart/public/admin/applications');
            exit;
        }

        $update = $db->prepare("UPDATE farmer_applications SET status = 'rejected' WHERE id = ?");
        $update->execute([$id]);

        NotificationController::create(
            $application->user_id,
            'verification',
            'Seller Application Rejected',
            'Your seller application was not approved. Please review your submission and try again.',
            'bi-x-circle',
            'danger',
            '/ani-mart/public/dashboard'
        );

        AuditLogService::log(
            'seller_application_rejected',
            'seller_application',
            (int) $application->id,
            ['user_id' => (int) $application->user_id, 'status' => 'rejected'],
            (int) ($this->requireAdminUser()['id'] ?? 0),
            '/ani-mart/public/admin/applications/reject',
            'success'
        );

        header('Location: /ani-mart/public/admin/applications');
        exit;
    }

    /** Re-run the AI recommendation without changing the admin application decision. */
    public function reverify()
    {
        $this->requireAdmin();
        csrf_verify();

        $id = (int) ($_POST['id'] ?? 0);
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM farmer_applications WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $application = $stmt->fetch();

        if (!$application || empty($application->rsbsa_card_path)) {
            Session::flash('error', 'This application has no RSBSA card available for AI verification.');
            header('Location: /ani-mart/public/admin/applications');
            exit;
        }

        $verificationImageUrl = (new CloudinaryService())->resolveForServer((string) $application->rsbsa_card_path);
        $verification = (new FarmerIdAiVerificationService())->verifyFarmerCard($verificationImageUrl);
        $nameMatchStatus = SellerApplicationRulesService::rsbsaNameMatchStatus(
            sensitive_value($application, 'first_name', 'farmer_applications.first_name', $application->first_name ?? null),
            sensitive_value($application, 'middle_name', 'farmer_applications.middle_name', $application->middle_name ?? null),
            sensitive_value($application, 'last_name', 'farmer_applications.last_name', $application->last_name ?? null),
            $verification['ocr_data']['full_name'] ?? null
        );
        $nameReviewStatus = match ($nameMatchStatus) {
            'match' => 'reference_match',
            'review' => 'name_review_required',
            'mismatch' => 'information_mismatch',
            default => 'name_not_readable',
        };
        $nameReviewSummary = match ($nameMatchStatus) {
            'match' => 'The name read from the RSBSA card matches the applicant name.',
            'review' => 'The first and last names appear to match, but the middle name or card formatting needs administrator review.',
            'mismatch' => 'The first or last name read from the RSBSA card does not match the seller application. RSBSA recognition was not granted.',
            default => 'The card name could not be read clearly enough to compare with the seller application. Administrator review is required.',
        };
        $nameMatchScore = $nameMatchStatus === 'match' ? 100 : ($nameMatchStatus === 'review' ? 70 : 0);
        $update = $db->prepare('UPDATE farmer_applications SET ai_status = ?, confidence_score = ?, verification_reason = ?, verified_at = ?, extracted_name = ?, extracted_rsbsa_number = ?, extracted_card_type = ?, extracted_agency = ?, ai_analysis = ?, ai_review_status = ?, ai_review_summary = ?, ai_match_score = ?, ai_reviewed_at = NOW() WHERE id = ?');
        $update->execute([
            $verification['ai_status'],
            $verification['confidence_score'],
            $verification['verification_reason'],
            $verification['ai_status'] === 'verified' ? date('Y-m-d H:i:s') : null,
            $verification['ocr_data']['full_name'],
            $verification['ocr_data']['rsbsa_number'],
            $verification['ocr_data']['card_type'],
            $verification['ocr_data']['agency'],
            json_encode($verification['detected_features']),
            $nameReviewStatus,
            $nameReviewSummary,
            $nameMatchScore,
            $application->id,
        ]);

        $cipher = new SensitiveDataCipher();
        $encryptedUpdate = $db->prepare(
            'UPDATE farmer_applications SET extracted_name_encrypted = ?, extracted_rsbsa_number_encrypted = ?, verification_reason_encrypted = ?, ai_analysis_encrypted = ? WHERE id = ?'
        );
        $encryptedUpdate->execute([
            $cipher->encrypt($verification['ocr_data']['full_name'], 'farmer_applications.extracted_name'),
            $cipher->encrypt($verification['ocr_data']['rsbsa_number'], 'farmer_applications.extracted_rsbsa_number'),
            $cipher->encrypt($verification['verification_reason'] ?? null, 'farmer_applications.verification_reason'),
            $cipher->encrypt(json_encode($verification['detected_features']), 'farmer_applications.ai_analysis'),
            $application->id,
        ]);
        FarmerVerificationLog::record((int) $application->id, $verification);

        // Applying as a seller remains allowed. A confirmed name match only
        // affects the optional RSBSA badge and starting trust score.
        if ($nameMatchStatus === 'match') {
            $application->extracted_name = $verification['ocr_data']['full_name'];
            (new SellerApprovalService())->approve($application);
        }

        AuditLogService::log(
            'farmer_application_ai_reverified',
            'farmer_application',
            (int) $application->id,
            ['ai_status' => $verification['ai_status'] ?? null, 'recommendation' => $verification['recommendation'] ?? null, 'name_match_status' => $nameMatchStatus],
            (int) ($this->requireAdminUser()['id'] ?? 0),
            '/ani-mart/public/admin/applications/reverify',
            'success'
        );

        if (($verification['ai_status'] ?? 'pending') === 'pending') {
            Session::flash(
                'error',
                'AI verification could not run: ' . ($verification['verification_reason'] ?? 'The AI provider is unavailable.')
            );
        } else {
            Session::flash('success', 'AI verification was re-run: ' . $verification['recommendation'] . '. ' . $nameReviewSummary);
        }
        header('Location: /ani-mart/public/admin/applications');
        exit;
    }

    public function recordOfficialVerification()
    {
        $admin = $this->requireAdminUser();
        csrf_verify();

        $applicationId = (int) ($_POST['id'] ?? 0);
        $verificationStatus = trim((string) ($_POST['verification_status'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $allowedStatuses = ['verified', 'information_mismatch', 'registration_not_found', 'needs_additional_information'];

        if (!$applicationId || !in_array($verificationStatus, $allowedStatuses, true)) {
            Session::flash('error', 'Select a valid official RSBSA verification result.');
            return $this->redirect('/ani-mart/public/admin/applications');
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM farmer_applications WHERE id = ? LIMIT 1');
        $stmt->execute([$applicationId]);
        $application = $stmt->fetch();

        if (!$application) {
            Session::flash('error', 'Application not found.');
            return $this->redirect('/ani-mart/public/admin/applications');
        }

        $applicationStatus = $verificationStatus === 'verified'
            ? 'approved'
            : (($application->status ?? '') === 'approved' ? 'approved' : 'pending');

        $update = $db->prepare('UPDATE farmer_applications SET status = ?, application_status = ?, rsbsa_verification_status = ?, rsbsa_verified_by = ?, rsbsa_verified_at = NOW(), rsbsa_verification_notes = ? WHERE id = ?');
        $update->execute([$applicationStatus, $applicationStatus, $verificationStatus, (int) $admin['id'], $notes, $applicationId]);
        $db->prepare('UPDATE farmer_applications SET rsbsa_verification_notes_encrypted = ? WHERE id = ?')
            ->execute([
                (new SensitiveDataCipher())->encrypt($notes, 'farmer_applications.rsbsa_verification_notes'),
                $applicationId,
            ]);
        FarmerVerificationLog::recordManual($applicationId, (int) $admin['id'], 'rsbsa', $verificationStatus, $notes);

        if ($applicationStatus === 'approved') {
            if ($verificationStatus === 'verified') {
                $application->rsbsa_verification_status = 'verified';
                (new SellerApprovalService())->approve($application);
            }
        } else {
            NotificationController::create(
                $application->user_id,
                'verification',
                'RSBSA Verification Update',
                'Your optional RSBSA verification was not completed. Your regular seller application remains eligible for administrator approval.',
                'bi-info-circle',
                'warning',
                '/ani-mart/public/farmer/apply'
            );
        }

        AuditLogService::log('official_rsbsa_verification_recorded', 'farmer_application', $applicationId, ['verification_status' => $verificationStatus, 'application_status' => $applicationStatus], (int) $admin['id'], '/ani-mart/public/admin/applications/official-verification', 'success');
        Session::flash('success', 'Official RSBSA verification recorded. Application status: ' . ucwords(str_replace('_', ' ', $applicationStatus)) . '.');
        return $this->redirect('/ani-mart/public/admin/applications');
    }

    /*
    |--------------------------------------------------------------------------
    | Farmers
    |--------------------------------------------------------------------------
    */

    public function farmers()
    {
        $this->requireAdmin();

        $db = Database::connection();

        $stmt = $db->query("

            SELECT

                seller_profiles.*,

                users.first_name,
                users.last_name,
                users.email

            FROM seller_profiles

            LEFT JOIN users
                ON users.id = seller_profiles.user_id

            ORDER BY seller_profiles.id DESC

        ");

        $farmers =
            $stmt->fetchAll();

        return $this->view(
            'admin/farmers/index',
            compact('farmers')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Transparency Center
    |--------------------------------------------------------------------------
    */

    public function transparency()
    {
        $this->requireAdmin();

        $db =
            Database::connection();

        $farmerId = filter_input(INPUT_GET, 'farmer_id', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);

        $farmerId = $farmerId !== false && $farmerId !== null
            ? (int) $farmerId
            : null;

        $selectedFarmer = null;
        $farmerProducts = [];

        if ($farmerId !== null) {
            $farmerStmt = $db->prepare("
                SELECT
                    seller_profiles.*,
                    users.first_name,
                    users.last_name,
                    users.email,
                    COUNT(products.id) AS total_products,
                    COALESCE(SUM(products.is_active = 1), 0) AS active_products,
                    COALESCE(SUM(
                        products.is_active = 1
                        AND products.harvest_date IS NOT NULL
                        AND products.harvest_photo IS NOT NULL
                        AND products.harvest_photo <> ''
                        AND DATEDIFF(CURDATE(), products.harvest_date) <= 14
                    ), 0) AS current_evidence_products,
                    MAX(products.harvest_date) AS latest_harvest_date
                FROM seller_profiles
                LEFT JOIN users
                    ON users.id = seller_profiles.user_id
                LEFT JOIN products
                    ON products.seller_profile_id = seller_profiles.id
                WHERE seller_profiles.id = ?
                GROUP BY seller_profiles.id
                LIMIT 1
            ");
            $farmerStmt->execute([$farmerId]);
            $selectedFarmer = $farmerStmt->fetch() ?: null;

            if ($selectedFarmer) {
                $productsStmt = $db->prepare("
                    SELECT
                        products.id,
                        products.name,
                        products.is_active,
                        products.approval_status,
                        products.harvest_date,
                        products.harvest_photo,
                        CASE
                            WHEN products.is_active = 0 THEN 'inactive'
                            WHEN products.harvest_date IS NULL THEN 'missing_date'
                            WHEN products.harvest_photo IS NULL OR products.harvest_photo = '' THEN 'missing_photo'
                            WHEN DATEDIFF(CURDATE(), products.harvest_date) > 14 THEN 'outdated'
                            ELSE 'current'
                        END AS evidence_status
                    FROM products
                    WHERE products.seller_profile_id = ?
                    ORDER BY products.is_active DESC, products.id DESC
                ");
                $productsStmt->execute([$farmerId]);
                $farmerProducts = $productsStmt->fetchAll();
            }
        }

        $sql = "

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

        ";

        $params = [];

        if ($farmerId) {

            $sql .= "

                WHERE seller_profiles.id = ?

            ";

            $params[] =
                $farmerId;
        }

        $sql .= "

            ORDER BY product_activity_logs.created_at DESC

        ";

        $stmt = $db->prepare($sql);

        $stmt->execute($params);

        $logs =
            $stmt->fetchAll();

        $transparencyAlerts = (new TransparencyAlertService())->all(
            $farmerId
        );

        return $this->view(
            'admin/transparency/index',
            compact(
                'logs',
                'transparencyAlerts',
                'selectedFarmer',
                'farmerProducts',
                'farmerId'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Risk Monitoring
    |--------------------------------------------------------------------------
    */

    public function riskMonitoring()
    {
        $this->requireAdmin();

        $riskService =
            new FarmerRiskService();

        $riskSummary =
            $riskService->summary();

        $farmers =
            $riskService->topSuspicious(25);

        $recentRiskAlerts =
            $riskService->recentAlerts(10);

        return $this->view(
            'admin/risk-monitoring/index',
            compact(
                'riskSummary',
                'farmers',
                'recentRiskAlerts'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Analytics & Insights
    |--------------------------------------------------------------------------
    */

    public function analytics()
    {
        $this->requireAdmin();

        $analyticsService =
            new AdminAnalyticsService();

        $analytics =
            $analyticsService->dashboard();

        return $this->view(
            'admin/analytics/index',
            compact('analytics')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Require Admin
    |--------------------------------------------------------------------------
    */

    private function requireAdmin()
    {
        return AuthGuard::admin();
    }

    private function requireAdminUser()
    {
        return AuthGuard::admin();
    }

    private function getMarketplaceAlerts()
    {
        $db = Database::connection();

        $alerts = [

            'harvest_changes' => [],
            'old_products'    => [],
            'missing_photos'  => [],
            'low_trust'       => []

        ];

        /*
    |--------------------------------------------------------------------------
    | Harvest Date Changes
    |--------------------------------------------------------------------------
    */

        try {

            $stmt = $db->query("

            SELECT

                product_activity_logs.*,

                products.name AS product_name,

                users.first_name,
                users.last_name

            FROM product_activity_logs

            LEFT JOIN products

                ON products.id =
                product_activity_logs.product_id

            LEFT JOIN users

                ON users.id =
                product_activity_logs.user_id

            WHERE action =
            'Harvest Date Changed'

            ORDER BY created_at DESC

            LIMIT 10

        ");

            $alerts['harvest_changes'] =
                $stmt->fetchAll();
        } catch (\Exception $e) {

            $alerts['harvest_changes'] = [];
        }

        /*
    |--------------------------------------------------------------------------
    | Products Older Than 14 Days
    |--------------------------------------------------------------------------
    */

        try {

            $stmt = $db->query("

            SELECT

                products.*,

                seller_profiles.shop_name,

                DATEDIFF(
                    CURDATE(),
                    products.harvest_date
                ) AS age_days

            FROM products

            LEFT JOIN seller_profiles

                ON seller_profiles.id =
                products.seller_profile_id

            WHERE products.harvest_date IS NULL
                OR DATEDIFF(
                    CURDATE(),
                    products.harvest_date
                ) > 14

            ORDER BY age_days DESC

            LIMIT 10

        ");

            $alerts['old_products'] =
                $stmt->fetchAll();
        } catch (\Exception $e) {

            $alerts['old_products'] = [];
        }

        /*
    |--------------------------------------------------------------------------
    | Missing Harvest Photos
    |--------------------------------------------------------------------------
    */

        try {

            $stmt = $db->query("

            SELECT

                products.*,

                seller_profiles.shop_name

            FROM products

            LEFT JOIN seller_profiles

                ON seller_profiles.id =
                products.seller_profile_id

            WHERE
                harvest_photo IS NULL
                OR harvest_photo = ''

            ORDER BY products.id DESC

            LIMIT 10

        ");

            $alerts['missing_photos'] =
                $stmt->fetchAll();
        } catch (\Exception $e) {

            $alerts['missing_photos'] = [];
        }

        /*
    |--------------------------------------------------------------------------
    | Low Trust Farmers
    |--------------------------------------------------------------------------
    */

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

            WHERE trust_score < 80

            ORDER BY trust_score ASC

            LIMIT 10

        ");

            $alerts['low_trust'] =
                $stmt->fetchAll();
        } catch (\Exception $e) {

            $alerts['low_trust'] = [];
        }

        return $alerts;
    }
}
