<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Banner;

/**
 * เลือก Banner ต่อช่อง (priority + กำหนดการ) และแทรก B06 ใน grid
 */
final class BannerService
{
    /** @return array<string, array<int, array<string, mixed>>> */
    public static function groupedForHome(): array
    {
        $raw = Banner::groupedForHome();
        $out = [];

        foreach (Banner::homeSlotKeys() as $slot) {
            $rows = $raw[$slot] ?? [];
            if ($rows === [] && ($legacy = Banner::legacyFallbackSlots()[$slot] ?? []) !== []) {
                foreach ($legacy as $legacySlot) {
                    $rows = array_merge($rows, $raw[$legacySlot] ?? []);
                }
            }
            $out[$slot] = self::sortRows($rows);
        }

        return $out;
    }

    /** Banner เดียวที่ควรแสดงในช่อง (priority สูงสุด) */
    public static function forSlot(string $slot): ?array
    {
        $rows = self::groupedForHome()[$slot] ?? [];

        return self::pickBest($rows);
    }

    /** @param array<int, array<string, mixed>> $rows */
    public static function pickBest(array $rows): ?array
    {
        $rows = self::sortRows($rows);
        if ($rows === []) {
            return null;
        }

        return $rows[0];
    }

    /**
     * แทรกการ์ดผู้สนับสนุน (B06) ใน grid
     *
     * @param array<int, array<string, mixed>> $properties
     * @return list<array<string, mixed>>
     */
    public static function injectNativeCard(array $properties, ?array $banner, int $position = 3): array
    {
        if ($banner === null || $banner === []) {
            return $properties;
        }

        $position = max(0, min(count($properties), $position));
        $marker = ['type' => 'sponsored_banner', 'banner' => $banner];
        array_splice($properties, $position, 0, [$marker]);

        return $properties;
    }

    /** @param array<int, array<string, mixed>> $rows */
    private static function sortRows(array $rows): array
    {
        usort($rows, static function (array $a, array $b): int {
            $pa = (int)($a['priority'] ?? 0);
            $pb = (int)($b['priority'] ?? 0);
            if ($pa !== $pb) {
                return $pb <=> $pa;
            }
            $sa = (int)($a['sort_order'] ?? 0);
            $sb = (int)($b['sort_order'] ?? 0);
            if ($sa !== $sb) {
                return $sa <=> $sb;
            }

            return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
        });

        return array_values($rows);
    }
}
