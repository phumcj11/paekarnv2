<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Coupon extends Model
{
    protected static string $table = 'coupons';

    public static function findByCode(string $code): ?array
    {
        return Database::fetch("SELECT * FROM coupons WHERE code = :c LIMIT 1", ['c' => $code]);
    }

    public static function generateCode(): string
    {
        do {
            $code = 'PKAN-' . strtoupper(bin2hex(random_bytes(2))) . '-' . strtoupper(bin2hex(random_bytes(2)));
            $exists = Database::fetch("SELECT id FROM coupons WHERE code = :c", ['c' => $code]);
        } while ($exists);
        return $code;
    }

    public static function forCustomer(int $customerId): array
    {
        return Database::fetchAll(
            "SELECT * FROM coupons WHERE customer_id = :c ORDER BY id DESC",
            ['c' => $customerId]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function walletForCustomer(int $customerId): array
    {
        return Database::fetchAll(
            "SELECT * FROM coupons WHERE customer_id = :c AND status IN ('unused','reserved') ORDER BY id DESC",
            ['c' => $customerId]
        );
    }
}
