<?php

namespace App\Core;

class Validator
{
    public static function required($value)
    {
        return trim($value) !== '';
    }

    public static function email($value)
    {
        return filter_var(
            $value,
            FILTER_VALIDATE_EMAIL
        );
    }

    public static function min($value, $length)
    {
        return strlen($value) >= $length;
    }
}