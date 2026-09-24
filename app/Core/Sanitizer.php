<?php

namespace App\Core;

class Sanitizer
{
    public static function clean($data)
    {
        if (is_array($data)) {

            return array_map(
                [self::class, 'clean'],
                $data
            );
        }

        return htmlspecialchars(
            trim($data),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}