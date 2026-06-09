<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class MembershipPlan extends Model
{
    protected static string $table = 'membership_plans';

    /** @return array<string,mixed>|null */
    public static function findByCode(string $code): ?array
    {
        return Database::fetch(
            'SELECT * FROM membership_plans WHERE code = :c LIMIT 1',
            ['c' => $code]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function activeOrdered(): array
    {
        return Database::fetchAll(
            'SELECT * FROM membership_plans WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
    }
}
