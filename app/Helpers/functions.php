<?php

use App\Core\Session;
use App\Services\SensitiveDataCipher;

/**
 * Return Ani-Mart's configured public URL, with an optional application path.
 * Keeping URL construction here prevents local installation paths from being
 * repeated throughout controllers, views, and JavaScript configuration.
 */
function app_url(string $path = ''): string
{
    static $baseUrl;

    if ($baseUrl === null) {
        $config = require ANI_MART_ROOT . '/config/app.php';
        $baseUrl = rtrim((string) ($config['app_url'] ?? ''), '/');

        // When a local build is opened from another device on the same
        // network, "localhost" points to that device rather than the Mac
        // running Apache. Keep the request host for generated links so the
        // Resolution Center and its nested pages remain reachable on mobile.
        $configuredHost = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));
        $isLocalConfiguredHost = in_array($configuredHost, ['localhost', '127.0.0.1', '::1'], true);
        $requestHost = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $isValidRequestHost = preg_match('/^(?:[a-z0-9.-]+|\[[0-9a-f:.]+\])(?::\d+)?$/i', $requestHost) === 1;

        if ($isLocalConfiguredHost && $isValidRequestHost && !in_array(strtolower($requestHost), ['localhost', '127.0.0.1', '::1'], true)) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $basePath = rtrim((string) parse_url($baseUrl, PHP_URL_PATH), '/');
            $baseUrl = $scheme . '://' . $requestHost . $basePath;
        }
    }

    if ($path === '') {
        return $baseUrl;
    }

    return $baseUrl . '/' . ltrim($path, '/');
}

/** Return only the configured public base path (for example /ani-mart/public). */
function app_base_path(): string
{
    return rtrim((string) (parse_url(app_url(), PHP_URL_PATH) ?: ''), '/');
}

/** Convert a legacy local Ani-Mart path to the configured installation path. */
function normalize_app_url(string $url): string
{
    if (str_starts_with($url, '/ani-mart/public')) {
        return app_base_path() . substr($url, strlen('/ani-mart/public'));
    }
    return $url;
}

/** Normalize legacy paths embedded in rendered HTML and inline JavaScript. */
function normalize_app_markup(string $markup): string
{
    return app_base_path() === '/ani-mart/public'
        ? $markup
        : str_replace('/ani-mart/public', app_base_path(), $markup);
}

/** Escape a value for safe HTML output. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Render a reusable view component with isolated variables. */
function render_component(string $component, array $data = []): void
{
    $path = ANI_MART_ROOT . '/app/Views/components/' . trim($component, '/') . '.php';
    if (!is_file($path)) {
        throw new RuntimeException('View component not found: ' . $component);
    }

    extract($data, EXTR_SKIP);
    require $path;
}

/*
|--------------------------------------------------------------------------
| Old input helper
|--------------------------------------------------------------------------
*/
function old($key)
{
    return Session::get('_old')[$key] ?? '';
}

/*
|--------------------------------------------------------------------------
| Set old input
|--------------------------------------------------------------------------
*/
function set_old($data)
{
    Session::set('_old', $data);
}

/*
|--------------------------------------------------------------------------
| Validation errors
|--------------------------------------------------------------------------
*/
function errors()
{
    return Session::flash('_errors') ?? [];
}

function set_errors($data)
{
    Session::flash('_errors', $data);
}

/*
|--------------------------------------------------------------------------
| Flash message
|--------------------------------------------------------------------------
*/
function flash($key)
{
    return Session::flash($key);
}

/*
|--------------------------------------------------------------------------
| Sensitive data helper
|--------------------------------------------------------------------------
*/
function sensitive_value($record, string $field, string $context, $fallback = '')
{
    if (is_array($record)) {
        $encrypted = $record[$field . '_encrypted'] ?? null;
        $plain = $record[$field] ?? $fallback;
    } elseif (is_object($record)) {
        $encryptedField = $field . '_encrypted';
        $encrypted = $record->{$encryptedField} ?? null;
        $plain = $record->{$field} ?? $fallback;
    } else {
        return $fallback;
    }

    if (!is_string($encrypted) || $encrypted === '') {
        return $plain;
    }

    return (new SensitiveDataCipher())->decrypt($encrypted, $context);
}

/**
 * Replace selected record properties with their decrypted companion values.
 */
function hydrate_sensitive_fields($record, string $table, array $fields)
{
    if (!is_array($record) && !is_object($record)) {
        return $record;
    }

    foreach ($fields as $field) {
        $fallback = is_array($record)
            ? ($record[$field] ?? null)
            : ($record->{$field} ?? null);
        $value = sensitive_value($record, $field, $table . '.' . $field, $fallback);
        if (is_array($record)) {
            $record[$field] = $value;
        } else {
            $record->{$field} = $value;
        }
    }

    return $record;
}

/*
|--------------------------------------------------------------------------
| Product image helper
|--------------------------------------------------------------------------
*/
function product_image_url($path)
{
    $fallback =
        'https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=1200';

    if (empty($path)) {

        return $fallback;
    }

    if (preg_match('/^https?:\/\//', $path)) {

        return $path;
    }

    $publicPrefix =
        '/ani-mart/public/';

    if (strpos($path, $publicPrefix) === 0) {

        $relativePath =
            substr($path, strlen($publicPrefix));

        $filePath =
            dirname(__DIR__, 2)
            . '/public/'
            . $relativePath;

        if (is_file($filePath)) {

            return $path;
        }

        return $fallback;
    }

    return $path;
}
/*
|--------------------------------------------------------------------------
| CSRF Token
|--------------------------------------------------------------------------
*/
function csrf_token()
{
    if (!isset($_SESSION['_token'])) {

        $_SESSION['_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_token'];
}

/*
|--------------------------------------------------------------------------
| Verify CSRF
|--------------------------------------------------------------------------
*/
function csrf_verify()
{
    $token = $_POST['_token']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? $_SERVER['HTTP_X_CSRF']
        ?? null;

    $expectedToken = $_SESSION['_token'] ?? null;

    if (
        !is_string($token)
        || !is_string($expectedToken)
        || !hash_equals($expectedToken, $token)
    ) {
        http_response_code(403);
        exit('CSRF token mismatch.');
    }
}
