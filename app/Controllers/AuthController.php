<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Middleware\RateLimitMiddleware;
use App\Services\AuditLogService;
use App\Services\SellerProfileService;
use App\Services\SmtpMailer;
use App\Services\VerificationEmailTemplate;
use App\Services\AccountSecurityEmailTemplate;
use App\Services\AuthSecurityService;
use App\Services\AccountControlService;
use App\Services\SensitiveDataCipher;
use App\Services\AccountDeletionService;

class AuthController extends Controller
{
    private const OTP_EXPIRY_SECONDS = 600;
    private const OTP_RESEND_SECONDS = 60;
    private const OTP_MAX_ATTEMPTS = 5;
    private const OTP_MAX_RESENDS = 3;
    private const LOGIN_ATTEMPTS_LIMIT = 5;
    private const LOGIN_ATTEMPTS_WINDOW_SECONDS = 300;
    private const REGISTER_ATTEMPTS_LIMIT = 5;
    private const REGISTER_ATTEMPTS_WINDOW_SECONDS = 600;
    private const OTP_VERIFY_ATTEMPTS_LIMIT = 5;
    private const OTP_VERIFY_ATTEMPTS_WINDOW_SECONDS = 600;
    private const OTP_RESEND_ATTEMPTS_LIMIT = 3;
    private const OTP_RESEND_ATTEMPTS_WINDOW_SECONDS = 600;
    private const ADMIN_OTP_EXPIRY_SECONDS = 600;
    private const ADMIN_OTP_MAX_ATTEMPTS = 5;
    private const ADMIN_OTP_MAX_RESENDS = 3;

    private string $otpDeliveryError =
        'We could not send a verification code to your email.';

    /*
    |--------------------------------------------------------------------------
    | REGISTER PAGE
    |--------------------------------------------------------------------------
    */

    public function register()
    {
        return $this->view(
            'auth/register'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE USER
    |--------------------------------------------------------------------------
    */

    public function store()
    {
        csrf_verify();

        $db =
            Database::connection();

        $firstName =
            trim($_POST['first_name'] ?? '');

        $lastName =
            trim($_POST['last_name'] ?? '');

        $email =
            $this->normalizeEmail($_POST['email'] ?? '');

        $password =
            $_POST['password'] ?? '';

        $botTrap =
            trim($_POST['company_website'] ?? '');

        $normalizedEmail = $this->normalizeEmail($email);
        $registerIpKey = $this->rateLimitKey('auth_register_ip', $this->clientIpAddress());
        $registerEmailKey = $this->rateLimitKey('auth_register_email', $normalizedEmail);

        if (!$this->enforceRateLimit($registerIpKey, $registerEmailKey, self::REGISTER_ATTEMPTS_LIMIT, self::REGISTER_ATTEMPTS_WINDOW_SECONDS, '/ani-mart/public/register')) {
            return $this->redirect('/ani-mart/public/register');
        }

        if ($botTrap !== '') {

            Session::flash(
                'error',
                'Registration could not be completed.'
            );

            return $this->redirect(
                '/ani-mart/public/register'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        set_old([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
        ]);

        $validationErrors = [];

        if (empty($firstName)) {
            $validationErrors['first_name'] = 'Enter your first name.';
        }

        if (empty($lastName)) {
            $validationErrors['last_name'] = 'Enter your last name.';
        }

        if (empty($email)) {
            $validationErrors['email'] = 'Enter your email address.';
        }

        if (empty($password)) {
            $validationErrors['password'] = 'Enter a password.';
        }

        if (!empty($validationErrors)) {

            set_errors($validationErrors);

            return $this->redirect(
                '/ani-mart/public/register'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            set_errors([
                'email' => 'Enter a valid email address.',
            ]);

            return $this->redirect(
                '/ani-mart/public/register'
            );
        }

        if (
            strlen($password) < 8 ||
            !preg_match('/[A-Za-z]/', $password) ||
            !preg_match('/[0-9]/', $password)
        ) {

            set_errors([
                'password' => 'Password must be at least 8 characters and include a letter and a number.',
            ]);

            return $this->redirect(
                '/ani-mart/public/register'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Check Existing Email
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("

                SELECT id

                FROM users

                WHERE email = ?

                LIMIT 1

            ");

        $stmt->execute([
            $email
        ]);

        if ($stmt->fetch()) {

            set_errors([
                'email' => 'An account with this email already exists.',
            ]);

            return $this->redirect(
                '/ani-mart/public/register'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Send Email OTP Before Creating User
        |--------------------------------------------------------------------------
        */

        $otp =
            (string) random_int(100000, 999999);

        $_SESSION['pending_registration'] = [

            'first_name' =>
            $firstName,

            'last_name' =>
            $lastName,

            'email' =>
            $email,

            'password_hash' =>
            password_hash(
                $password,
                PASSWORD_DEFAULT
            ),

            'otp_hash' =>
            password_hash(
                $otp,
                PASSWORD_DEFAULT
            ),

            'otp_expires_at' =>
            time() + self::OTP_EXPIRY_SECONDS,

            'otp_sent_at' =>
            time(),

            'otp_attempts' =>
            0,

            'otp_resends' =>
            0

        ];

        if (!$this->sendRegistrationOtp($email, $firstName, $otp)) {

            unset($_SESSION['pending_registration']);

            Session::flash(
                'error',
                $this->otpDeliveryError
            );

            return $this->redirect(
                '/ani-mart/public/register'
            );
        }

        RateLimitMiddleware::clear($registerIpKey);
        RateLimitMiddleware::clear($registerEmailKey);

        Session::flash(
            'success',
            'We sent a 6-digit verification code to your email.'
        );

        return $this->redirect(
            '/ani-mart/public/register/verify'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY REGISTRATION OTP PAGE
    |--------------------------------------------------------------------------
    */

    public function verifyRegistration()
    {
        if (empty($_SESSION['pending_registration'])) {

            Session::flash(
                'error',
                'Please start registration first.'
            );

            return $this->redirect(
                '/ani-mart/public/register'
            );
        }

        return $this->view(
            'auth/verify-register',
            [
                'pendingEmail' =>
                $_SESSION['pending_registration']['email']
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIRM REGISTRATION OTP
    |--------------------------------------------------------------------------
    */

    public function confirmRegistrationOtp()
    {
        csrf_verify();

        $pending =
            $_SESSION['pending_registration'] ?? null;

        if ($pending) {
            $pendingEmail = $this->normalizeEmail((string) ($pending['email'] ?? ''));
            $verifyIpKey = $this->rateLimitKey('auth_verify_ip', $this->clientIpAddress());
            $verifyEmailKey = $this->rateLimitKey('auth_verify_email', $pendingEmail);

            if (!$this->enforceRateLimit($verifyIpKey, $verifyEmailKey, self::OTP_VERIFY_ATTEMPTS_LIMIT, self::OTP_VERIFY_ATTEMPTS_WINDOW_SECONDS, '/ani-mart/public/register/verify')) {
                return $this->redirect('/ani-mart/public/register/verify');
            }
        }

        if (!$pending) {

            Session::flash(
                'error',
                'Your verification session expired. Please register again.'
            );

            return $this->redirect(
                '/ani-mart/public/register'
            );
        }

        if (time() > (int) $pending['otp_expires_at']) {

            unset($_SESSION['pending_registration']);

            Session::flash(
                'error',
                'Your verification code expired. Please register again.'
            );

            return $this->redirect(
                '/ani-mart/public/register'
            );
        }

        if ((int) $pending['otp_attempts'] >= self::OTP_MAX_ATTEMPTS) {

            unset($_SESSION['pending_registration']);

            Session::flash(
                'error',
                'Too many incorrect attempts. Please register again.'
            );

            return $this->redirect(
                '/ani-mart/public/register'
            );
        }

        $code =
            preg_replace('/\D/', '', $_POST['otp_code'] ?? '');

        if (
            strlen($code) !== 6 ||
            !password_verify(
                $code,
                $pending['otp_hash']
            )
        ) {

            AuditLogService::log(
                'OTP_VERIFICATION_FAILED',
                'user',
                null,
                [
                    'email' => $this->normalizeEmail((string) ($pending['email'] ?? '')),
                    'reason' => 'INVALID_OTP'
                ],
                null,
                '/ani-mart/public/register/verify',
                'failed'
            );

            $_SESSION['pending_registration']['otp_attempts'] =
                (int) $pending['otp_attempts'] + 1;

            Session::flash(
                'error',
                'Invalid verification code.'
            );

            return $this->redirect(
                '/ani-mart/public/register/verify'
            );
        }

        AuditLogService::log(
            'OTP_VERIFICATION_SUCCESS',
            'user',
            null,
            [
                'email' => $this->normalizeEmail((string) ($pending['email'] ?? '')),
                'reason' => 'VALID_OTP'
            ],
            null,
            '/ani-mart/public/register/verify',
            'success'
        );

        $db =
            Database::connection();

        $stmt =
            $db->prepare("

                SELECT id

                FROM users

                WHERE email = ?

                LIMIT 1

            ");

        $stmt->execute([
            $pending['email']
        ]);

        if ($stmt->fetch()) {

            unset($_SESSION['pending_registration']);

            Session::flash(
                'error',
                'Email already exists.'
            );

            return $this->redirect(
                '/ani-mart/public/register'
            );
        }

        $stmt =
            $db->prepare("

                INSERT INTO users (

                    first_name,
                    first_name_encrypted,
                    last_name,
                    last_name_encrypted,
                    email,
                    email_encrypted,
                    password,
                    role,
                    created_at

                )

                VALUES (

                    ?, ?, ?, ?, ?, ?, ?, 'customer', NOW()

                )

            ");

        $cipher = new SensitiveDataCipher();
        $stmt->execute([

            $pending['first_name'],
            $cipher->encrypt($pending['first_name'], 'users.first_name'),
            $pending['last_name'],
            $cipher->encrypt($pending['last_name'], 'users.last_name'),
            $pending['email'],
            $cipher->encrypt($pending['email'], 'users.email'),
            $pending['password_hash']

        ]);

        if ($pending) {
            $pendingEmail = $this->normalizeEmail((string) ($pending['email'] ?? ''));
            $verifyIpKey = $this->rateLimitKey('auth_verify_ip', $this->clientIpAddress());
            $verifyEmailKey = $this->rateLimitKey('auth_verify_email', $pendingEmail);
            RateLimitMiddleware::clear($verifyIpKey);
            RateLimitMiddleware::clear($verifyEmailKey);
        }

        session_regenerate_id(true);
        unset($_SESSION['pending_registration']);

        AuditLogService::log(
            'user_registered',
            'user',
            null,
            ['email' => $pending['email']],
            null,
            '/ani-mart/public/register'
        );

        $_SESSION['success'] =
            'Registration verified. You can now login.';

        return $this->redirect(
            '/ani-mart/public/login'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RESEND REGISTRATION OTP
    |--------------------------------------------------------------------------
    */

    public function resendRegistrationOtp()
    {
        csrf_verify();

        $pending =
            $_SESSION['pending_registration'] ?? null;

        if ($pending) {
            $pendingEmail = $this->normalizeEmail((string) ($pending['email'] ?? ''));
            $resendIpKey = $this->rateLimitKey('auth_resend_ip', $this->clientIpAddress());
            $resendEmailKey = $this->rateLimitKey('auth_resend_email', $pendingEmail);

            if (!$this->enforceRateLimit($resendIpKey, $resendEmailKey, self::OTP_RESEND_ATTEMPTS_LIMIT, self::OTP_RESEND_ATTEMPTS_WINDOW_SECONDS, '/ani-mart/public/register/verify')) {
                return $this->redirect('/ani-mart/public/register/verify');
            }
        }

        if (!$pending) {

            Session::flash(
                'error',
                'Please start registration first.'
            );

            return $this->redirect(
                '/ani-mart/public/register'
            );
        }

        if (
            time() - (int) $pending['otp_sent_at'] <
            self::OTP_RESEND_SECONDS
        ) {

            Session::flash(
                'error',
                'Please wait before requesting another code.'
            );

            return $this->redirect(
                '/ani-mart/public/register/verify'
            );
        }

        if ((int) $pending['otp_resends'] >= self::OTP_MAX_RESENDS) {

            unset($_SESSION['pending_registration']);

            Session::flash(
                'error',
                'Too many code requests. Please register again.'
            );

            return $this->redirect(
                '/ani-mart/public/register'
            );
        }

        $otp =
            (string) random_int(100000, 999999);

        $_SESSION['pending_registration']['otp_hash'] =
            password_hash(
                $otp,
                PASSWORD_DEFAULT
            );

        $_SESSION['pending_registration']['otp_expires_at'] =
            time() + self::OTP_EXPIRY_SECONDS;

        $_SESSION['pending_registration']['otp_sent_at'] =
            time();

        $_SESSION['pending_registration']['otp_attempts'] =
            0;

        $_SESSION['pending_registration']['otp_resends'] =
            (int) $pending['otp_resends'] + 1;

        if (!$this->sendRegistrationOtp(
            $pending['email'],
            $pending['first_name'],
            $otp
        )) {

            Session::flash(
                'error',
                $this->otpDeliveryError
            );

            return $this->redirect(
                '/ani-mart/public/register/verify'
            );
        }

        if ($pending) {
            $pendingEmail = $this->normalizeEmail((string) ($pending['email'] ?? ''));
            $resendIpKey = $this->rateLimitKey('auth_resend_ip', $this->clientIpAddress());
            $resendEmailKey = $this->rateLimitKey('auth_resend_email', $pendingEmail);
            RateLimitMiddleware::clear($resendIpKey);
            RateLimitMiddleware::clear($resendEmailKey);
        }

        AuditLogService::log(
            'OTP_RESEND',
            'user',
            null,
            [
                'email' => $this->normalizeEmail((string) ($pending['email'] ?? '')),
                'reason' => 'OTP_RESENT'
            ],
            null,
            '/ani-mart/public/register/resend-otp',
            'success'
        );

        Session::flash(
            'success',
            'A new verification code was sent to your email.'
        );

        return $this->redirect(
            '/ani-mart/public/register/verify'
        );
    }

    private function normalizeEmail(string $email): string
    {
        return trim(strtolower($email));
    }

    private function clientIpAddress(): string
    {
        $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';

        if ($forwardedFor !== '') {
            $parts = array_filter(array_map('trim', explode(',', $forwardedFor)));
            if (!empty($parts)) {
                return (string) $parts[0];
            }
        }

        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    private function rateLimitKey(string $prefix, string $value): string
    {
        $normalized = preg_replace('/[^a-z0-9._-]+/', '_', strtolower(trim($value)));

        return $prefix . '_' . ($normalized !== '' ? $normalized : 'unknown');
    }

    private function enforceRateLimit(string $ipKey, string $accountKey, int $limit, int $seconds, string $redirectPath): bool
    {
        if (!RateLimitMiddleware::handle($ipKey, $limit, $seconds, 'Too many attempts. Please wait a few minutes and try again.')) {
            Session::flash('error', 'Too many attempts. Please wait a few minutes and try again.');
            return false;
        }

        if (!RateLimitMiddleware::handle($accountKey, $limit, $seconds, 'Too many attempts. Please wait a few minutes and try again.')) {
            Session::flash('error', 'Too many attempts. Please wait a few minutes and try again.');
            return false;
        }

        return true;
    }

    private function sendRegistrationOtp(
        string $email,
        string $firstName,
        string $otp
    ): bool {

        if (!$this->hasEmailOtpConfig()) {

            $this->otpDeliveryError =
                'Email OTP is not configured yet. Add MAIL_USERNAME, MAIL_PASSWORD, and MAIL_FROM_ADDRESS in .env.';

            return false;
        }

        $htmlBody =
            VerificationEmailTemplate::html(
                $firstName,
                $otp
            );

        $textBody =
            VerificationEmailTemplate::text(
                $firstName,
                $otp
            );

        try {

            return (new SmtpMailer())->send(
                $email,
                'Your Ani-Mart verification code',
                $htmlBody,
                $textBody
            );

        } catch (\Throwable $exception) {

            error_log(
                'Ani-Mart email OTP failed: ' .
                $exception->getMessage()
            );

            $message =
                $exception->getMessage();

            if (
                str_contains($message, '535') ||
                str_contains($message, 'BadCredentials') ||
                str_contains($message, 'Username and Password not accepted')
            ) {

                $this->otpDeliveryError =
                    'Gmail rejected the SMTP login. Use a Gmail App Password for the same account in MAIL_USERNAME.';

            } else {

                $this->otpDeliveryError =
                    'We could not send a verification code to your email. Please check your Gmail SMTP settings.';
            }

            return false;
        }
    }

    private function hasEmailOtpConfig(): bool
    {
        return trim($_ENV['MAIL_USERNAME'] ?? '') !== '' &&
            trim($_ENV['MAIL_PASSWORD'] ?? '') !== '' &&
            trim($_ENV['MAIL_FROM_ADDRESS'] ?? '') !== '';
    }

    public function forgotPassword()
    {
        return $this->view('auth/forgot-password');
    }

    public function sendPasswordReset()
    {
        csrf_verify();
        $email = $this->normalizeEmail((string) ($_POST['email'] ?? ''));
        set_old(['email' => $email]);
        $security = new AuthSecurityService();
        $ip = $this->clientIpAddress();
        $limited = max(
            $security->recordAttempt('password_reset_ip', $ip, 3, 3600, 3600),
            $security->recordAttempt('password_reset_email', $email, 3, 3600, 3600)
        ) > 0;

        if (!$limited && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = Database::connection()->prepare('SELECT id, first_name, email FROM users WHERE LOWER(email) = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $token = $security->createResetToken((int) $user->id, $ip);
                $appUrl = rtrim((string) ($_ENV['APP_URL'] ?? 'http://localhost/ani-mart/public'), '/');
                $message = AccountSecurityEmailTemplate::passwordReset((string) $user->first_name, $appUrl . '/password/reset?token=' . rawurlencode($token));
                if ($this->sendSecurityEmail((string) $user->email, $message)) {
                    AuditLogService::log('PASSWORD_RESET_REQUESTED', 'user', (int) $user->id, ['reason' => 'email_link_sent'], (int) $user->id, '/ani-mart/public/password/forgot');
                }
            }
        }

        Session::flash('success', 'If an Ani-Mart account uses that email, a password-reset link has been sent. Check your inbox and spam folder.');
        return $this->redirect('/ani-mart/public/password/forgot');
    }

    public function resetPassword()
    {
        $token = strtolower(trim((string) ($_GET['token'] ?? '')));
        $validReset = (new AuthSecurityService())->validReset($token);
        return $this->view('auth/reset-password', ['resetToken' => $token, 'validReset' => $validReset]);
    }

    public function updatePasswordFromReset()
    {
        csrf_verify();
        $token = strtolower(trim((string) ($_POST['token'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        if (!$this->validPassword($password)) {
            Session::flash('error', 'Password must be at least 8 characters and include a letter and a number.');
            return $this->redirect('/ani-mart/public/password/reset?token=' . rawurlencode($token));
        }
        if (!hash_equals($password, $confirmation)) {
            Session::flash('error', 'Password confirmation does not match.');
            return $this->redirect('/ani-mart/public/password/reset?token=' . rawurlencode($token));
        }

        $userId = (new AuthSecurityService())->resetPassword($token, password_hash($password, PASSWORD_DEFAULT));
        if (!$userId) {
            Session::flash('error', 'This password-reset link is invalid or has expired. Request a new one.');
            return $this->redirect('/ani-mart/public/password/forgot');
        }

        Session::remove('user');
        Session::remove('pending_admin_login');
        session_regenerate_id(true);
        AuditLogService::log('PASSWORD_RESET_COMPLETED', 'user', $userId, ['reason' => 'single_use_token'], $userId, '/ani-mart/public/password/reset');
        Session::flash('success', 'Your password has been changed. You can now sign in.');
        return $this->redirect('/ani-mart/public/login');
    }

    public function verifyAdminLogin()
    {
        $pending = Session::get('pending_admin_login');
        if (!$pending || time() > (int) ($pending['expires_at'] ?? 0)) {
            Session::remove('pending_admin_login');
            Session::flash('error', 'Your administrator login code expired. Please sign in again.');
            return $this->redirect('/ani-mart/public/login');
        }
        return $this->view('auth/verify-admin-login', ['pendingEmail' => $pending['email'] ?? '']);
    }

    public function confirmAdminLogin()
    {
        csrf_verify();
        $pending = Session::get('pending_admin_login');
        if (!$pending || time() > (int) ($pending['expires_at'] ?? 0)) {
            Session::remove('pending_admin_login');
            Session::flash('error', 'Your administrator login code expired. Please sign in again.');
            return $this->redirect('/ani-mart/public/login');
        }
        if ((int) ($pending['attempts'] ?? 0) >= self::ADMIN_OTP_MAX_ATTEMPTS) {
            Session::remove('pending_admin_login');
            Session::flash('error', 'Too many incorrect codes. Please sign in again.');
            return $this->redirect('/ani-mart/public/login');
        }

        $code = preg_replace('/\D/', '', (string) ($_POST['otp_code'] ?? ''));
        if (strlen($code) !== 6 || !password_verify($code, (string) $pending['otp_hash'])) {
            $_SESSION['pending_admin_login']['attempts'] = (int) ($pending['attempts'] ?? 0) + 1;
            AuditLogService::log('ADMIN_LOGIN_OTP_FAILED', 'user', (int) $pending['user_id'], ['reason' => 'invalid_code'], (int) $pending['user_id'], '/ani-mart/public/login/admin-verify', 'failed');
            Session::flash('error', 'Invalid administrator login code.');
            return $this->redirect('/ani-mart/public/login/admin-verify');
        }

        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = ? AND LOWER(role) = ? LIMIT 1');
        $stmt->execute([(int) $pending['user_id'], 'admin']);
        $user = $stmt->fetch();
        if (!$user) {
            Session::remove('pending_admin_login');
            Session::flash('error', 'Administrator account could not be verified.');
            return $this->redirect('/ani-mart/public/login');
        }
        AuditLogService::log('ADMIN_LOGIN_OTP_SUCCESS', 'user', (int) $user->id, ['reason' => 'valid_code'], (int) $user->id, '/ani-mart/public/login/admin-verify');
        Session::remove('pending_admin_login');
        return $this->completeLogin($user);
    }

    public function resendAdminLoginCode()
    {
        csrf_verify();
        $pending = Session::get('pending_admin_login');
        if (!$pending) return $this->redirect('/ani-mart/public/login');
        if (time() - (int) ($pending['sent_at'] ?? 0) < self::OTP_RESEND_SECONDS) {
            Session::flash('error', 'Please wait before requesting another code.');
            return $this->redirect('/ani-mart/public/login/admin-verify');
        }
        if ((int) ($pending['resends'] ?? 0) >= self::ADMIN_OTP_MAX_RESENDS) {
            Session::remove('pending_admin_login');
            Session::flash('error', 'Too many code requests. Please sign in again.');
            return $this->redirect('/ani-mart/public/login');
        }
        $stmt = Database::connection()->prepare('SELECT id, first_name, email FROM users WHERE id = ? AND LOWER(role) = ? LIMIT 1');
        $stmt->execute([(int) $pending['user_id'], 'admin']);
        $user = $stmt->fetch();
        if (!$user || !$this->beginAdminChallenge($user, (int) ($pending['resends'] ?? 0) + 1)) {
            Session::flash('error', 'We could not send another administrator login code.');
            return $this->redirect('/ani-mart/public/login');
        }
        Session::flash('success', 'A new administrator login code was sent.');
        return $this->redirect('/ani-mart/public/login/admin-verify');
    }

    /*
    |--------------------------------------------------------------------------
    | LOGIN PAGE
    |--------------------------------------------------------------------------
    */

    public function login()
    {
        return $this->view(
            'auth/login'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATE USER
    |--------------------------------------------------------------------------
    */

    public function authenticate()
    {
        csrf_verify();
        $email = $this->normalizeEmail((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $ip = $this->clientIpAddress();
        $security = new AuthSecurityService();
        $security->ensureSchema();

        $retryAfter = max(
            $security->retryAfter('login_ip', $ip),
            $security->retryAfter('login_email', $email)
        );
        if ($retryAfter > 0) {
            Session::flash('error', 'Too many unsuccessful sign-in attempts. Try again in ' . max(1, (int) ceil($retryAfter / 60)) . ' minute(s).');
            set_old(['email' => $email]);
            return $this->redirect('/ani-mart/public/login');
        }

        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE LOWER(email) = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $user = hydrate_sensitive_fields($user, 'users', [
                'first_name', 'last_name', 'email', 'latitude', 'longitude',
                'address', 'barangay', 'municipality', 'province', 'phone',
                'suspension_reason',
            ]);
        }

        if (!$user || !password_verify($password, (string) $user->password)) {
            $lockedFor = max(
                $security->recordAttempt('login_ip', $ip, AuthSecurityService::LOGIN_LIMIT, AuthSecurityService::LOGIN_WINDOW_SECONDS, AuthSecurityService::LOGIN_LOCK_SECONDS),
                $security->recordAttempt('login_email', $email, AuthSecurityService::LOGIN_LIMIT, AuthSecurityService::LOGIN_WINDOW_SECONDS, AuthSecurityService::LOGIN_LOCK_SECONDS)
            );
            AuditLogService::log('LOGIN_FAILED', 'user', $user ? (int) $user->id : null, ['email' => $email, 'reason' => 'INVALID_CREDENTIALS'], $user ? (int) $user->id : null, '/ani-mart/public/login', 'failed');
            set_old(['email' => $email]);
            Session::flash('error', $lockedFor > 0 ? 'Too many unsuccessful sign-in attempts. This login is temporarily locked for 15 minutes.' : 'Invalid email or password.');
            return $this->redirect('/ani-mart/public/login');
        }

        if ((new AccountControlService())->isSuspended((int) $user->id)) {
            AuditLogService::log('LOGIN_BLOCKED_SUSPENDED', 'user', (int) $user->id, ['reason' => 'ACCOUNT_SUSPENDED'], (int) $user->id, '/ani-mart/public/login', 'failed');
            Session::flash('error', 'This account is suspended. Contact Ani-Mart support if you believe access should be restored.');
            return $this->redirect('/ani-mart/public/login');
        }

        $deletionState = (new AccountDeletionService())->handleSuccessfulLogin((int) $user->id);
        if ($deletionState === 'deleted') {
            AuditLogService::log('LOGIN_BLOCKED_DELETED', 'user', (int) $user->id, ['reason' => 'ACCOUNT_DELETION_COMPLETED'], (int) $user->id, '/ani-mart/public/login', 'failed');
            Session::flash('error', 'This account has been deleted because its 30-day grace period ended.');
            return $this->redirect('/ani-mart/public/login');
        }
        if ($deletionState === 'cancelled') {
            AuditLogService::log('ACCOUNT_DELETION_CANCELLED_BY_LOGIN', 'user', (int) $user->id, ['description' => 'Deletion request cancelled by successful login.'], (int) $user->id, '/ani-mart/public/login');
            Session::flash('success', 'Welcome back. Your account deletion request has been cancelled.');
        }

        $security->clearAttempts('login_ip', $ip);
        $security->clearAttempts('login_email', $email);
        $loginIpKey = $this->rateLimitKey('auth_login_ip', $this->clientIpAddress());
        $loginEmailKey = $this->rateLimitKey('auth_login_email', $email);
        RateLimitMiddleware::clear($loginIpKey);
        RateLimitMiddleware::clear($loginEmailKey);

        if (strtolower((string) ($user->role ?? '')) === 'admin' && !empty($user->admin_login_otp_enabled)) {
            if (!$this->beginAdminChallenge($user)) {
                Session::flash('error', 'Administrator login protection is enabled, but the verification email could not be sent. Check the mail configuration.');
                return $this->redirect('/ani-mart/public/login');
            }
            Session::flash('success', 'We sent a 6-digit administrator login code to your email.');
            return $this->redirect('/ani-mart/public/login/admin-verify');
        }

        return $this->completeLogin($user);
    }

    private function completeLogin(object $user)
    {
        session_regenerate_id(true);
        Session::remove('active_shop_id');
        Session::set('user', [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'profile_image' => $user->profile_image ?? null,
            'role' => $user->role,
            'phone' => $user->phone ?? null,
        ]);
        if (strtolower((string) ($user->role ?? '')) === 'farmer') SellerProfileService::activeShop($user->id);
        AuditLogService::log('user_logged_in', 'user', (int) $user->id, ['role' => $user->role ?? 'unknown'], (int) $user->id, '/ani-mart/public/login', 'success');
        return $this->redirect('/ani-mart/public/marketplace');
    }

    private function beginAdminChallenge(object $user, int $resends = 0): bool
    {
        if (!$this->hasEmailOtpConfig()) return false;
        $code = (string) random_int(100000, 999999);
        $message = AccountSecurityEmailTemplate::adminLoginCode((string) ($user->first_name ?? 'Administrator'), $code);
        if (!$this->sendSecurityEmail((string) $user->email, $message)) return false;
        Session::set('pending_admin_login', [
            'user_id' => (int) $user->id,
            'email' => (string) $user->email,
            'otp_hash' => password_hash($code, PASSWORD_DEFAULT),
            'expires_at' => time() + self::ADMIN_OTP_EXPIRY_SECONDS,
            'sent_at' => time(),
            'attempts' => 0,
            'resends' => $resends,
        ]);
        return true;
    }

    private function sendSecurityEmail(string $email, array $message): bool
    {
        if (!$this->hasEmailOtpConfig()) return false;
        try {
            return (new SmtpMailer())->send($email, (string) $message['subject'], (string) $message['html'], (string) $message['text']);
        } catch (\Throwable $e) {
            error_log('Ani-Mart account security email failed: ' . $e->getMessage());
            return false;
        }
    }

    private function validPassword(string $password): bool
    {
        return strlen($password) >= 8 && preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password);
    }

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    public function logout()
    {
        $user = Session::get('user');
        $userId = !empty($user['id']) ? (int) $user['id'] : null;

        Session::remove('user');

        if ($userId) {
            AuditLogService::log(
                'user_logged_out',
                'user',
                $userId,
                ['role' => $user['role'] ?? 'unknown'],
                $userId,
                '/ani-mart/public/logout',
                'success'
            );
        }

        session_destroy();

        return $this->redirect(
            '/ani-mart/public/login'
        );
    }
}
?>
