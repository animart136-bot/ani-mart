<?php

namespace App\Middleware;

use App\Core\Session;
use App\Services\AuditLogService;

class RateLimitMiddleware
{
    public static function handle($key, $limit = 10, $seconds = 60, $message = null)
    {
        self::ensureSessionStarted();

        $time = time();
        $sessionKey = 'rate_limit_' . $key;

        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [];
        }

        $_SESSION[$sessionKey] = array_values(
            array_filter(
                $_SESSION[$sessionKey],
                fn($t) => $t > $time - $seconds
            )
        );

        if (count($_SESSION[$sessionKey]) >= $limit) {
            http_response_code(429);

            AuditLogService::log(
                'RATE_LIMIT_TRIGGERED',
                'security',
                null,
                [
                    'key' => $key,
                    'limit' => (int) $limit,
                    'seconds' => (int) $seconds,
                    'path' => $_SERVER['REQUEST_URI'] ?? null,
                    'reason' => 'rate_limit_exceeded'
                ],
                null,
                $_SERVER['REQUEST_URI'] ?? null,
                'failed'
            );

            if ($message !== null) {
                Session::flash('error', $message);
            }

            return false;
        }

        $_SESSION[$sessionKey][] = $time;

        return true;
    }

    public static function clear($key)
    {
        self::ensureSessionStarted();

        unset($_SESSION['rate_limit_' . $key]);
    }

    private static function ensureSessionStarted()
    {
        Session::start();
    }
}