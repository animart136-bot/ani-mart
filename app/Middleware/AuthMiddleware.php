<?php

namespace App\Middleware;

use App\Core\Session;

class AuthMiddleware
{
    public static function handle()
    {
        Session::start();

        if (!isset($_SESSION['user'])) {
            header("Location: /ani-mart/public/login");
            exit;
        }
    }
}