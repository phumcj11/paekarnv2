<?php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo) return self::$pdo;

        $cfg = Application::$config['db'];
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['driver'], $cfg['host'], (int)$cfg['port'], $cfg['database'], $cfg['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE             => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES    => false,
            PDO::MYSQL_ATTR_INIT_COMMAND  => "SET NAMES {$cfg['charset']} COLLATE {$cfg['collation']}",
        ];

        try {
            self::$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], $options);
        } catch (PDOException $e) {
            // โชว์หน้า install เพื่อช่วย user
            self::showConnectionError($e->getMessage(), $cfg);
            exit;
        }
        return self::$pdo;
    }

    /** helper สั้น */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /** true ถ้ามีคอลัมน์ในตารางของ schema ปัจจุบัน (ใช้หลบ schema เก่าที่ยังไม่มี unit_id เป็นต้น) */
    public static function tableHasColumn(string $table, string $column): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            throw new \InvalidArgumentException('Invalid table or column name');
        }
        static $cache = [];
        $key = strtolower($table . '.' . $column);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        try {
            // SHOW COLUMNS มักใช้ได้บน shared hosting ที่จำกัด / แปลกผลกับ INFORMATION_SCHEMA
            $quoted = self::pdo()->quote($column);
            $stmt = self::pdo()->query("SHOW COLUMNS FROM `{$table}` LIKE {$quoted}");
            if ($stmt === false) {
                throw new \RuntimeException('SHOW COLUMNS query failed');
            }
            $cache[$key] = $stmt->fetch() !== false;
        } catch (\Throwable $e) {
            try {
                $row = self::fetch(
                    'SELECT 1 AS ok FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1',
                    ['t' => $table, 'c' => $column]
                );
                $cache[$key] = $row !== null;
            } catch (\Throwable $e2) {
                error_log('[Paekan] tableHasColumn(' . $table . '.' . $column . '): ' . $e->getMessage() . ' | ' . $e2->getMessage());
                $cache[$key] = false;
            }
        }

        return $cache[$key];
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $place = array_map(fn($c) => ":$c", $cols);
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table, implode('`,`', $cols), implode(',', $place)
        );
        self::query($sql, $data);
        return (int)self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = [];
        foreach (array_keys($data) as $c) $set[] = "`$c` = :$c";
        $sql = sprintf('UPDATE `%s` SET %s WHERE %s', $table, implode(', ', $set), $where);
        return self::query($sql, array_merge($data, $whereParams))->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        return self::query("DELETE FROM `$table` WHERE $where", $params)->rowCount();
    }

    private static function showConnectionError(string $msg, array $cfg): void
    {
        http_response_code(500);
        $base = Application::$publicUrl ?? '';
        echo <<<HTML
<!DOCTYPE html><html lang="th"><head>
<meta charset="utf-8"><title>Database Connection Error</title>
<link rel="stylesheet" href="{$base}/assets/css/app.css">
</head><body class="bg-slate-50">
<div class="max-w-2xl mx-auto py-16 px-6">
<div class="bg-white rounded-xl shadow border border-slate-200 p-8">
<h1 class="text-2xl font-bold text-rose-600 mb-2">เชื่อมต่อฐานข้อมูลไม่ได้</h1>
<p class="text-slate-600 mb-4">ตรวจสอบว่า MySQL ใน XAMPP เปิดอยู่ และสร้างฐานข้อมูลตามขั้นตอนแล้ว</p>
<div class="bg-slate-100 rounded-lg p-4 text-sm font-mono text-slate-700 mb-4">
host: {$cfg['host']}<br>port: {$cfg['port']}<br>db: {$cfg['database']}<br>user: {$cfg['username']}
</div>
<p class="text-sm text-slate-500 mb-6">Error: <span class="text-slate-700">$msg</span></p>
<a href="{$base}/install.php" class="inline-block px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
🚀 เปิดหน้า Install ระบบ
</a>
</div>
</div>
</body></html>
HTML;
    }
}
