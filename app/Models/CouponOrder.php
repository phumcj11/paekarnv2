<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class CouponOrder extends Model
{
    protected static string $table = 'coupon_orders';

    public static function generateOrderNo(): string
    {
        return 'PKO-' . date('Ym') . '-' . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
