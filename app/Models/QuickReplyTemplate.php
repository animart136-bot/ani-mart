<?php

namespace App\Models;

use App\Core\Model;

class QuickReplyTemplate extends Model
{
    public function all()
    {
        return $this->fetchAll(
            "SELECT * FROM quick_reply_templates ORDER BY title ASC"
        );
    }

    public function findById($id)
    {
        return $this->fetchOne(
            "SELECT * FROM quick_reply_templates WHERE id = ? LIMIT 1",
            [$id]
        );
    }
}
