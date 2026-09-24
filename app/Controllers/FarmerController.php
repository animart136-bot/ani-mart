<?php

namespace App\Controllers;

use App\Services\SchemaInspector;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\CloudinaryService;
use App\Services\NotificationService;
use App\Services\SellerApprovalService;
use App\Services\SellerProfileService;
use App\Services\SellerApplicationRulesService;
use App\Services\AuditLogService;
use App\Services\OrderWorkflowSchemaService;
use App\Services\SensitiveDataCipher;
use App\Models\FarmerVerificationLog;

class FarmerController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Application Form
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        if (!Session::get('user')) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $db = Database::connection();
        $this->ensureApplicationTable($db);

        $stmt = $db->prepare(
            "SELECT * FROM farmer_applications WHERE user_id = ? AND status IN ('pending', 'incomplete', 'pending_official_verification', 'needs_correction') ORDER BY submitted_at DESC LIMIT 1"
        );
        $stmt->execute([Session::get('user')['id']]);

        $application = $stmt->fetch();

        if ($application) {
            foreach ([
                'first_name' => 'farmer_applications.first_name',
                'middle_name' => 'farmer_applications.middle_name',
                'last_name' => 'farmer_applications.last_name',
                'name_extension' => 'farmer_applications.name_extension',
                'sex' => 'farmer_applications.sex',
                'address' => 'farmer_applications.address',
                'contact_number' => 'farmer_applications.contact_number',
                'date_of_birth' => 'farmer_applications.date_of_birth',
                'rsbsa_number' => 'farmer_applications.rsbsa_number',
                'extracted_name' => 'farmer_applications.extracted_name',
                'extracted_rsbsa_number' => 'farmer_applications.extracted_rsbsa_number',
                'rsbsa_card_path' => 'farmer_applications.rsbsa_card_path',
            ] as $field => $context) {
                $application->{$field} = sensitive_value($application, $field, $context, $application->{$field} ?? null);
            }
        }

        if ($application && in_array($application->status ?? '', ['pending', 'pending_official_verification'], true)) {
            $requiredValues = [
                $application->farm_name ?? '',
                $application->farm_description ?? '',
                $application->address ?? '',
                $application->contact_number ?? '',
                $application->seller_profile_image_path ?? '',
                $application->declaration_accepted_at ?? '',
            ];
            $isComplete = count(array_filter($requiredValues, static fn ($value): bool => trim((string) $value) !== '')) === count($requiredValues);

            if ($isComplete) {
                (new SellerApprovalService())->approve($application, true);
                $sessionUser = Session::get('user');
                $sessionUser['role'] = 'farmer';
                Session::set('user', $sessionUser);
                $shop = SellerProfileService::defaultShop((int) $sessionUser['id']);
                if ($shop) SellerProfileService::setActiveShopId((int) $shop->id);
                Session::flash('success', 'Your shop is active. You can create products now. Products that pass Smart Price Check are published automatically; other listings are reviewed by an administrator.');
                return $this->redirect('/ani-mart/public/farmer/products');
            }
        }

        $isEditing = $application && isset($_GET['edit']);

        return $this->view('farmer/apply', compact('application', 'isEditing'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store Application
    |--------------------------------------------------------------------------
    */

    public function store()
    {
        csrf_verify();

        if (!Session::get('user')) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $user =
            Session::get('user');

        $firstName = trim($_POST['first_name'] ?? $user['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? $user['last_name'] ?? '');
        $nameExtension = trim($_POST['name_extension'] ?? '');
        $sex = trim($_POST['sex'] ?? '');
        $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
        $rsbsaNumber = trim($_POST['rsbsa_number'] ?? '');

        $farmName =
            trim($_POST['farm_name'] ?? '');

        $farmDescription =
            trim($_POST['farm_description'] ?? '');

        $address =
            trim($_POST['address'] ?? '');

        $contactNumber =
            trim($_POST['contact_number'] ?? '');

        if (
            $firstName === '' ||
            $lastName === '' ||
            ($sex !== '' && !in_array($sex, ['male', 'female', 'other', 'prefer_not_to_say'], true)) ||
            ($dateOfBirth !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfBirth)) ||
            $farmName === '' ||
            $farmDescription === '' ||
            $address === '' ||
            $contactNumber === '' ||
            empty($_FILES['seller_profile_image']['name']) ||
            ($_POST['verification_declaration'] ?? '') !== '1'
        ) {
            $_SESSION['error'] =
                'Please complete all required seller information and accept the declaration.';

            header('Location: /ani-mart/public/farmer/apply');
            exit;
        }

        $db = Database::connection();
        $sellerStmt = $db->prepare("SELECT id, is_suspended FROM seller_profiles WHERE user_id = ? AND status IN ('approved', 'pending') LIMIT 1");
        $sellerStmt->execute([$user['id']]);
        if ($seller = $sellerStmt->fetch()) {
            Session::flash('error', !empty($seller->is_suspended) ? 'Your seller account is suspended.' : 'You already have an active seller profile.');
            return $this->redirect('/ani-mart/public/farmer/apply');
        }

        $allowedExtensions = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];

        $cloudinary = new CloudinaryService();
        $sellerProfileImage = $this->uploadVerificationImage($_FILES['seller_profile_image'], $cloudinary, $allowedExtensions, true, 'ani-mart/seller-profile-images');
        $hasRsbsaUpload = !empty($_FILES['rsbsa_card']['name']);
        $rsbsaCard = $hasRsbsaUpload
            ? $this->uploadVerificationImage($_FILES['rsbsa_card'], $cloudinary, $allowedExtensions, false, 'ani-mart/farmer-verification', true)
            : null;
        $shopBanner = $this->uploadVerificationImage($_FILES['shop_banner'] ?? [], $cloudinary, $allowedExtensions);

        if ($sellerProfileImage === null || ($hasRsbsaUpload && $rsbsaCard === null)) {
            $_SESSION['error'] =
            'Please upload a valid seller profile image. If supplied, the RSBSA card must be JPG, PNG, or WEBP.';

            header('Location: /ani-mart/public/farmer/apply');
            exit;
        }

        $verification = [
            'ai_status' => 'pending',
            'confidence_score' => null,
            'verification_reason' => $hasRsbsaUpload
                ? 'RSBSA documentation was submitted as optional Local Farmer badge evidence. It is not officially verified by Ani-Mart.'
                : 'No RSBSA documentation was submitted. The application will be reviewed as a regular seller application.',
            'ocr_data' => [
                'full_name' => null,
                'rsbsa_number' => null,
                'card_type' => null,
                'agency' => null,
            ],
            'detected_features' => [],
        ];

        $this->ensureApplicationTable($db);
        $this->ensureApplicationColumns($db);
        $cipher = new SensitiveDataCipher();

        $stmt = $db->prepare("

            SELECT *

            FROM farmer_applications

            WHERE user_id = ?
                AND status IN ('pending', 'incomplete', 'pending_official_verification', 'needs_correction')

            LIMIT 1

        ");

        $stmt->execute([
            $user['id']
        ]);

        $existingApplication = $stmt->fetch();

        if ($existingApplication && !$hasRsbsaUpload && !empty($existingApplication->rsbsa_card_path)) {
            $rsbsaCard = ['secure_url' => $existingApplication->rsbsa_card_path];
            $verification = [
                'ai_status' => $existingApplication->ai_status ?? 'pending',
                'confidence_score' => $existingApplication->confidence_score ?? null,
                'verification_reason' => $existingApplication->verification_reason ?? null,
                'ocr_data' => [
                    'full_name' => sensitive_value($existingApplication, 'extracted_name', 'farmer_applications.extracted_name', null),
                    'rsbsa_number' => sensitive_value($existingApplication, 'extracted_rsbsa_number', 'farmer_applications.extracted_rsbsa_number', null),
                    'card_type' => $existingApplication->extracted_card_type ?? null,
                    'agency' => $existingApplication->extracted_agency ?? null,
                ],
                'detected_features' => json_decode((string) ($existingApplication->ai_analysis ?? '[]'), true) ?: [],
            ];
        }

        if ($existingApplication) {
            $stmt = $db->prepare("\n                UPDATE farmer_applications\n                SET\n                    farm_name = ?,\n                    farm_description = ?,\n                    address = ?,\n                    contact_number = ?,\n                    shop_banner_path = ?,\n                    seller_profile_image_path = ?,\n                    rsbsa_card_path = ?,\n                    ai_status = ?,\n                    confidence_score = ?,\n                    verification_reason = ?,\n                    verified_at = ?,\n                    extracted_name = ?,\n                    extracted_rsbsa_number = ?,\n                    extracted_card_type = ?,\n                    extracted_agency = ?,\n                    ai_analysis = ?,\n                    submitted_at = NOW()\n                WHERE id = ? AND user_id = ? AND status IN ('pending', 'incomplete', 'pending_official_verification', 'needs_correction')\n            ");

            $stmt->execute([
                $farmName,
                $farmDescription,
                $address,
                $contactNumber,
                $shopBanner['secure_url'] ?? $existingApplication->shop_banner_path ?? null,
                $sellerProfileImage['secure_url'],
                $rsbsaCard['secure_url'] ?? null,
                $verification['ai_status'],
                $verification['confidence_score'],
                $verification['verification_reason'],
                $verification['ai_status'] === 'verified' ? date('Y-m-d H:i:s') : null,
                $verification['ocr_data']['full_name'],
                $verification['ocr_data']['rsbsa_number'],
                $verification['ocr_data']['card_type'],
                $verification['ocr_data']['agency'],
                json_encode($verification['detected_features']),
                $existingApplication->id,
                $user['id']
            ]);

            $applicationId = (int) $existingApplication->id;
            $_SESSION['success'] = 'Your seller information has been updated.';
        } else {

        $stmt = $db->prepare("

            INSERT INTO farmer_applications
            (
                user_id,
                farm_name,
                farm_description,
                address,
                contact_number,
                shop_banner_path,
                seller_profile_image_path,
                status,
                rsbsa_card_path,
                ai_status,
                confidence_score,
                verification_reason,
                verified_at,
                extracted_name,
                extracted_rsbsa_number,
                extracted_card_type,
                extracted_agency,
                ai_analysis,
                submitted_at
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )

        ");

        $stmt->execute([
            $user['id'],
            $farmName,
            $farmDescription,
            $address,
            $contactNumber,
            $shopBanner['secure_url'] ?? null,
            $sellerProfileImage['secure_url'],
            $rsbsaCard['secure_url'] ?? null,
            $verification['ai_status'],
            $verification['confidence_score'],
            $verification['verification_reason'],
            $verification['ai_status'] === 'verified' ? date('Y-m-d H:i:s') : null,
            $verification['ocr_data']['full_name'],
            $verification['ocr_data']['rsbsa_number'],
            $verification['ocr_data']['card_type'],
            $verification['ocr_data']['agency'],
            json_encode($verification['detected_features'])
        ]);

        $applicationId = (int) $db->lastInsertId();
        $_SESSION['success'] = 'Your seller information was submitted.';
        }

        if (!empty($rsbsaCard['public_id'])) {
            $cloudinary->associate((string) $rsbsaCard['public_id'], 'farmer_application', $applicationId, (int) $user['id']);
        }

        $applicationTrack = SellerApplicationRulesService::verificationTrack(
            $rsbsaCard['secure_url'] ?? null,
            $rsbsaNumber
        );
        $hasRsbsaInformation = $applicationTrack['has_rsbsa_information'];
        $applicationStatus = $applicationTrack['application_status'];
        $rsbsaVerificationStatus = $applicationTrack['rsbsa_verification_status'];
        $identityUpdate = $db->prepare('UPDATE farmer_applications SET first_name = ?, middle_name = ?, last_name = ?, name_extension = ?, sex = ?, date_of_birth = ?, rsbsa_number = ?, status = ?, application_status = ?, rsbsa_verification_status = ?, declaration_accepted_at = NOW() WHERE id = ?');
        $identityUpdate->execute([$firstName, $middleName, $lastName, $nameExtension, $sex ?: null, $dateOfBirth ?: null, $rsbsaNumber ?: null, $applicationStatus, $applicationStatus, $rsbsaVerificationStatus, $applicationId]);

        $encryptedIdentityUpdate = $db->prepare('UPDATE farmer_applications SET first_name_encrypted = ?, middle_name_encrypted = ?, last_name_encrypted = ?, name_extension_encrypted = ?, sex_encrypted = ?, address_encrypted = ?, contact_number_encrypted = ?, date_of_birth_encrypted = ?, rsbsa_number_encrypted = ?, extracted_name_encrypted = ?, extracted_rsbsa_number_encrypted = ?, rsbsa_card_path_encrypted = ? WHERE id = ?');
        $encryptedIdentityUpdate->execute([
            $cipher->encrypt($firstName, 'farmer_applications.first_name'),
            $cipher->encrypt($middleName ?: null, 'farmer_applications.middle_name'),
            $cipher->encrypt($lastName, 'farmer_applications.last_name'),
            $cipher->encrypt($nameExtension ?: null, 'farmer_applications.name_extension'),
            $cipher->encrypt($sex ?: null, 'farmer_applications.sex'),
            $cipher->encrypt($address, 'farmer_applications.address'),
            $cipher->encrypt($contactNumber, 'farmer_applications.contact_number'),
            $cipher->encrypt($dateOfBirth ?: null, 'farmer_applications.date_of_birth'),
            $cipher->encrypt($rsbsaNumber ?: null, 'farmer_applications.rsbsa_number'),
            $cipher->encrypt($verification['ocr_data']['full_name'], 'farmer_applications.extracted_name'),
            $cipher->encrypt($verification['ocr_data']['rsbsa_number'], 'farmer_applications.extracted_rsbsa_number'),
            $cipher->encrypt($rsbsaCard['secure_url'] ?? ($existingApplication->rsbsa_card_path ?? null), 'farmer_applications.rsbsa_card_path'),
            $applicationId,
        ]);

        $reviewUpdate = $db->prepare('UPDATE farmer_applications SET ai_review_status = ?, ai_review_summary = ?, ai_match_score = ?, ai_reviewed_at = NOW() WHERE id = ?');
        $reviewSummary = !$hasRsbsaInformation
            ? 'No RSBSA documentation was submitted. The application can be reviewed as a regular seller.'
            : 'RSBSA documentation was submitted. The card name must match the applicant name before RSBSA recognition is granted.';
        $reviewUpdate->execute([$hasRsbsaInformation ? 'submitted' : 'not_submitted', $reviewSummary, null, $applicationId]);
        $db->prepare('UPDATE farmer_applications SET verification_reason_encrypted = ?, ai_analysis_encrypted = ?, ai_review_summary_encrypted = ? WHERE id = ?')
            ->execute([
                $cipher->encrypt($verification['verification_reason'] ?? null, 'farmer_applications.verification_reason'),
                $cipher->encrypt(json_encode($verification['detected_features']), 'farmer_applications.ai_analysis'),
                $cipher->encrypt($reviewSummary, 'farmer_applications.ai_review_summary'),
                $applicationId,
            ]);

        FarmerVerificationLog::recordManual(
            $applicationId,
            null,
            'application_review',
            'approved',
            $hasRsbsaInformation
                ? 'Seller access was activated immediately. RSBSA recognition is pending a name match or administrator review.'
                : 'Regular seller access was activated immediately.'
        );

        $applicationStmt = $db->prepare('SELECT * FROM farmer_applications WHERE id = ? LIMIT 1');
        $applicationStmt->execute([$applicationId]);
        $submittedApplication = $applicationStmt->fetch();
        (new SellerApprovalService())->approve($submittedApplication, true);

        $user['role'] = 'farmer';
        Session::set('user', $user);
        $activeShop = SellerProfileService::defaultShop((int) $user['id']);
        if ($activeShop) SellerProfileService::setActiveShopId((int) $activeShop->id);

        AuditLogService::log(
            'SELLER_ACCESS_AUTO_ACTIVATED',
            'seller_application',
            $applicationId,
            ['rsbsa_documentation_submitted' => $hasRsbsaInformation],
            (int) $user['id'],
            '/ani-mart/public/farmer/apply',
            'success'
        );
        Session::flash('success', 'Your shop is active. You can create products now. Products that pass Smart Price Check are published automatically; other listings are reviewed by an administrator.');

        $adminStmt = $db->query(
            "SELECT id FROM users WHERE role = 'admin'"
        );

        $admins = $adminStmt->fetchAll();

        foreach ($admins as $admin) {
            NotificationService::send(
                $admin->id,
                'New Seller Activated',
                "{$user['first_name']} {$user['last_name']} activated a new shop" . ($hasRsbsaInformation ? ' with RSBSA documentation pending a name-match review.' : '.'),
                'verification',
                'bi-patch-check',
                'info',
                '/ani-mart/public/admin/applications'
            );
        }

        header('Location: /ani-mart/public/farmer/products');
        exit;
    }

    private function uploadVerificationImage(array $file, CloudinaryService $cloudinary, array $allowedExtensions, bool $required = false, string $folder = 'ani-mart/farmer-verification', bool $sensitive = false): ?array
    {
        if (empty($file['name'])) return $required ? null : null;
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return null;
        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) return null;
        $payload = [
            'name' => $file['name'] ?? '',
            'tmp_name' => $file['tmp_name'] ?? '',
            'size' => $file['size'] ?? 0,
            'type' => $file['type'] ?? '',
            'error' => $file['error'] ?? UPLOAD_ERR_NO_FILE,
        ];

        return $sensitive
            ? $cloudinary->uploadSensitive($payload, $folder, ['retention_days' => 365])
            : $cloudinary->uploadPublic($payload, $folder);
    }

    private function ensureApplicationColumns($db): void
    {
        $this->ensureApplicationTable($db);
        SchemaInspector::requireColumns('farmer_applications', [
            'first_name_encrypted', 'middle_name_encrypted', 'last_name_encrypted', 'name_extension_encrypted',
            'sex_encrypted', 'seller_profile_image_path', 'shop_banner_path', 'address', 'address_encrypted',
            'contact_number', 'contact_number_encrypted', 'date_of_birth_encrypted', 'rsbsa_number_encrypted',
            'extracted_name_encrypted', 'extracted_rsbsa_number_encrypted', 'rsbsa_card_path_encrypted',
            'ai_review_status', 'ai_review_summary', 'ai_review_summary_encrypted', 'ai_match_score',
            'ai_review_notes', 'ai_review_notes_encrypted', 'ai_reviewed_at',
        ]);
    }

    private function ensureApplicationTable($db): void
    {
        SchemaInspector::requireTable('farmer_applications');
    }


    /*
    |--------------------------------------------------------------------------
    | Orders
    |--------------------------------------------------------------------------
    */

    public function orders()
    {
        if (
            !Session::get('user')
            || Session::get('user')['role'] !== 'farmer'
        ) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $user = Session::get('user');

        $db = Database::connection();
        (new OrderWorkflowSchemaService())->ensure();

        $seller = SellerProfileService::activeShop($user['id']);

        if (!$seller) {

            return $this->view(
                'orders/index',
                [
                    'orders' => [],
                    'role' => 'farmer'
                ]
            );
        }

        $db->exec("INSERT IGNORE INTO order_seller_statuses (order_id, seller_profile_id, status)
            SELECT DISTINCT
                oi.order_id,
                oi.seller_profile_id,
                CASE
                    WHEN (
                        SELECT COUNT(DISTINCT sibling.seller_profile_id)
                        FROM order_items sibling
                        WHERE sibling.order_id = oi.order_id
                    ) > 1 THEN 'pending'
                    ELSE o.status
                END
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            WHERE oi.seller_profile_id = " . (int) $seller->id);

        $stmt = $db->prepare("
            SELECT
                orders.*,
                users.first_name,
                users.last_name,
                cancellation.id AS cancellation_request_id,
                cancellation.status AS cancellation_request_status,
                cancellation.request_reason AS cancellation_request_reason,
                cancellation.request_reason_encrypted AS cancellation_request_reason_encrypted,
                cancellation.response_reason AS cancellation_response_reason,
                cancellation.response_reason_encrypted AS cancellation_response_reason_encrypted,
                cancellation.requested_at AS cancellation_requested_at,
                cancellation.responded_at AS cancellation_responded_at,
                returns.id AS return_request_id,
                returns.status AS return_request_status,
                returns.refunded_amount AS return_refunded_amount,
                seller_status.status AS seller_order_status
            FROM orders
            INNER JOIN order_items
                ON order_items.order_id = orders.id
            LEFT JOIN users
                ON users.id = orders.user_id
            LEFT JOIN order_cancellation_requests cancellation
                ON cancellation.order_id = orders.id
                AND cancellation.seller_user_id = ?
            LEFT JOIN return_requests returns
                ON returns.order_id = orders.id
            LEFT JOIN order_seller_statuses seller_status
                ON seller_status.order_id = orders.id
                AND seller_status.seller_profile_id = ?
            WHERE order_items.seller_profile_id = ?
            GROUP BY orders.id
            ORDER BY orders.id DESC
        ");

        $stmt->execute([(int) $user['id'], $seller->id, $seller->id]);

        $orders = $stmt->fetchAll();

        foreach ($orders as $order) {

            $order->status = $order->seller_order_status ?: 'pending';

            $itemStmt = $db->prepare("
                SELECT
                    order_items.*,
                    products.name,
                    products.image
                FROM order_items
                LEFT JOIN products
                    ON products.id =
                    order_items.product_id
                WHERE order_items.order_id = ?
                    AND order_items.seller_profile_id = ?
            ");

            $itemStmt->execute([
                $order->id,
                $seller->id
            ]);

            $order->items =
                $itemStmt->fetchAll();
        }

        return $this->view(
            'orders/index',
            [
                'orders' => $orders,
                'role' => 'farmer'
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Order Status
    |--------------------------------------------------------------------------
    */

    public function updateOrderStatus()
    {
        $user = Session::get('user');

        if (!$user) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        if (strtolower($user['role'] ?? '') !== 'farmer') {
            http_response_code(403);
            echo '403 Unauthorized';
            exit;
        }

        $id =
            $_GET['id'] ?? 0;

        $status =
            $_GET['status'] ?? '';

        $allowed = [
            'accepted',
            'preparing',
            'out_for_delivery',
            'delivered',
            'cancelled'
        ];

        if (
            $id &&
            in_array(
                $status,
                $allowed
            )
        ) {

            $db =
                Database::connection();

            $stmt = $db->prepare(
                "SELECT o.id, sp.id AS seller_profile_id
                FROM orders o
                INNER JOIN order_items oi ON oi.order_id = o.id
                INNER JOIN seller_profiles sp ON sp.id = oi.seller_profile_id
                WHERE o.id = ? AND sp.user_id = ?
                LIMIT 1"
            );
            $stmt->execute([$id, $user['id']]);

            $sellerOrder = $stmt->fetch();
            if (!$sellerOrder) {
                http_response_code(403);
                echo '403 Unauthorized';
                exit;
            }

            $db->prepare('INSERT IGNORE INTO order_seller_statuses (order_id, seller_profile_id, status) SELECT id, ?, status FROM orders WHERE id = ?')
                ->execute([(int) $sellerOrder->seller_profile_id, (int) $id]);

            $stmt = $db->prepare("UPDATE order_seller_statuses
                SET status = ?
                WHERE order_id = ? AND seller_profile_id = ?");

            $stmt->execute([
                $status,
                $id,
                (int) $sellerOrder->seller_profile_id
            ]);
        }

        header(
            'Location: /ani-mart/public/farmer/orders'
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Upload Delivery Proof
    |--------------------------------------------------------------------------
    */

    public function uploadProof()
    {
        // move existing code from OrderController here
    }

}
