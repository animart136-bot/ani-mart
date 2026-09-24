<?php

namespace App\Core;

use App\Core\Database;
use PDO;

class Model
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->connect();
    }

    /*
    |--------------------------------------------------------------------------
    | Basic Query Helper
    |--------------------------------------------------------------------------
    */
    public function query($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /*
    |--------------------------------------------------------------------------
    | Fetch All
    |--------------------------------------------------------------------------
    */
    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_OBJ);
    }

    /*
    |--------------------------------------------------------------------------
    | Fetch One
    |--------------------------------------------------------------------------
    */
    public function fetchOne($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch(PDO::FETCH_OBJ);
    }
}