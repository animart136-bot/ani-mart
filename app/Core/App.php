<?php

namespace App\Core;

use App\Core\Env;

class App
{
    public static function init(): void
    {
        self::loadEnvironment();
        date_default_timezone_set((string) ($_ENV['APP_TIMEZONE'] ?? 'Asia/Manila'));
        self::configureErrorHandling();
        self::registerHandlers();
    }

    public static function isProduction(): bool
    {
        $appEnv = trim((string) ($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: ''));

        return strtolower($appEnv) === 'production';
    }

    private static function loadEnvironment(): void
    {
        Env::load(__DIR__ . '/../../.env');
    }

    private static function configureErrorHandling(): void
    {
        $appEnv = strtolower(trim((string) ($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: '')));
        $showErrors = in_array($appEnv, ['local', 'development', 'dev', 'testing'], true);

        if (!$showErrors) {
            error_reporting(E_ALL);
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            ini_set('log_errors', '1');
            ini_set('error_log', __DIR__ . '/../../storage/logs/app.log');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            ini_set('log_errors', '1');
            ini_set('error_log', __DIR__ . '/../../storage/logs/app.log');
        }
    }

    private static function registerHandlers(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException(\Throwable $exception): void
    {
        self::logThrowable($exception);

        if (!headers_sent()) {
            http_response_code(500);
        }

        if (self::isProduction()) {
            echo 'Something went wrong. Please try again later.';
            exit(1);
        }

        echo '<pre>' . htmlspecialchars(
            sprintf(
                "Unhandled Exception: %s in %s on line %d\n\nStack trace:\n%s",
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString()
            ),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ) . '</pre>';
        exit(1);
    }

    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        error_log(sprintf('PHP error [%d]: %s in %s on line %d', $errno, $errstr, $errfile, $errline));

        return self::isProduction();
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error === null) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array($error['type'], $fatalTypes, true)) {
            return;
        }

        error_log(sprintf('Fatal error [%d]: %s in %s on line %d', $error['type'], $error['message'], $error['file'], $error['line']));

        if (!headers_sent()) {
            http_response_code(500);
        }

        if (self::isProduction()) {
            echo 'Something went wrong. Please try again later.';
            return;
        }

        echo '<pre>' . htmlspecialchars(
            sprintf('Fatal error [%d]: %s in %s on line %d', $error['type'], $error['message'], $error['file'], $error['line']),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ) . '</pre>';
    }

    private static function logThrowable(\Throwable $exception): void
    {
        error_log(sprintf(
            '[%s] Unhandled Exception: %s in %s on line %d\n%s',
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        ));
    }
}
