<?php

namespace App\Controllers;

use App\Services\ImageUploadService;
use App\Services\AuthGuard;
use App\Services\SchemaInspector;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\AuditLogService;
use App\Services\AuthSecurityService;
use App\Services\CloudinaryService;
use App\Services\SensitiveDataCipher;
use App\Services\AccountDeletionService;
use InvalidArgumentException;

class ProfileController extends Controller
{
    public function edit()
    {
        $user =
            $this->requireUser();

        $this->ensureProfileImageColumn();

        (new AuthSecurityService())->ensureSchema();

        $profileUser =
            $this->freshUser($user['id'])
            ?? $user;

        $sellerProfile =
            null;

        if (($user['role'] ?? '') === 'farmer') {
            $sellerProfile =
                $this->sellerProfile($user['id']);
        }

        return $this->view(
            'profile/edit',
            [
                'profileUser' => $profileUser,
                'sellerProfile' => $sellerProfile,
                'pageCss' => '/ani-mart/public/assets/css/pages/profile.css'
            ]
        );
    }

    public function update()
    {
        csrf_verify();
        
        $user =
            $this->requireUser();

        $firstName =
            trim($_POST['first_name'] ?? '');

        $lastName =
            trim($_POST['last_name'] ?? '');

        $email =
            trim($_POST['email'] ?? '');

        $password =
            $_POST['password'] ?? '';

        $passwordConfirmation =
            $_POST['password_confirmation'] ?? '';

        if (
            $firstName === ''
            || $lastName === ''
            || $email === ''
        ) {
            Session::flash(
                'error',
                'Please complete your name and email.'
            );

            return $this->redirect('/ani-mart/public/profile');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash(
                'error',
                'Please enter a valid email address.'
            );

            return $this->redirect('/ani-mart/public/profile');
        }

        if (
            $password !== ''
            && (
                strlen($password) < 8
                || !preg_match('/[A-Za-z]/', $password)
                || !preg_match('/[0-9]/', $password)
            )
        ) {
            Session::flash(
                'error',
                'Password must be at least 8 characters and include a letter and a number.'
            );

            return $this->redirect('/ani-mart/public/profile');
        }

        if (
            $password !== ''
            && $password !== $passwordConfirmation
        ) {
            Session::flash(
                'error',
                'Password confirmation does not match.'
            );

            return $this->redirect('/ani-mart/public/profile');
        }

        $db =
            Database::connection();

        $this->ensureProfileImageColumn();

        $currentProfile = $this->freshUser($user['id']);
        $previousProfileImage = (string) ($currentProfile['profile_image'] ?? '');

        $profileImage =
            $this->uploadProfileImage($user['id']);

        $removeProfileImage =
            !empty($_POST['remove_profile_image']) && !$profileImage;

        $stmt =
            $db->prepare("
                SELECT id
                FROM users
                WHERE email = ?
                    AND id <> ?
                LIMIT 1
            ");

        $stmt->execute([
            $email,
            $user['id']
        ]);

        if ($stmt->fetch()) {
            Session::flash(
                'error',
                'That email address is already in use.'
            );

            return $this->redirect('/ani-mart/public/profile');
        }

        if ($password !== '') {
            if ($profileImage) {
                $stmt =
                    $db->prepare("
                        UPDATE users
                        SET
                            first_name = ?,
                            last_name = ?,
                            email = ?,
                            password = ?,
                            profile_image = ?
                        WHERE id = ?
                    ");

                $stmt->execute([
                    $firstName,
                    $lastName,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT),
                    $profileImage,
                    $user['id']
                ]);
            } elseif ($removeProfileImage) {
                $stmt =
                    $db->prepare("
                        UPDATE users
                        SET
                            first_name = ?,
                            last_name = ?,
                            email = ?,
                            password = ?,
                            profile_image = NULL
                        WHERE id = ?
                    ");

                $stmt->execute([
                    $firstName,
                    $lastName,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT),
                    $user['id']
                ]);
            } else {
                $stmt =
                    $db->prepare("
                        UPDATE users
                        SET
                            first_name = ?,
                            last_name = ?,
                            email = ?,
                            password = ?
                        WHERE id = ?
                    ");

                $stmt->execute([
                    $firstName,
                    $lastName,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT),
                    $user['id']
                ]);
            }
        } else {
            if ($profileImage) {
                $stmt =
                    $db->prepare("
                        UPDATE users
                        SET
                            first_name = ?,
                            last_name = ?,
                            email = ?,
                            profile_image = ?
                        WHERE id = ?
                    ");

                $stmt->execute([
                    $firstName,
                    $lastName,
                    $email,
                    $profileImage,
                    $user['id']
                ]);
            } elseif ($removeProfileImage) {
                $stmt =
                    $db->prepare("
                        UPDATE users
                        SET
                            first_name = ?,
                            last_name = ?,
                            email = ?,
                            profile_image = NULL
                        WHERE id = ?
                    ");

                $stmt->execute([
                    $firstName,
                    $lastName,
                    $email,
                    $user['id']
                ]);
            } else {
                $stmt =
                    $db->prepare("
                        UPDATE users
                        SET
                            first_name = ?,
                            last_name = ?,
                            email = ?
                        WHERE id = ?
                    ");

                $stmt->execute([
                    $firstName,
                    $lastName,
                    $email,
                    $user['id']
                ]);
            }
        }

        $cipher = new SensitiveDataCipher();
        $db->prepare('UPDATE users SET first_name_encrypted = ?, last_name_encrypted = ?, email_encrypted = ? WHERE id = ?')
            ->execute([
                $cipher->encrypt($firstName, 'users.first_name'),
                $cipher->encrypt($lastName, 'users.last_name'),
                $cipher->encrypt($email, 'users.email'),
                $user['id'],
            ]);

        if (($user['role'] ?? '') === 'farmer') {
            $this->updateSellerProfile($user['id']);
        }

        if (($profileImage || $removeProfileImage) && $previousProfileImage !== '' && $previousProfileImage !== (string) $profileImage) {
            try {
                (new CloudinaryService())->deleteByUrl($previousProfileImage, false);
            } catch (\RuntimeException $exception) {
                error_log('Ani-Mart profile image cleanup skipped: ' . $exception->getMessage());
            }
        }

        $freshUser =
            $this->freshUser($user['id']);

        Session::set(
            'user',
            [
                'id' => $user['id'],
                'first_name' => $freshUser['first_name'] ?? $firstName,
                'last_name' => $freshUser['last_name'] ?? $lastName,
                'email' => $freshUser['email'] ?? $email,
                'profile_image' => $freshUser['profile_image'] ?? null,
                'role' => $user['role']
            ]
        );

        Session::flash(
            'success',
            'Profile updated successfully.'
        );

        return $this->redirect('/ani-mart/public/profile');
    }

    public function updateAdminLoginProtection()
    {
        csrf_verify();

        $user = $this->requireUser();

        if (strtolower((string) ($user['role'] ?? '')) !== 'admin') {
            Session::flash('error', 'Only administrators can change administrator login protection.');
            return $this->redirect('/ani-mart/public/profile');
        }

        $enabled = !empty($_POST['admin_login_otp_enabled']) ? 1 : 0;

        if ($enabled && !$this->hasMailConfiguration()) {
            Session::flash(
                'error',
                'Email must be configured before administrator login protection can be enabled.'
            );
            return $this->redirect('/ani-mart/public/profile');
        }

        (new AuthSecurityService())->ensureSchema();

        $stmt = Database::connection()->prepare('
            UPDATE users
            SET admin_login_otp_enabled = ?
            WHERE id = ? AND LOWER(role) = ?
        ');
        $stmt->execute([$enabled, (int) $user['id'], 'admin']);

        AuditLogService::log(
            'ADMIN_LOGIN_PROTECTION_UPDATED',
            'user',
            (int) $user['id'],
            ['new_value' => ['enabled' => (bool) $enabled]],
            (int) $user['id'],
            '/ani-mart/public/profile/admin-login-protection'
        );

        Session::flash(
            'success',
            $enabled
                ? 'Administrator login protection is now enabled.'
                : 'Administrator login protection is now disabled.'
        );

        return $this->redirect('/ani-mart/public/profile');
    }

    public function requestAccountDeletion()
    {
        csrf_verify();
        $user = $this->requireUser();

        if (strtolower((string) ($user['role'] ?? '')) === 'admin') {
            Session::flash('error', 'Administrator accounts cannot use self-service deletion.');
            return $this->redirect('/ani-mart/public/profile');
        }

        if (empty($_POST['confirm_account_deletion'])) {
            Session::flash('error', 'Confirm that you understand the 30-day deletion period.');
            return $this->redirect('/ani-mart/public/profile');
        }

        try {
            $scheduledFor = (new AccountDeletionService())->schedule(
                (int) $user['id'],
                (string) ($_POST['current_password'] ?? '')
            );

            AuditLogService::log(
                'ACCOUNT_DELETION_SCHEDULED',
                'user',
                (int) $user['id'],
                ['new_value' => ['scheduled_for' => $scheduledFor, 'grace_period_days' => AccountDeletionService::GRACE_PERIOD_DAYS]],
                (int) $user['id'],
                '/ani-mart/public/profile/delete-account'
            );

            Session::destroy();
            Session::start();
            Session::flash('success', 'Account deletion is scheduled for ' . date('F j, Y', strtotime($scheduledFor)) . '. Sign in before then to cancel it automatically.');
            return $this->redirect('/ani-mart/public/login');
        } catch (InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
            return $this->redirect('/ani-mart/public/profile');
        } catch (\Throwable $e) {
            error_log('Account deletion request failed: ' . $e->getMessage());
            Session::flash('error', 'We could not schedule account deletion. Please try again.');
            return $this->redirect('/ani-mart/public/profile');
        }
    }

    private function requireUser()
    {
        return AuthGuard::user();
    }

    private function freshUser($userId)
    {
        $db =
            Database::connection();

        $stmt =
            $db->prepare("
                SELECT
                    id,
                    first_name,
                    first_name_encrypted,
                    last_name,
                    last_name_encrypted,
                    email,
                    email_encrypted,
                    role,
                    profile_image,
                    admin_login_otp_enabled
                FROM users
                WHERE id = ?
                LIMIT 1
            ");

        $stmt->execute([
            $userId
        ]);

        $freshUser =
            $stmt->fetch();

        if (!$freshUser) {
            return null;
        }

        $freshUser = hydrate_sensitive_fields($freshUser, 'users', ['first_name', 'last_name', 'email']);

        return [
            'id' => $freshUser->id,
            'first_name' => $freshUser->first_name,
            'last_name' => $freshUser->last_name,
            'email' => $freshUser->email,
            'role' => $freshUser->role,
            'profile_image' => $freshUser->profile_image,
            'admin_login_otp_enabled' => (int) ($freshUser->admin_login_otp_enabled ?? 0)
        ];
    }

    private function hasMailConfiguration(): bool
    {
        return trim((string) ($_ENV['MAIL_USERNAME'] ?? '')) !== ''
            && trim((string) ($_ENV['MAIL_PASSWORD'] ?? '')) !== ''
            && trim((string) ($_ENV['MAIL_FROM_ADDRESS'] ?? '')) !== '';
    }

    private function ensureProfileImageColumn()
    {
        SchemaInspector::requireColumns('users', ['profile_image']);
    }

    private function uploadProfileImage($userId)
    {
        if (
            empty($_FILES['profile_image'])
            || $_FILES['profile_image']['error'] === UPLOAD_ERR_NO_FILE
        ) {
            return null;
        }

        if ($_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
            Session::flash(
                'error',
                'We could not upload your profile image.'
            );

            $this->redirect('/ani-mart/public/profile');
        }

        try {
            $upload = (new ImageUploadService())->publicField('profile_image', 'profiles', [
                'entity_type' => 'user_profile',
                'entity_id' => $userId,
                'owner_user_id' => $userId,
            ]);
        } catch (InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
            $this->redirect('/ani-mart/public/profile');
        } catch (\RuntimeException $exception) {
            error_log('Ani-Mart profile image upload unavailable: ' . $exception->getMessage());
            Session::flash(
                'error',
                'Profile image uploads are not configured on this server yet. You can save other profile changes without selecting an image.'
            );
            $this->redirect('/ani-mart/public/profile');
        }
        if (!$upload || empty($upload['secure_url'])) {
            Session::flash(
                'error',
                'We could not upload your profile image.'
            );

            $this->redirect('/ani-mart/public/profile');
        }

        return $upload['secure_url'];
    }

    private function sellerProfile($userId)
    {
        $db =
            Database::connection();

        $stmt =
            $db->prepare("
                SELECT *
                FROM seller_profiles
                WHERE user_id = ?
                LIMIT 1
            ");

        $stmt->execute([
            $userId
        ]);

        return $stmt->fetch();
    }

    private function updateSellerProfile($userId)
    {
        $shopName =
            trim($_POST['shop_name'] ?? '');

        $description =
            trim($_POST['description'] ?? '');

        if (
            $shopName === ''
            && $description === ''
        ) {
            return;
        }

        $db =
            Database::connection();

        $existing =
            $this->sellerProfile($userId);

        if ($existing) {
            $stmt =
                $db->prepare("
                    UPDATE seller_profiles
                    SET
                        shop_name = ?,
                        description = ?
                    WHERE user_id = ?
                ");

            $stmt->execute([
                $shopName,
                $description,
                $userId
            ]);

            return;
        }

        $stmt =
            $db->prepare("
                INSERT INTO seller_profiles
                (
                    user_id,
                    shop_name,
                    description,
                    shop_theme,
                    government_id,
                    status,
                    created_at
                )
                VALUES
                (
                    ?, ?, ?, 'fresh-farm', '', 'approved', NOW()
                )
            ");

        $stmt->execute([
            $userId,
            $shopName,
            $description
        ]);
    }
}
