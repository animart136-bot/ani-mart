<?php

namespace App\Models;

use App\Core\Model;

class CommunityCategory extends Model
{
    public function all()
    {
        return $this->fetchAll(
            "
            SELECT *
            FROM community_categories
            ORDER BY name ASC
            "
        );
    }

    public function find($id)
    {
        return $this->fetchOne(
            "
            SELECT *
            FROM community_categories
            WHERE id = ?
            LIMIT 1
            ",
            [$id]
        );
    }
}
