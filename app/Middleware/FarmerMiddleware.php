<?php

namespace App\Middleware;

use App\Core\Session;

class FarmerMiddleware
{
    public static function handle()
    {
        $user = Session::get('user');

        if (!$user || $user['role'] !== 'farmer') {
            \App\Helpers\ErrorResponse::unauthorized('Farmer access only.');
        }
    }
}