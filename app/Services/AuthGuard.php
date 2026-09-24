<?php

namespace App\Services;

use App\Core\Session;

final class AuthGuard
{
    /**
     * Require a signed-in user and optionally limit access to one or more roles.
     * Controllers keep deciding the destination; this class makes the rule itself
     * consistent across the application.
     */
    public static function user(array|string|null $roles = null, ?string $redirect = null): array
    {
        $user = Session::get('user');
        $allowedRoles = $roles === null ? [] : array_map('strtolower', (array) $roles);
        $role = strtolower((string) ($user['role'] ?? ''));

        if (!$user || ($allowedRoles !== [] && !in_array($role, $allowedRoles, true))) {
            Session::flash('error', $user ? 'You do not have permission to access that page.' : 'Please sign in to continue.');
            header('Location: ' . ($redirect ?? app_url('login')));
            exit;
        }

        return $user;
    }

    public static function admin(): array
    {
        return self::user('admin');
    }

    public static function farmer(): array
    {
        return self::user('farmer');
    }

    public static function customer(): array
    {
        return self::user('customer');
    }

    public static function hasRole(string ...$roles): bool
    {
        $user = Session::get('user');
        return $user && in_array(
            strtolower((string) ($user['role'] ?? '')),
            array_map('strtolower', $roles),
            true
        );
    }
}
