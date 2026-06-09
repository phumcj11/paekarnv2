<?php
namespace App\Core;

abstract class Model
{
    protected static string $table = '';
    protected static string $pk    = 'id';

    public static function find($id): ?array
    {
        return Database::fetch(
            "SELECT * FROM `" . static::$table . "` WHERE `" . static::$pk . "` = :id LIMIT 1",
            ['id' => $id]
        );
    }

    public static function all(string $orderBy = 'id', string $dir = 'DESC'): array
    {
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';
        return Database::fetchAll("SELECT * FROM `" . static::$table . "` ORDER BY `$orderBy` $dir");
    }

    public static function create(array $data): int
    {
        return Database::insert(static::$table, $data);
    }

    public static function update($id, array $data): int
    {
        return Database::update(static::$table, $data, static::$pk . " = :id_pk", ['id_pk' => $id]);
    }

    public static function destroy($id): int
    {
        return Database::delete(static::$table, static::$pk . " = :id", ['id' => $id]);
    }

    public static function count(string $where = '1=1', array $params = []): int
    {
        return (int)Database::fetch("SELECT COUNT(*) AS c FROM `" . static::$table . "` WHERE $where", $params)['c'];
    }
}
