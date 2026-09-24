<?php

namespace App\Middleware;

use App\Core\Session;

class RoleMiddleware
{
    public static function handle($role)
    {
        $user = Session::get('user');

        if (!$user || $user['role'] !== $role) {
            \App\Helpers\ErrorResponse::unauthorized();
        }
    }
}