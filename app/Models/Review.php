<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Review extends Model
{
    protected static string $table = 'reviews';

    public static function recalcProperty(int $propertyId): void
    {
        $prop = Database::fetch(
            'SELECT rating_locked FROM properties WHERE id = :id',
            ['id' => $propertyId]
        );
        if ($prop && !empty($prop['rating_locked'])) {
            return;
        }

        $row = Database::fetch(
            "SELECT AVG(rating) AS avg, COUNT(*) AS cnt FROM reviews
             WHERE property_id = :id AND is_approved=1",
            ['id' => $propertyId]
        );
        Database::update('properties', [
            'rating_avg'   => round((float)($row['avg'] ?? 0), 2),
            'rating_count' => (int)($row['cnt'] ?? 0),
        ], 'id = :id', ['id' => $propertyId]);
    }

    public static function latest(int $limit = 6): array
    {
        return Database::fetchAll(
            "SELECT r.*, p.name AS property_name, p.slug AS property_slug, p.cover_image
             FROM reviews r
             JOIN properties p ON p.id = r.property_id
             WHERE r.is_approved = 1
             ORDER BY r.created_at DESC LIMIT $limit"
        );
    }
}
