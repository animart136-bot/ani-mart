<?php

namespace App\Core;

class Session
{
    /*
    |--------------------------------------------------------------------------
    | Start Session
    |--------------------------------------------------------------------------
    */
    public static function start()
    {
        if (
            session_status() === PHP_SESSION_NONE
        ) {
            self::configureStorage();
            self::configureCookieSettings();
            session_start();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Set Session
    |--------------------------------------------------------------------------
    */
    public static function set(
        $key,
        $value
    ) {

        $_SESSION[$key] = $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Session
    |--------------------------------------------------------------------------
    */
    public static function get(
        $key,
        $default = null
    ) {

        return $_SESSION[$key]
            ?? $default;
    }

    /*
    |--------------------------------------------------------------------------
    | Check Session Exists
    |--------------------------------------------------------------------------
    */
    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    /*
    |--------------------------------------------------------------------------
    | Remove Session
    |--------------------------------------------------------------------------
    */
    public static function remove($key)
    {
        unset($_SESSION[$key]);
    }

    /*
    |--------------------------------------------------------------------------
    | Flash Message
    |--------------------------------------------------------------------------
    */
    public static function flash(
        $key,
        $message = null
    ) {

        /*
        |--------------------------------------------------------------------------
        | SET FLASH
        |--------------------------------------------------------------------------
        */
        if ($message !== null) {

            $_SESSION['_flash'][$key]
                = $message;

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | GET FLASH
        |--------------------------------------------------------------------------
        */
        if (
            isset($_SESSION['_flash'][$key])
        ) {

            $flash =
                $_SESSION['_flash'][$key];

            unset(
                $_SESSION['_flash'][$key]
            );

            return $flash;
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy Session
    |--------------------------------------------------------------------------
    */
    public static function destroy()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_unset();
            session_destroy();
        }

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }

    private static function configureCookieSettings()
    {
        if (headers_sent()) {
            return;
        }

        $secure = self::shouldUseSecureCookies();

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
    }

    private static function configureStorage(): void
    {
        $configuredPath = trim((string) ($_ENV['SESSION_SAVE_PATH'] ?? ''));
        $root = defined('ANI_MART_ROOT')
            ? ANI_MART_ROOT
            : dirname(__DIR__, 2);
        $path = $configuredPath !== ''
            ? $configuredPath
            : $root . '/storage/sessions';

        if (!is_dir($path)) {
            @mkdir($path, 0700, true);
        }

        if (is_dir($path) && is_writable($path)) {
            session_save_path($path);
            return;
        }

        error_log('Ani-Mart session storage is unavailable: ' . $path);
    }

    private static function shouldUseSecureCookies(): bool
    {
        $https = false;

        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            $https = true;
        } elseif (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            $https = true;
        } elseif (!empty($_SERVER['REQUEST_SCHEME']) && strtolower($_SERVER['REQUEST_SCHEME']) === 'https') {
            $https = true;
        }

        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        $isLocalhost = $host === 'localhost' || $host === '127.0.0.1' || $host === '::1' || strpos($host, 'localhost') !== false;

        return $https && !$isLocalhost;
    }
}
