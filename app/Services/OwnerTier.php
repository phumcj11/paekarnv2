<?php

namespace App\Services;

use App\Models\Setting;

/**
 * จุดกลางสำหรับเช็คสิทธิ์ฟีเจอร์ตามระดับสมาชิกเจ้าของแพ
 *
 * Tiers:
 *   none      — Free (ลงทะเบียนใหม่, ยังไม่ซื้อแพ็กเกจ)
 *   standard  — Starter / Standard
 *   vip       — Pro / VIP
 *
 * การใช้งาน:
 *   OwnerTier::can($ownerId, OwnerTier::FEATURE_BROADCAST)  → bool
 *   OwnerTier::tier($ownerId)                               → 'none'|'standard'|'vip'
 */
final class OwnerTier
{
    private const SETTING_FEATURES = 'owner_tier_features_json';
    private const SETTING_SERVICE_PERKS = 'owner_tier_service_perks_json';

    // ---- ฟีเจอร์ทั้งหมด ----
    public const FEATURE_GUEST_SEEK_LEADS  = 'guest_seek_leads';
    public const FEATURE_BROADCAST         = 'broadcast';
    public const FEATURE_AUTOMATION        = 'automation';
    public const FEATURE_ANALYTICS_DEEP    = 'analytics_deep';
    public const FEATURE_LINE_CRM          = 'line_crm';
    public const FEATURE_AI_DRAFT          = 'ai_draft';
    public const FEATURE_LISTING_BOOST     = 'listing_boost';
    public const FEATURE_AVAILABLE_BOOST   = 'available_boost';
    public const FEATURE_COUPON            = 'coupon';
    public const FEATURE_BOOKING           = 'booking';
    public const FEATURE_AVAILABILITY      = 'availability';
    public const FEATURE_CONTENT_PLAN      = 'content_plan';
    public const FEATURE_ANALYTICS         = 'analytics';
    public const FEATURE_LINE_HUB          = 'line_hub';

    /**
     * @param self::FEATURE_* $feature
     */
    public static function can(int $ownerId, string $feature): bool
    {
        return self::tierCan(self::tier($ownerId), $feature);
    }

    /**
     * @param 'none'|'standard'|'vip' $tier
     * @param self::FEATURE_*         $feature
     */
    public static function tierCan(string $tier, string $feature): bool
    {
        if (!in_array($tier, ['none', 'standard', 'vip'], true)) {
            return false;
        }

        $cfg = self::featuresConfig();

        if ($feature === self::FEATURE_AVAILABLE_BOOST) {
            if ($tier === 'none') {
                return false;
            }
            return (int) ($cfg['boost'][$tier] ?? 0) > 0;
        }

        return in_array($feature, $cfg['features'][$tier] ?? [], true);
    }

    public static function tier(int $ownerId): string
    {
        $row = OwnerMembership::ownerRow($ownerId);
        if (!$row) {
            return 'none';
        }
        if (!OwnerMembership::hasActiveFeatureBenefits($row)) {
            return 'none';
        }
        $t = OwnerMembership::featureTier($row);

        return in_array($t, ['standard', 'vip'], true) ? $t : 'none';
    }

    /** @return list<string> */
    public static function featuresForTier(string $tier): array
    {
        $all = array_keys(self::featureLabels());
        return array_values(array_filter($all, static fn ($f) => self::tierCan($tier, $f)));
    }

    /** @return array<string, string> */
    public static function featureLabels(): array
    {
        return [
            self::FEATURE_BOOKING            => 'การจอง (รายการ / สร้างจอง / รายได้)',
            self::FEATURE_AVAILABILITY       => 'ปฏิทินวันว่าง + จองจากปฏิทิน',
            self::FEATURE_LINE_HUB           => 'LINE Hub — ตั้งค่า OA / Chatbot',
            self::FEATURE_CONTENT_PLAN       => 'Content Plan + โพสต์ FB / LINE / IG',
            self::FEATURE_ANALYTICS          => 'Analytics พื้นฐาน + Funnel',
            self::FEATURE_LINE_CRM           => 'LINE CRM (รายชื่อแชท, tag, ส่งทีละคน)',
            self::FEATURE_AUTOMATION         => 'Automation — บันทึก template ข้อความอัตโนมัติ',
            self::FEATURE_AI_DRAFT           => 'AI ช่วยร่างข้อความ (LINE / Automation / Content)',
            self::FEATURE_BROADCAST          => 'LINE Broadcast',
            self::FEATURE_ANALYTICS_DEEP     => 'Analytics ลึก (Referrer + AI สรุป)',
            self::FEATURE_COUPON             => 'ตรวจ / สแกนคูปอง',
            self::FEATURE_GUEST_SEEK_LEADS   => 'รับ Lead ฟอร์ม "ขอให้ช่วยหาที่พัก"',
            self::FEATURE_LISTING_BOOST      => 'Boost ลำดับค้นหา + Featured',
            self::FEATURE_AVAILABLE_BOOST    => 'Boost หน้าแพว่าง (ranking)',
        ];
    }

    /** @return array{base_property: array<string,bool>, features: array<string,list<string>>, boost: array<string,int>} */
    public static function defaultFeaturesConfig(): array
    {
        $features = array_keys(self::featureLabels());
        $pick = static function (string $tier) use ($features): array {
            return array_values(array_filter(
                $features,
                static fn (string $f) => self::tierCanDefault($tier, $f)
            ));
        };

        return [
            'base_property' => ['none' => true, 'standard' => true, 'vip' => true],
            'features'      => [
                'none'     => [],
                'standard' => $pick('standard'),
                'vip'      => $pick('vip'),
            ],
            'boost'         => ['standard' => 20, 'vip' => 30],
        ];
    }

    /** @return array{base_property: array<string,bool>, features: array<string,list<string>>, boost: array<string,int>} */
    public static function featuresConfig(): array
    {
        $raw = Setting::get(self::SETTING_FEATURES, '');
        if ($raw === '' || $raw === null) {
            return self::defaultFeaturesConfig();
        }
        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return self::defaultFeaturesConfig();
        }

        return self::normalizeFeaturesConfig($decoded);
    }

    /** @param array<string,mixed> $input */
    public static function saveFeaturesConfig(array $input): void
    {
        $normalized = self::normalizeFeaturesConfig($input);
        Setting::set(self::SETTING_FEATURES, json_encode($normalized, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param array<string,mixed> $config
     * @return array{base_property: array<string,bool>, features: array<string,list<string>>, boost: array<string,int>}
     */
    public static function normalizeFeaturesConfig(array $config): array
    {
        $validFeatures = array_keys(self::featureLabels());
        $tiers = ['none', 'standard', 'vip'];
        $defaults = self::defaultFeaturesConfig();

        $normalized = [
            'base_property' => [],
            'features'      => ['none' => [], 'standard' => [], 'vip' => []],
            'boost'         => ['standard' => 20, 'vip' => 30],
        ];

        foreach ($tiers as $t) {
            $normalized['base_property'][$t] = !empty($config['base_property'][$t]);
        }

        foreach ($tiers as $t) {
            $list = $config['features'][$t] ?? [];
            if (!is_array($list)) {
                $list = [];
            }
            $normalized['features'][$t] = array_values(array_intersect(
                array_map('strval', $list),
                $validFeatures
            ));
        }

        foreach (['standard', 'vip'] as $t) {
            $normalized['boost'][$t] = max(0, min(100, (int) ($config['boost'][$t] ?? $defaults['boost'][$t])));
        }

        return $normalized;
    }

    /**
     * @return list<array{label: string, base_property?: bool, feature?: string}>
     */
    public static function comparisonRows(): array
    {
        $rows = [
            ['label' => 'จัดการที่พัก / ยูนิต + แสดงบนเว็บ', 'base_property' => true],
        ];

        foreach (self::featureLabels() as $feature => $label) {
            $rows[] = ['label' => $label, 'feature' => $feature];
        }

        return $rows;
    }

    /**
     * @param array{label: string, base_property?: bool, feature?: string} $row
     * @return bool|string
     */
    public static function comparisonCell(array $row, string $tier): bool|string
    {
        $cfg = self::featuresConfig();

        if (!empty($row['base_property'])) {
            return !empty($cfg['base_property'][$tier]);
        }

        $feature = $row['feature'] ?? '';
        if ($feature === self::FEATURE_AVAILABLE_BOOST) {
            if ($tier === 'none') {
                return false;
            }
            $v = (int) ($cfg['boost'][$tier] ?? 0);

            return $v > 0 ? ('+' . $v) : false;
        }

        return in_array($feature, $cfg['features'][$tier] ?? [], true);
    }

    /** @return list<array{key: string, label: string, none: bool, standard: bool, vip: bool}> */
    public static function comparisonServiceRows(): array
    {
        return self::servicePerksConfig()['perks'];
    }

    /** @param self::FEATURE_* $feature */
    private static function tierCanDefault(string $tier, string $feature): bool
    {
        return match ($feature) {
            self::FEATURE_BOOKING,
            self::FEATURE_AVAILABILITY,
            self::FEATURE_CONTENT_PLAN,
            self::FEATURE_ANALYTICS,
            self::FEATURE_LINE_HUB,
            self::FEATURE_AUTOMATION,
            self::FEATURE_LINE_CRM,
            self::FEATURE_AI_DRAFT       => $tier !== 'none',

            self::FEATURE_BROADCAST,
            self::FEATURE_ANALYTICS_DEEP,
            self::FEATURE_COUPON         => in_array($tier, ['standard', 'vip'], true),

            self::FEATURE_GUEST_SEEK_LEADS,
            self::FEATURE_LISTING_BOOST  => $tier === 'vip',

            self::FEATURE_AVAILABLE_BOOST => false,

            default => false,
        };
    }

    /** @return array{perks: list<array{key: string, label: string, none: bool, standard: bool, vip: bool}>} */
    public static function defaultServicePerksConfig(): array
    {
        return ['perks' => []];
    }

    /** @return array{perks: list<array{key: string, label: string, none: bool, standard: bool, vip: bool}>} */
    public static function servicePerksConfig(): array
    {
        $raw = Setting::get(self::SETTING_SERVICE_PERKS, '');
        if ($raw === '' || $raw === null) {
            return self::defaultServicePerksConfig();
        }
        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return self::defaultServicePerksConfig();
        }

        return self::normalizeServicePerksConfig($decoded);
    }

    /** @param array<string,mixed> $input */
    public static function saveServicePerksConfig(array $input): void
    {
        Setting::set(self::SETTING_SERVICE_PERKS, json_encode(
            self::normalizeServicePerksConfig($input),
            JSON_UNESCAPED_UNICODE
        ));
    }

    /**
     * @param array<string,mixed> $config
     * @return array{perks: list<array{key: string, label: string, none: bool, standard: bool, vip: bool}>}
     */
    public static function normalizeServicePerksConfig(array $config): array
    {
        $perks = [];
        $seen = [];
        foreach ($config['perks'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $key = trim((string) ($row['key'] ?? ''));
            if ($key === '') {
                $key = self::slugPerkKey($label);
            }
            $base = $key;
            $n = 2;
            while (isset($seen[$key])) {
                $key = $base . '_' . $n;
                $n++;
            }
            $seen[$key] = true;
            $perks[] = [
                'key'      => $key,
                'label'    => $label,
                'none'     => !empty($row['none']),
                'standard' => !empty($row['standard']),
                'vip'      => !empty($row['vip']),
            ];
        }

        return ['perks' => $perks];
    }

    /** @return list<array{key: string, label: string}> */
    public static function servicePerksForTier(string $tier): array
    {
        if (!in_array($tier, ['none', 'standard', 'vip'], true)) {
            return [];
        }
        $out = [];
        foreach (self::servicePerksConfig()['perks'] as $p) {
            if (!empty($p[$tier])) {
                $out[] = ['key' => $p['key'], 'label' => $p['label']];
            }
        }

        return $out;
    }

    public static function servicePerkTierEnabled(array $perk, string $tier): bool
    {
        return !empty($perk[$tier]);
    }

    private static function slugPerkKey(string $label): string
    {
        $key = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $label) ?? '');
        $key = trim($key, '_');

        return $key !== '' ? substr($key, 0, 60) : 'perk_' . substr(md5($label), 0, 8);
    }
}
