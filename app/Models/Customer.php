<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Customer extends Model
{
    protected static string $table = 'customers';

    public static function findByUserId(int $userId): ?array
    {
        return Database::fetch('SELECT * FROM customers WHERE user_id = :u LIMIT 1', ['u' => $userId]);
    }

    public static function findWithUser(int $customerId): ?array
    {
        return Database::fetch(
            "SELECT c.*, u.name, u.email, u.phone, u.status, u.avatar, u.last_login_at,
                    u.line_user_id, u.notify_line, u.notify_email, u.created_at AS user_created_at
             FROM customers c
             JOIN users u ON u.id = c.user_id
             WHERE c.id = :id AND u.role = 'customer'",
            ['id' => $customerId]
        );
    }

    public static function ensureProfile(int $userId): int
    {
        $row = self::findByUserId($userId);
        if ($row) {
            return (int)$row['id'];
        }

        return Database::insert('customers', ['user_id' => $userId]);
    }
}
