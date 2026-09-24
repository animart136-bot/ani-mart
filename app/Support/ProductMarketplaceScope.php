<?php

namespace App\Support;

class ProductMarketplaceScope
{
    public static function sql(string $alias = 'products'): string
    {
        $prefix = $alias === '' ? '' : $alias . '.';

        return "{$prefix}is_active = 1 AND {$prefix}approval_status = 'approved'";
    }
}
