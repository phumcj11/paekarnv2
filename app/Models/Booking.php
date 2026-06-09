<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Booking extends Model
{
    protected static string $table = 'bookings';

    public static function generateCode(): string
    {
        return 'BK' . date('ymd') . strtoupper(bin2hex(random_bytes(2)));
    }

    public static function forCustomer(int $customerId): array
    {
        return Database::fetchAll(
            "SELECT b.*, p.name AS property_name, p.cover_image, p.slug AS property_slug, u.name AS unit_name
             FROM bookings b
             JOIN properties p ON p.id = b.property_id
             LEFT JOIN property_units u ON u.id = b.unit_id
             WHERE b.customer_id = :c
             ORDER BY b.created_at DESC",
            ['c' => $customerId]
        );
    }
}
