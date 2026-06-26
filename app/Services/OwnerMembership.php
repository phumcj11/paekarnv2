<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * สถานะสมาชิกเจ้าของแพ — แยก 2 มิติ (หลัง migration):
 *   service_tier  → สิทธิ์บริการ manual
 *   feature_tier  → ฟีเจอร์ในระบบ (OwnerTier::can)
 * ก่อน migration ใช้ membership_tier เดิม
 */
class OwnerMembership
{
    public static function splitTiersAvailable(): bool
    {
        static $ok = null;
        if ($ok === null) {
            $ok = Database::tableHasColumn('owners', 'service_tier')
                && Database::tableHasColumn('owners', 'feature_tier');
        }

        return $ok;
    }

    /** @return array<string,mixed>|null */
    public static function ownerRow(int $ownerId): ?array
    {
        $cols = 'id, user_id, partner_status, membership_tier, membership_expires_at, membership_grace_until';
        if (self::splitTiersAvailable()) {
            $cols .= ', service_tier, feature_tier, service_expires_at, feature_expires_at, service_grace_until, feature_grace_until';
        }

        return Database::fetch(
            "SELECT {$cols} FROM owners WHERE id = :id",
            ['id' => $ownerId]
        );
    }

    /** @return 'none'|'standard'|'vip' */
    public static function serviceTier(?array $owner): string
    {
        if (!$owner) {
            return 'none';
        }
        if (self::splitTiersAvailable()) {
            $t = (string) ($owner['service_tier'] ?? 'none');

            return self::normalizeTier($t);
        }

        return self::normalizeTier((string) ($owner['membership_tier'] ?? 'none'));
    }

    /** @return 'none'|'standard'|'vip' */
    public static function featureTier(?array $owner): string
    {
        if (!$owner) {
            return 'none';
        }
        if (self::splitTiersAvailable()) {
            $t = (string) ($owner['feature_tier'] ?? 'none');

            return self::normalizeTier($t);
        }

        return self::normalizeTier((string) ($owner['membership_tier'] ?? 'none'));
    }

    /** สิทธิ์บริการยังใช้ได้ (รวม grace) */
    public static function hasActiveServiceBenefits(?array $owner): bool
    {
        if (!$owner) {
            return false;
        }
        if (self::splitTiersAvailable()) {
            return self::dimensionActive(
                self::serviceTier($owner),
                $owner['service_expires_at'] ?? null,
                $owner['service_grace_until'] ?? null
            );
        }

        return self::hasActiveBenefitsLegacy($owner);
    }

    /** ฟีเจอร์ในระบบยังใช้ได้ (รวม grace) */
    public static function hasActiveFeatureBenefits(?array $owner): bool
    {
        if (!$owner) {
            return false;
        }
        if (self::splitTiersAvailable()) {
            return self::dimensionActive(
                self::featureTier($owner),
                $owner['feature_expires_at'] ?? null,
                $owner['feature_grace_until'] ?? null
            );
        }

        return self::hasActiveBenefitsLegacy($owner);
    }

    /** มีสมาชิกอย่างใดอย่างหนึ่งที่ยัง active */
    public static function hasActiveBenefits(?array $owner): bool
    {
        if (self::splitTiersAvailable()) {
            return self::hasActiveServiceBenefits($owner) || self::hasActiveFeatureBenefits($owner);
        }

        return self::hasActiveBenefitsLegacy($owner);
    }

    public static function isVipActive(int $ownerId): bool
    {
        $o = self::ownerRow($ownerId);

        return $o
            && self::featureTier($o) === 'vip'
            && self::hasActiveFeatureBenefits($o);
    }

    /** VIP feature + partner active → รับ lead จากฟอร์มหาที่พัก */
    public static function receivesGuestSeekLeads(int $ownerId): bool
    {
        $o = self::ownerRow($ownerId);
        if (!$o || ($o['partner_status'] ?? '') !== 'active') {
            return false;
        }

        return self::featureTier($o) === 'vip' && self::hasActiveFeatureBenefits($o);
    }

    /**
     * ซิงก์ membership_* เดิมให้ SQL/boost เก่ายังใช้ feature tier เป็นหลัก
     */
    public static function syncLegacyMembershipColumns(int $ownerId): void
    {
        if (!self::splitTiersAvailable() || $ownerId <= 0) {
            return;
        }

        $o = self::ownerRow($ownerId);
        if (!$o) {
            return;
        }

        if (self::hasActiveFeatureBenefits($o)) {
            $legacyTier = self::featureTier($o);
            $legacyExp = $o['feature_expires_at'] ?? null;
            $legacyGrace = $o['feature_grace_until'] ?? null;
        } else {
            $legacyTier = 'none';
            $legacyExp = null;
            $legacyGrace = null;
        }

        Database::update(
            'owners',
            [
                'membership_tier'        => $legacyTier,
                'membership_expires_at'  => $legacyTier === 'none' ? null : $legacyExp,
                'membership_grace_until' => $legacyTier === 'none' ? null : $legacyGrace,
            ],
            'id = :id',
            ['id' => $ownerId]
        );
    }

    /**
     * @param 'service'|'feature' $dimension
     * @param array<string,mixed> $fields tier, expires_at, grace_until (null clears)
     */
    public static function applyDimensionUpdate(int $ownerId, string $dimension, array $fields): void
    {
        if (!self::splitTiersAvailable() || $ownerId <= 0) {
            return;
        }

        $prefix = $dimension === 'service' ? 'service' : 'feature';
        $tier = self::normalizeTier((string) ($fields['tier'] ?? 'none'));
        $update = [
            "{$prefix}_tier" => $tier,
        ];
        if (array_key_exists('expires_at', $fields)) {
            $update["{$prefix}_expires_at"] = $tier === 'none' ? null : $fields['expires_at'];
        }
        if (array_key_exists('grace_until', $fields)) {
            $update["{$prefix}_grace_until"] = $tier === 'none' ? null : $fields['grace_until'];
        }

        Database::update('owners', $update, 'id = :id', ['id' => $ownerId]);
        self::syncLegacyMembershipColumns($ownerId);
    }

    /** @return 'none'|'standard'|'vip' */
    private static function normalizeTier(string $tier): string
    {
        return in_array($tier, ['standard', 'vip'], true) ? $tier : 'none';
    }

    private static function dimensionActive(string $tier, mixed $expires, mixed $grace): bool
    {
        if ($tier === 'none') {
            return false;
        }
        if ($expires === null || $expires === '') {
            return true;
        }
        if (strtotime((string) $expires) > time()) {
            return true;
        }
        if ($grace !== null && $grace !== '' && strtotime((string) $grace) > time()) {
            return true;
        }

        return false;
    }

    private static function hasActiveBenefitsLegacy(?array $owner): bool
    {
        if (!$owner || ($owner['membership_tier'] ?? 'none') === 'none') {
            return false;
        }

        return self::dimensionActive(
            self::normalizeTier((string) ($owner['membership_tier'] ?? 'none')),
            $owner['membership_expires_at'] ?? null,
            $owner['membership_grace_until'] ?? null
        );
    }
}
