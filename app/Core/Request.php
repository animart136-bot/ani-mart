<?php

namespace App\Core;

use App\Core\Sanitizer;

class Request
{
    /*
    |--------------------------------------------------------------------------
    | Get POST/GET Input (SANITIZED)
    |--------------------------------------------------------------------------
    */
    public static function input($key)
    {
        $value =
            $_POST[$key]
            ?? $_GET[$key]
            ?? null;

        return Sanitizer::clean($value);
    }

    /*
    |--------------------------------------------------------------------------
    | Get all POST data (optional helper)
    |--------------------------------------------------------------------------
    */
    public static function all()
    {
        return Sanitizer::clean($_POST);
    }

    /*
    |--------------------------------------------------------------------------
    | Check request method
    |--------------------------------------------------------------------------
    */
    public static function method()
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /*
    |--------------------------------------------------------------------------
    | Check if POST
    |--------------------------------------------------------------------------
    */
    public static function isPost()
    {
        return self::method() === 'POST';
    }
}