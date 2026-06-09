<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Property;
use App\Support\PropertyBookingCapabilities;
use App\Support\UnitPricing;

class CompareService
{
    public const MAX_ITEMS = 5;
    private const STORAGE_TYPES = ['raft', 'pool_villa'];

    /**
     * @param array<int,mixed> $items
     * @return array<int,array{property_id:int,unit_id:int,added_at:string|null}>
     */
    public static function normalizeItems(array $items): array
    {
        $out = [];
        $seen = [];

        foreach ($items as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $propertyId = (int)($raw['property_id'] ?? $raw['propertyId'] ?? $raw['p'] ?? 0);
            $unitId = (int)($raw['unit_id'] ?? $raw['unitId'] ?? $raw['u'] ?? 0);
            if ($propertyId <= 0 || $unitId <= 0) {
                continue;
            }
            $key = $propertyId . ':' . $unitId;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = [
                'property_id' => $propertyId,
                'unit_id' => $unitId,
                'added_at' => isset($raw['added_at']) ? (string)$raw['added_at'] : (isset($raw['addedAt']) ? (string)$raw['addedAt'] : null),
            ];
            if (count($out) >= self::MAX_ITEMS) {
                break;
            }
        }

        return $out;
    }

    /** @return array<int,array{property_id:int,unit_id:int,added_at:string|null}> */
    public static function parseQueryItems(?string $encoded): array
    {
        $encoded = trim((string)$encoded);
        if ($encoded === '') {
            return [];
        }

        $items = [];
        foreach (explode(',', $encoded) as $pair) {
            $pair = trim($pair);
            if ($pair === '' || !preg_match('/^(\d+)-(\d+)$/', $pair, $m)) {
                continue;
            }
            $items[] = ['property_id' => (int)$m[1], 'unit_id' => (int)$m[2], 'added_at' => null];
        }

        return self::normalizeItems($items);
    }

    /** @param array<int,array<string,mixed>> $items */
    public static function encodeItems(array $items): string
    {
        $pairs = [];
        foreach (self::normalizeItems($items) as $item) {
            $pairs[] = $item['property_id'] . '-' . $item['unit_id'];
        }

        return implode(',', $pairs);
    }

    /** @return array<int,array<string,mixed>> */
    public static function customerItems(int $customerId): array
    {
        if ($customerId <= 0 || !self::tableReady()) {
            return [];
        }

        $rows = Database::fetchAll(
            'SELECT property_id, unit_id, created_at AS added_at
             FROM compare_items
             WHERE customer_id = :c
             ORDER BY created_at DESC, id DESC
             LIMIT ' . self::MAX_ITEMS,
            ['c' => $customerId]
        );

        return self::normalizeItems($rows);
    }

    /**
     * @param array<int,mixed> $incoming
     * @return array{items:array<int,array<string,mixed>>,rows:array<int,array<string,mixed>>,expired:int,db_ready:bool}
     */
    public static function syncCustomer(int $customerId, array $incoming): array
    {
        $incoming = self::normalizeItems($incoming);
        $dbReady = self::tableReady();
        if ($customerId <= 0 || !$dbReady) {
            $rows = self::loadCompareRows($incoming);
            return [
                'items' => $incoming,
                'rows' => $rows,
                'expired' => max(0, count($incoming) - count($rows)),
                'db_ready' => $dbReady,
            ];
        }

        $merged = [];
        foreach (array_merge($incoming, self::customerItems($customerId)) as $item) {
            $key = (int)$item['property_id'] . ':' . (int)$item['unit_id'];
            if (!isset($merged[$key])) {
                $merged[$key] = $item;
            }
            if (count($merged) >= self::MAX_ITEMS) {
                break;
            }
        }

        Database::delete('compare_items', 'customer_id = :c', ['c' => $customerId]);
        foreach (array_values($merged) as $item) {
            Database::query(
                'INSERT INTO compare_items (customer_id, property_id, unit_id, created_at)
                 VALUES (:c, :p, :u, NOW())',
                ['c' => $customerId, 'p' => (int)$item['property_id'], 'u' => (int)$item['unit_id']]
            );
        }

        $items = self::customerItems($customerId);
        $rows = self::loadCompareRows($items);

        return [
            'items' => $items,
            'rows' => $rows,
            'expired' => max(0, count($items) - count($rows)),
            'db_ready' => true,
        ];
    }

    /**
     * @param array<int,mixed> $items
     * @return array<int,array<string,mixed>>
     */
    public static function loadCompareRows(array $items): array
    {
        $items = self::normalizeItems($items);
        if ($items === []) {
            return [];
        }

        $clauses = [];
        $params = [];
        foreach ($items as $i => $item) {
            $clauses[] = "(p.id = :p{$i} AND u.id = :u{$i})";
            $params["p{$i}"] = (int)$item['property_id'];
            $params["u{$i}"] = (int)$item['unit_id'];
        }

        $typePlaceholders = [];
        foreach (self::STORAGE_TYPES as $i => $type) {
            $key = 'type' . $i;
            $typePlaceholders[] = ':' . $key;
            $params[$key] = $type;
        }

        $priceIncludesSelect = self::unitPriceIncludesSelect();
        $capabilitySelect = self::capabilityColumnsSelect();
        $rows = Database::fetchAll(
            'SELECT
                p.id AS property_id,
                p.slug,
                p.name AS property_name,
                p.type,
                p.raft_variant,
                p.province,
                p.district,
                p.zone,
                p.cover_image AS property_cover_image,
                p.pet_policy,
                p.coupon_enabled,
                ' . $capabilitySelect . '
                p.rating_avg,
                p.rating_count,
                p.booking_mode,
                p.phone,
                u.id AS unit_id,
                u.name AS unit_name,
                u.description AS unit_description,
                u.cover_image AS unit_cover_image,
                u.capacity_min,
                u.capacity_max,
                u.bedrooms,
                u.bathrooms,
                u.area_sqm,
                u.price,
                u.price_weekend,
                u.price_holiday,
                ' . $priceIncludesSelect . ' AS price_includes_guests,
                u.extra_person_fee
             FROM properties p
             JOIN property_units u ON u.property_id = p.id
             WHERE p.status = "published"
               AND p.type IN (' . implode(',', $typePlaceholders) . ')
               AND ' . Property::publicUnitCondition('u') . '
               AND (' . implode(' OR ', $clauses) . ')',
            $params
        );

        $byKey = [];
        foreach ($rows as $row) {
            $row = self::decorateRow($row);
            $byKey[(int)$row['property_id'] . ':' . (int)$row['unit_id']] = $row;
        }

        $ordered = [];
        foreach ($items as $item) {
            $key = (int)$item['property_id'] . ':' . (int)$item['unit_id'];
            if (isset($byKey[$key])) {
                $ordered[] = $byKey[$key];
            }
        }

        return $ordered;
    }

    public static function tableReady(): bool
    {
        try {
            return Database::tableHasColumn('compare_items', 'unit_id');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @param array<string,mixed> $row */
    private static function decorateRow(array $row): array
    {
        $unit = [
            'price' => (float)($row['price'] ?? 0),
            'capacity_min' => (int)($row['capacity_min'] ?? 0),
            'capacity_max' => (int)($row['capacity_max'] ?? 0),
            'price_includes_guests' => $row['price_includes_guests'] ?? null,
            'extra_person_fee' => (float)($row['extra_person_fee'] ?? 0),
        ];
        $propertyId = (int)$row['property_id'];
        $unitId = (int)$row['unit_id'];
        $cover = (string)($row['unit_cover_image'] ?: $row['property_cover_image'] ?: '');

        $row['property_id'] = $propertyId;
        $row['unit_id'] = $unitId;
        $row['price'] = (float)($row['price'] ?? 0);
        $row['capacity_min'] = (int)($row['capacity_min'] ?? 0);
        $row['capacity_max'] = (int)($row['capacity_max'] ?? 0);
        $row['bedrooms'] = (int)($row['bedrooms'] ?? 0);
        $row['bathrooms'] = (int)($row['bathrooms'] ?? 0);
        $row['area_sqm'] = isset($row['area_sqm']) && $row['area_sqm'] !== null ? (float)$row['area_sqm'] : null;
        $row['coupon_enabled'] = (int)($row['coupon_enabled'] ?? 0);
        $row['rating_avg'] = (float)($row['rating_avg'] ?? 0);
        $row['rating_count'] = (int)($row['rating_count'] ?? 0);
        $row['cover_url'] = upload_img($cover, 'thumb') ?: 'https://placehold.co/800x600?text=Paekan';
        $row['detail_url'] = url('/property/' . $row['slug'] . '?unit=' . $unitId);
        $ctaUrls = PropertyBookingCapabilities::urlsForUnit($row, $propertyId, $unitId);
        $row['book_url'] = $ctaUrls['book_online'] ?? '';
        $row['buy_coupon_url'] = $ctaUrls['buy_coupon'] ?? '';
        $row['phone_url'] = $ctaUrls['contact'] ?? (trim((string)($row['phone'] ?? '')) !== '' ? 'tel:' . trim((string)$row['phone']) : '');
        $row['primary_action_url'] = $row['book_url'] ?: ($row['phone_url'] ?: '');
        $row['price_label'] = UnitPricing::formatCardPrice($unit) !== '' ? UnitPricing::formatCardPrice($unit) : format_money($row['price']);
        $row['price_note'] = UnitPricing::guestPriceNote($unit);
        $row['amenities'] = self::amenitiesFor($propertyId, $unitId);
        $row['key'] = $propertyId . ':' . $unitId;

        return $row;
    }

    private static function unitPriceIncludesSelect(): string
    {
        try {
            return Database::tableHasColumn('property_units', 'price_includes_guests')
                ? 'u.price_includes_guests'
                : 'NULL';
        } catch (\Throwable $e) {
            return 'NULL';
        }
    }

    private static function capabilityColumnsSelect(): string
    {
        try {
            return Database::tableHasColumn('properties', 'allow_contact')
                ? 'p.allow_contact, p.allow_online_booking, p.booking_requires_payment,'
                : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** @return array<int,array{name:string,icon:string}> */
    private static function amenitiesFor(int $propertyId, int $unitId): array
    {
        $rows = Database::fetchAll(
            '(SELECT a.name, COALESCE(NULLIF(a.icon, ""), "check-circle-2") AS icon, a.sort_order
              FROM unit_amenities ua
              JOIN amenities a ON a.id = ua.amenity_id
              WHERE ua.unit_id = :u)
             UNION
             (SELECT a.name, COALESCE(NULLIF(a.icon, ""), "check-circle-2") AS icon, a.sort_order
              FROM property_amenities pa
              JOIN amenities a ON a.id = pa.amenity_id
              WHERE pa.property_id = :p)
             ORDER BY sort_order ASC, name ASC
             LIMIT 8',
            ['u' => $unitId, 'p' => $propertyId]
        );

        return array_map(static fn(array $r): array => [
            'name' => (string)$r['name'],
            'icon' => (string)($r['icon'] ?: 'check-circle-2'),
        ], $rows);
    }
}
