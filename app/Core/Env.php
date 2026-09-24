<?php

namespace App\Core;

class Env
{
    public static function load($path)
    {
        if (!file_exists($path)) return;

        $lines = file($path);

        foreach ($lines as $line) {

            if (strpos(trim($line), '#') === 0 || trim($line) === '') {
                continue;
            }

            [$key, $value] = explode('=', trim($line), 2);

            $key = trim($key);
            $value = trim($value);

            /*
            |------------------------------------------------------------------
            | Allow standard quoted .env values
            |------------------------------------------------------------------
            |
            | App passwords are commonly written as
            | MAIL_PASSWORD="abcd efgh ijkl mnop". Keeping the quotes makes
            | SMTP authentication fail because they become part of the password.
            |
            */
            if (
                strlen($value) >= 2 &&
                (($value[0] === '"' && substr($value, -1) === '"') ||
                    ($value[0] === "'" && substr($value, -1) === "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            if (getenv($key) === false && !isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }

            if (!isset($_SERVER[$key])) {
                $_SERVER[$key] = $value;
            }
        }
    }
}
