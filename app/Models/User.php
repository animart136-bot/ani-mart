<?php

namespace App\Models;

use App\Core\Model;
use App\Services\SensitiveDataCipher;

class User extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Create User
    |--------------------------------------------------------------------------
    */
    public function create($data)
    {
        $sql = "
            INSERT INTO users (

                first_name,
                first_name_encrypted,
                last_name,
                last_name_encrypted,
                email,
                email_encrypted,
                password,
                role

            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $cipher = new SensitiveDataCipher();

        return $this->query($sql, [

            $data['first_name'],
            $cipher->encrypt($data['first_name'], 'users.first_name'),
            $data['last_name'],
            $cipher->encrypt($data['last_name'], 'users.last_name'),
            $data['email'],
            $cipher->encrypt($data['email'], 'users.email'),
            $data['password'],
            $data['role']
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Find By Email
    |--------------------------------------------------------------------------
    */
    public function findByEmail($email)
    {
        return $this->hydrateSensitive($this->fetchOne(
            "
            SELECT *
            FROM users
            WHERE email = ?
            ",
            [$email]
        ));
    }

    public function findById($id)
    {
        return $this->hydrateSensitive($this->fetchOne(
            "
            SELECT *
            FROM users
            WHERE id = ?
            LIMIT 1
            ",
            [$id]
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Farmers
    |--------------------------------------------------------------------------
    */
    public function farmers()
    {
        return $this->hydrateMany($this->fetchAll(
            "
            SELECT *
            FROM users
            WHERE role='farmer'
            ORDER BY created_at DESC
            "
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Customers
    |--------------------------------------------------------------------------
    */
    public function customers()
    {
        return $this->hydrateMany($this->fetchAll(
            "
            SELECT *
            FROM users
            WHERE role='customer'
            ORDER BY created_at DESC
            "
        ));
    }

    public function newUsers($days = 7, $role = null)
    {
        $sql = "SELECT * FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params = [(int) $days];

        if ($role && in_array($role, ['customer', 'farmer'], true)) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }

        $sql .= " ORDER BY created_at DESC";

        return $this->hydrateMany($this->fetchAll($sql, $params));
    }

    private function hydrateSensitive($record)
    {
        return hydrate_sensitive_fields($record, 'users', [
            'first_name', 'last_name', 'email', 'latitude', 'longitude',
            'address', 'barangay', 'municipality', 'province', 'phone',
            'suspension_reason',
        ]);
    }

    private function hydrateMany(array $records): array
    {
        return array_map(fn($record) => $this->hydrateSensitive($record), $records);
    }
}
