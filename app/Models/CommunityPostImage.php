<?php

namespace App\Models;

use App\Core\Model;

class CommunityPostImage extends Model
{
    public function create($postId, $imageUrl)
    {
        return $this->query(
            "INSERT INTO community_post_images (post_id, image_url) VALUES (?, ?)",
            [$postId, $imageUrl]
        );
    }
}
