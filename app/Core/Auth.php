<?php
namespace App\Core;

class Auth
{
    private const KEY = 'auth_user';

    public static function attempt(string $email, string $password, ?string $role = null): bool
    {
        $sql = "SELECT * FROM users WHERE email = :email AND status = 'active'";
        $params = ['email' => $email];
        if ($role) { $sql .= " AND role = :role"; $params['role'] = $role; }

        $user = Database::fetch($sql, $params);
        if (!$user) return false;

        if (!password_verify($password, $user['password'])) return false;

        // rehash if cost change
        if (password_needs_rehash($user['password'], PASSWORD_BCRYPT)) {
            Database::update('users',
                ['password' => password_hash($password, PASSWORD_BCRYPT)],
                'id = :id',
                ['id' => $user['id']]
            );
        }

        Database::update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);

        unset($user['password']);
        Session::regenerate();
        Session::set(self::KEY, $user);
        return true;
    }

    public static function login(array $user): void
    {
        unset($user['password']);
        Session::regenerate();
        Session::set(self::KEY, $user);
    }

    public static function logout(): void
    {
        Session::remove(self::KEY);
        Session::regenerate();
    }

    public static function check(): bool
    {
        return Session::has(self::KEY);
    }

    public static function user(): ?array
    {
        return Session::get(self::KEY);
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function isAdmin(): bool    { return self::role() === 'admin'; }
    public static function isOwner(): bool    { return self::role() === 'owner'; }
    public static function isProvider(): bool { return self::role() === 'provider'; }
    public static function isCustomer(): bool { return self::role() === 'customer'; }

    public static function customerId(): ?int
    {
        if (!self::isCustomer()) return null;
        $row = Database::fetch('SELECT id FROM customers WHERE user_id = :uid', ['uid' => self::id()]);
        return $row['id'] ?? null;
    }

    public static function ownerId(): ?int
    {
        if (!self::isOwner()) return null;
        $row = Database::fetch('SELECT id FROM owners WHERE user_id = :uid', ['uid' => self::id()]);
        return $row['id'] ?? null;
    }

    public static function providerId(): ?int
    {
        if (!self::isProvider() && !self::isAdmin()) {
            return null;
        }
        if (!Database::tableHasColumn('activity_providers', 'user_id')) {
            return null;
        }
        $uid = self::id();
        if (!$uid) {
            return null;
        }
        $row = Database::fetch(
            'SELECT id FROM activity_providers WHERE user_id = :uid LIMIT 1',
            ['uid' => $uid]
        );

        return isset($row['id']) ? (int)$row['id'] : null;
    }

    /** @return array<string,mixed>|null */
    public static function providerRow(): ?array
    {
        $pid = self::providerId();
        if (!$pid || !Database::tableHasColumn('activity_providers', 'id')) {
            return null;
        }

        return Database::fetch('SELECT * FROM activity_providers WHERE id = :id', ['id' => $pid]);
    }

    public static function providerIsActive(): bool
    {
        $row = self::providerRow();
        if (!$row) {
            return false;
        }
        $ps = (string)($row['partner_status'] ?? 'pending');

        return $ps === 'active' && ($row['status'] ?? 'active') === 'active';
    }
}
