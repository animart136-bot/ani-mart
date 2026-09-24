<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    /*
    |--------------------------------------------------------------------------
    | Singleton Instance
    |--------------------------------------------------------------------------
    */
    private static $instance = null;

    /*
    |--------------------------------------------------------------------------
    | PDO Connection
    |--------------------------------------------------------------------------
    */
    private $connection;

    /*
    |--------------------------------------------------------------------------
    | Private Constructor
    |--------------------------------------------------------------------------
    */
    private function __construct()
    {
        $env = static function (string $key, string $default): string {
            $value = $_ENV[$key] ?? getenv($key) ?: $_SERVER[$key] ?? $default;
            return trim((string) $value);
        };

        $host = $env('DB_HOST', 'localhost');
        $port = $env('DB_PORT', '3306');
        $database = $env('DB_DATABASE', 'ani_mart');
        $username = $env('DB_USERNAME', 'root');
        $password = $env('DB_PASSWORD', '');

        try {

            $this->connection = new PDO(

                "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",

                $username,

                $password,

                [

                    PDO::ATTR_ERRMODE
                        => PDO::ERRMODE_EXCEPTION,

                    PDO::ATTR_DEFAULT_FETCH_MODE
                        => PDO::FETCH_OBJ,

                    PDO::ATTR_EMULATE_PREPARES
                        => false
                ]
            );

        } catch (PDOException $e) {

            throw new \RuntimeException(
                'Database Connection Failed.',
                0,
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Singleton Access
    |--------------------------------------------------------------------------
    */
    public static function getInstance()
    {
        if (!self::$instance) {

            self::$instance =
                new self();
        }

        return self::$instance;
    }

    /*
    |--------------------------------------------------------------------------
    | Get PDO Connection
    |--------------------------------------------------------------------------
    */
    public function connect()
    {
        return $this->connection;
    }

    /*
    |--------------------------------------------------------------------------
    | Static Shortcut
    |--------------------------------------------------------------------------
    */
    public static function connection()
    {
        return self::getInstance()
            ->connect();
    }
}