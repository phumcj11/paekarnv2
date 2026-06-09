<?php

namespace App\Support;

class UnitPricing
{
    public static function includedGuests(array $unit): int
    {
        if (isset($unit['price_includes_guests']) && $unit['price_includes_guests'] !== null && $unit['price_includes_guests'] !== '') {
            return max(1, (int)$unit['price_includes_guests']);
        }
        return max(1, (int)($unit['capacity_max'] ?? 1));
    }

    public static function extraGuestsPerNight(int $guests, array $unit): int
    {
        $fee = (float)($unit['extra_person_fee'] ?? 0);
        if ($fee <= 0) {
            return 0;
        }
        $included = self::includedGuests($unit);
        if ($guests <= $included) {
            return 0;
        }
        $capMax = max($included, (int)($unit['capacity_max'] ?? $guests));
        return max(0, min($guests, $capMax) - $included);
    }

    public static function nightPrice(float $basePrice, int $guests, array $unit): float
    {
        $extra = self::extraGuestsPerNight($guests, $unit);
        return $basePrice + $extra * (float)($unit['extra_person_fee'] ?? 0);
    }

    public static function maxNightPriceAtCapacity(array $unit, ?float $baseOverride = null): float
    {
        $base = $baseOverride ?? (float)($unit['price'] ?? 0);
        $capMax = max(1, (int)($unit['capacity_max'] ?? 1));
        return self::nightPrice($base, $capMax, $unit);
    }

    public static function hasExtraGuestFee(array $unit): bool
    {
        $included = self::includedGuests($unit);
        $capMax = (int)($unit['capacity_max'] ?? $included);
        return (float)($unit['extra_person_fee'] ?? 0) > 0 && $capMax > $included;
    }

    public static function formatCardPrice(array $unit): string
    {
        $base = (float)($unit['price'] ?? 0);
        if ($base <= 0) {
            return '';
        }
        if (!self::hasExtraGuestFee($unit)) {
            return format_money($base);
        }
        $max = self::maxNightPriceAtCapacity($unit, $base);
        if ($max <= $base) {
            return format_money($base);
        }
        return format_money($base) . '–' . format_money($max, false);
    }

    public static function guestPriceNote(array $unit): string
    {
        if (!self::hasExtraGuestFee($unit)) {
            return '';
        }
        $included = self::includedGuests($unit);
        $capMax = (int)($unit['capacity_max'] ?? $included);
        $cmin = (int)($unit['capacity_min'] ?? 1);
        $fee = (float)$unit['extra_person_fee'];
        $guestPart = ($cmin > 1 && $cmin <= $included)
            ? $cmin . '–' . $included . ' ท่าน'
            : 'รวม ' . $included . ' ท่าน';
        $extraFrom = min($capMax, $included + 1);
        return $guestPart . ' · +' . format_money($fee, false) . '/ท่าน (' . $extraFrom . '–' . $capMax . ')';
    }

    public static function formatDetailLines(array $unit): array
    {
        $base = (float)($unit['price'] ?? 0);
        $lines = ['primary' => format_money($base) . ' / คืน'];
        if (self::hasExtraGuestFee($unit)) {
            $included = self::includedGuests($unit);
            $capMax = (int)($unit['capacity_max'] ?? $included);
            $fee = (float)$unit['extra_person_fee'];
            $lines['included'] = 'ราคานี้รวม ' . $included . ' ท่าน';
            $lines['extra'] = 'ท่านที่ ' . ($included + 1) . '–' . $capMax . ' + ' . format_money($fee) . '/ท่าน/คืน';
            $lines['max_example'] = 'สูงสุด ' . $capMax . ' ท่าน ≈ ' . format_money(self::maxNightPriceAtCapacity($unit)) . '/คืน (วันธรรมดา)';
        }
        return $lines;
    }

    public static function coerceUnit(array $row): array
    {
        if (!empty($row['listing_unit_id'])) {
            return [
                'price' => (float)($row['listing_unit_price'] ?? 0),
                'capacity_min' => (int)($row['listing_unit_cap_min'] ?? 0),
                'capacity_max' => (int)($row['listing_unit_cap_max'] ?? 0),
                'price_includes_guests' => $row['listing_unit_price_includes'] ?? null,
                'extra_person_fee' => (float)($row['listing_unit_extra_fee'] ?? 0),
            ];
        }
        return [
            'price' => (float)($row['price'] ?? $row['min_price'] ?? 0),
            'capacity_min' => (int)($row['capacity_min'] ?? $row['_unit_cap_min'] ?? 0),
            'capacity_max' => (int)($row['capacity_max'] ?? $row['_unit_cap_max'] ?? 0),
            'price_includes_guests' => $row['price_includes_guests'] ?? null,
            'extra_person_fee' => (float)($row['extra_person_fee'] ?? 0),
        ];
    }

    public static function listingShowsFeatured(array $row): bool
    {
        return (int)($row['is_featured'] ?? 0) === 1
            || (int)($row['listing_unit_is_featured'] ?? 0) === 1;
    }
}