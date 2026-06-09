<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PropertyUnit extends Model
{
    protected static string $table = 'property_units';

    public static function findActive(int $id): ?array
    {
        return Database::fetch(
            'SELECT * FROM property_units WHERE id = :id AND ' . Property::publicUnitCondition(''),
            ['id' => $id]
        );
    }
}
