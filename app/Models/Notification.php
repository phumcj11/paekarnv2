<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Notification extends Model
{
    protected static string $table = 'notifications';

    public static function unreadCount(int $userId): int
    {
        $r = Database::fetch(
            "SELECT COUNT(*) c FROM notifications WHERE user_id = :u AND channel='in_app' AND read_at IS NULL",
            ['u' => $userId]
        );
        return (int)($r['c'] ?? 0);
    }

    public static function recentForUser(int $userId, int $limit = 10): array
    {
        $limit = max(1, (int)$limit);
        return Database::fetchAll(
            "SELECT * FROM notifications WHERE user_id = :u AND channel='in_app'
             ORDER BY id DESC LIMIT $limit",
            ['u' => $userId]
        );
    }

    public static function markRead(int $userId, ?int $id = null): void
    {
        if ($id) {
            Database::query("UPDATE notifications SET read_at = NOW() WHERE id = :i AND user_id = :u",
                ['i' => $id, 'u' => $userId]);
        } else {
            Database::query("UPDATE notifications SET read_at = NOW() WHERE user_id = :u AND read_at IS NULL",
                ['u' => $userId]);
        }
    }
}
