<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

/**
 * บันทึกเหตุการณ์หลังบ้าน — ต้องมีตาราง audit_logs (ดู database/migration_audit_logs.sql)
 */
class AuditLog
{
    /**
     * @param array<string,mixed>|null $payload
     */
    public static function record(string $action, ?array $payload = null, ?string $entityType = null, ?int $entityId = null): void
    {
        try {
            $user = Auth::user();
            Database::insert('audit_logs', [
                'created_at'    => date('Y-m-d H:i:s'),
                'actor_user_id' => isset($user['id']) ? (int)$user['id'] : null,
                'actor_email'   => isset($user['email']) ? substr((string)$user['email'], 0, 160) : null,
                'action'        => substr($action, 0, 64),
                'entity_type'   => $entityType !== null ? substr($entityType, 0, 64) : null,
                'entity_id'     => $entityId,
                'ip'            => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
                'user_agent'    => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
                'payload'       => $payload !== null && $payload !== [] ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            ]);
        } catch (\Throwable) {
            // ไม่ให้กระทบ flow หลักถ้ายังไม่รัน migration หรือ DB error
        }
    }
}
