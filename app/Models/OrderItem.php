<?php

namespace App\Models;

use App\Core\Model;

class OrderItem extends Model
{
    public function create($data)
    {
        $sql = "
            INSERT INTO order_items (
                order_id,
                product_id,
                seller_profile_id,
                quantity,
                price,
                subtotal
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ";

        return $this->query($sql, [
            $data['order_id'],
            $data['product_id'],
            $data['seller_profile_id'],
            $data['quantity'],
            $data['price'],
            $data['subtotal']
        ]);
    }
}