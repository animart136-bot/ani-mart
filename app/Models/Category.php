<?php

namespace App\Models;

use App\Core\Model;

class Category extends Model
{
    public function all()
    {
        (new \App\Services\CategoryAdminService())->ensureSchema();

        return $this->fetchAll(
            "SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC"
        );
    }
}
