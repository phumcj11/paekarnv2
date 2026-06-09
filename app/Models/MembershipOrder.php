<?php

namespace App\Models;

use App\Core\Model;

class MembershipOrder extends Model
{
    protected static string $table = 'membership_orders';

    public static function generateOrderNo(): string
    {
        return 'MBR-' . date('Ym') . '-' . str_pad((string)random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    }
}
