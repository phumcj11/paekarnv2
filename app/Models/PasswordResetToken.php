<?php
namespace App\Models;

use App\Core\Database;

class PasswordResetToken
{
    private const TTL_MINUTES = 60;

    /** สร้าง token ใหม่ คืน plain token สำหรับส่งในอีเมล */
    public static function create(int $userId): string
    {
        // ยกเลิก token เก่าที่ยังไม่ใช้
        Database::query(
            "UPDATE password_reset_tokens SET used_at = NOW()
             WHERE user_id = :uid AND used_at IS NULL",
            ['uid' => $userId]
        );

        $plain = bin2hex(random_bytes(32));
        $hash  = hash('sha256', $plain);

        Database::insert('password_reset_tokens', [
            'user_id'    => $userId,
            'token_hash' => $hash,
            'expires_at' => date('Y-m-d H:i:s', time() + self::TTL_MINUTES * 60),
        ]);

        return $plain;
    }

    /** ค้นหา token ที่ยังใช้ได้ */
    public static function findValid(string $plainToken): ?array
    {
        $plainToken = trim($plainToken);
        if ($plainToken === '') return null;

        $hash = hash('sha256', $plainToken);
        return Database::fetch(
            "SELECT * FROM password_reset_tokens
             WHERE token_hash = :h AND used_at IS NULL AND expires_at > NOW()
             LIMIT 1",
            ['h' => $hash]
        );
    }

    public static function markUsed(int $id): void
    {
        Database::update('password_reset_tokens', ['used_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
    }
}
