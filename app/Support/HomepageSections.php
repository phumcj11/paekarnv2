<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Registry สำหรับ section หน้าแรก — ลำดับ, ชื่อ, โซนแพ, กติกาเรียงการ์ด
 */
class HomepageSections
{
    /** @return array<string,array{title:string,eyebrow:string,enabled:bool,limit:int,more_path:string,type_key:string}> */
    public static function defaultFeaturedLabels(): array
    {
        return [
            'raft' => [
                'title'      => 'Recommended by แพกาญ.com',
                'eyebrow'    => 'Hot Deal',
                'enabled'    => true,
                'limit'      => 8,
                'more_path'  => '/rafts',
                'type_key'   => 'raft',
            ],
            'resort' => [
                'title'      => 'รีสอร์ทแนะนำ',
                'eyebrow'    => 'รีสอร์ท',
                'enabled'    => true,
                'limit'      => 6,
                'more_path'  => '/resorts',
                'type_key'   => 'resort',
            ],
            'hotel' => [
                'title'      => 'โรงแรมแนะนำ',
                'eyebrow'    => 'โรงแรม',
                'enabled'    => true,
                'limit'      => 6,
                'more_path'  => '/hotels',
                'type_key'   => 'hotel',
            ],
            'stay' => [
                'title'      => 'โฮมสเตย์ & บ้านพัก',
                'eyebrow'    => 'ที่พัก',
                'enabled'    => true,
                'limit'      => 6,
                'more_path'  => '/stays',
                'type_key'   => 'stay',
            ],
        ];
    }

    /** @return list<array{id:string,title:string,zones:list<string>,enabled:bool,sort_order:int}> */
    public static function defaultZoneSections(): array
    {
        return [
            ['id' => 'home-zone-srinakarin', 'title' => 'แพเขื่อนศรีนครินทร์', 'zones' => ['เขื่อนศรีนครินทร์'], 'enabled' => true, 'sort_order' => 1],
            ['id' => 'home-zone-khao-laem', 'title' => 'แพเขื่อนเขาแหลม', 'zones' => ['เขื่อนเขาแหลม', 'เขื่อนวชิราลงกรณ์'], 'enabled' => true, 'sort_order' => 2],
            ['id' => 'home-zone-sangkhla', 'title' => 'แพสังขละบุรี', 'zones' => ['สังขละบุรี'], 'enabled' => true, 'sort_order' => 3],
            ['id' => 'home-zone-muang', 'title' => 'แพหน้าเมือง', 'zones' => ['ริมแม่น้ำแคว', 'แม่น้ำแคว', 'แควใหญ่'], 'enabled' => true, 'sort_order' => 4],
            ['id' => 'home-zone-saiyok-noi', 'title' => 'แพไทรโยคน้อย', 'zones' => ['ริมแม่น้ำแควน้อย', 'น้ำตกไทรโยคน้อย', 'แควน้อย'], 'enabled' => true, 'sort_order' => 5],
            ['id' => 'home-zone-saiyok-yai', 'title' => 'แพไทรโยคใหญ่', 'zones' => ['อุทยานไทรโยค', 'อุทยานแห่งชาติเอราวัณ'], 'enabled' => true, 'sort_order' => 6],
        ];
    }

    public static function zoneSectionId(string $defId): string
    {
        $slug = preg_replace('#^home-zone-#', '', $defId);

        return 'zone-raft-' . ($slug !== '' ? $slug : $defId);
    }

    /** @return list<string> */
    public static function defaultOrder(): array
    {
        $order = [
            'featured-raft',
            'featured-resort',
            'featured-hotel',
            'featured-stay',
            'trust',
            'coupon-mobile',
            'zones-popular',
            'newest-raft',
        ];
        foreach (self::defaultZoneSections() as $z) {
            $order[] = self::zoneSectionId($z['id']);
        }
        $order = array_merge($order, [
            'activities',
            'reviews-youtube',
            'reviews-guest',
            'blog',
            'cta-bottom',
        ]);

        return $order;
    }

    /** @return array<string,list<string>> */
    public static function bannerSlotsBeforeSection(): array
    {
        return [
            'trust'            => ['home_after_stats', 'home_before_featured'],
            'coupon-mobile'    => ['home_before_coupon'],
            'zones-popular'    => ['home_before_zones'],
            'newest-raft'      => ['home_before_newest'],
            'reviews-youtube'  => ['home_before_reviews'],
            'blog'             => ['home_before_blog'],
            'cta-bottom'       => ['home_before_cta'],
        ];
    }

    /** @return array<string,array{title:string,eyebrow:string,enabled:bool,limit:int,more_path:string,type_key:string}> */
    public static function featuredLabels(): array
    {
        $defaults = self::defaultFeaturedLabels();
        $raw = trim((string) Setting::get('home_featured_labels', ''));
        if ($raw === '') {
            return $defaults;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $defaults;
        }
        foreach ($defaults as $key => $def) {
            if (!isset($decoded[$key]) || !is_array($decoded[$key])) {
                continue;
            }
            $row = $decoded[$key];
            if (isset($row['title']) && trim((string) $row['title']) !== '') {
                $defaults[$key]['title'] = trim((string) $row['title']);
            }
            if (isset($row['eyebrow']) && trim((string) $row['eyebrow']) !== '') {
                $defaults[$key]['eyebrow'] = trim((string) $row['eyebrow']);
            }
            if (array_key_exists('enabled', $row)) {
                $defaults[$key]['enabled'] = (bool) $row['enabled'];
            }
            if (isset($row['limit'])) {
                $defaults[$key]['limit'] = max(1, min(24, (int) $row['limit']));
            }
        }

        return $defaults;
    }

    /** @return list<array{id:string,title:string,zones:list<string>,enabled:bool,sort_order:int}> */
    public static function zoneSections(): array
    {
        $defaults = self::defaultZoneSections();
        $raw = trim((string) Setting::get('home_zone_sections', ''));
        if ($raw === '') {
            return $defaults;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $defaults;
        }
        $byId = [];
        foreach ($defaults as $d) {
            $byId[$d['id']] = $d;
        }
        $out = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = trim((string) ($row['id'] ?? ''));
            if ($id === '' || !isset($byId[$id])) {
                continue;
            }
            $base = $byId[$id];
            $out[] = [
                'id'         => $id,
                'title'      => trim((string) ($row['title'] ?? '')) !== '' ? trim((string) $row['title']) : $base['title'],
                'zones'      => $base['zones'],
                'enabled'    => array_key_exists('enabled', $row) ? (bool) $row['enabled'] : $base['enabled'],
                'sort_order' => isset($row['sort_order']) ? (int) $row['sort_order'] : $base['sort_order'],
            ];
            unset($byId[$id]);
        }
        foreach ($byId as $remaining) {
            $out[] = $remaining;
        }
        usort($out, static fn (array $a, array $b): int => ($a['sort_order'] <=> $b['sort_order']) ?: strcmp($a['id'], $b['id']));

        return $out;
    }

    /** @return list<string> */
    public static function sectionsOrder(): array
    {
        $default = self::defaultOrder();
        $raw = trim((string) Setting::get('home_sections_order', ''));
        if ($raw === '') {
            return self::reorderZoneSectionsInOrder($default);
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return self::reorderZoneSectionsInOrder($default);
        }
        $valid = array_flip($default);
        $seen = [];
        $order = [];
        foreach ($decoded as $id) {
            $id = trim((string) $id);
            if ($id === '' || !isset($valid[$id]) || isset($seen[$id])) {
                continue;
            }
            $order[] = $id;
            $seen[$id] = true;
        }
        foreach ($default as $id) {
            if (!isset($seen[$id])) {
                $order[] = $id;
            }
        }

        return self::reorderZoneSectionsInOrder($order);
    }

    /** @param list<string> $order */
    private static function reorderZoneSectionsInOrder(array $order): array
    {
        $zoneSortMap = [];
        foreach (self::zoneSections() as $z) {
            $zoneSortMap[self::zoneSectionId($z['id'])] = $z['sort_order'];
        }
        $firstIdx = null;
        $lastIdx = null;
        foreach ($order as $i => $id) {
            if (strpos($id, 'zone-raft-') === 0 && isset($zoneSortMap[$id])) {
                $firstIdx = $firstIdx ?? $i;
                $lastIdx = $i;
            }
        }
        if ($firstIdx === null || $lastIdx === null) {
            return $order;
        }
        $zoneIds = [];
        for ($i = $firstIdx; $i <= $lastIdx; $i++) {
            $id = $order[$i];
            if (strpos($id, 'zone-raft-') === 0 && isset($zoneSortMap[$id])) {
                $zoneIds[] = $id;
            }
        }
        usort($zoneIds, static function (string $a, string $b) use ($zoneSortMap): int {
            return ($zoneSortMap[$a] <=> $zoneSortMap[$b]) ?: strcmp($a, $b);
        });

        return array_merge(
            array_slice($order, 0, $firstIdx),
            $zoneIds,
            array_slice($order, $lastIdx + 1)
        );
    }

    public static function sortBoostCoupon(): bool
    {
        return (string) Setting::get('home_sort_boost_coupon', '0') === '1';
    }

    /**
     * @param array<string,mixed> $sectionPayload keyed by section id
     * @return list<array{id:string,type:string,banners_before:list<string>,payload:array<string,mixed>}>
     */
    public static function buildRenderPlan(array $sectionPayload): array
    {
        $bannerMap = self::bannerSlotsBeforeSection();
        $plan = [];
        foreach (self::sectionsOrder() as $id) {
            if (!isset($sectionPayload[$id])) {
                continue;
            }
            $entry = $sectionPayload[$id];
            if (empty($entry['visible'])) {
                continue;
            }
            $plan[] = [
                'id'              => $id,
                'type'            => (string) ($entry['type'] ?? 'unknown'),
                'banners_before'  => $bannerMap[$id] ?? [],
                'payload'         => $entry['payload'] ?? [],
            ];
        }

        return $plan;
    }

    /** @return list<array{id:string,label:string}> */
    public static function sectionLabelsForAdmin(): array
    {
        $labels = self::sectionLabelMap();

        return array_map(
            static fn (string $id, string $label): array => ['id' => $id, 'label' => $label],
            array_keys($labels),
            array_values($labels)
        );
    }

    /** @return array<string,string> */
    public static function sectionLabelMap(): array
    {
        $labels = [
            'featured-raft'     => 'แนะนำ — แพพัก',
            'featured-resort'   => 'แนะนำ — รีสอร์ท',
            'featured-hotel'    => 'แนะนำ — โรงแรม',
            'featured-stay'     => 'แนะนำ — โฮมสเตย์ & บ้านพัก',
            'trust'             => 'ทำไมต้องเลือกแพกาญ (เดสก์ท็อป)',
            'coupon-mobile'     => 'โปรคูปอง (มือถือ)',
            'zones-popular'     => 'จุดหมายยอดนิยม',
            'newest-raft'       => 'แพใหม่ล่าสุด',
            'activities'        => 'กิจกรรม & บริการ',
            'reviews-youtube'   => 'คลิปรีวิวแนวตั้ง',
            'reviews-guest'     => 'รีวิวจากผู้เข้าพัก',
            'blog'              => 'บทความ & ทริปแนะนำ',
            'cta-bottom'        => 'CTA ท้ายหน้า',
        ];
        foreach (self::zoneSections() as $z) {
            $labels[self::zoneSectionId($z['id'])] = 'แพตามโซน — ' . $z['title'];
        }

        return $labels;
    }
}
