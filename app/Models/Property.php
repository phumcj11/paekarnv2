<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Property extends Model
{
    protected static string $table = 'properties';

    public static function publicUnitCondition(string $alias = 'u'): string
    {
        $prefix = $alias !== '' ? $alias . '.' : '';

        return "{$prefix}is_active = 1 AND {$prefix}moderation_status = 'published'";
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch(
            "SELECT * FROM properties WHERE slug = :slug AND status = 'published' LIMIT 1",
            ['slug' => $slug]
        );
    }

    public static function findByIdPublic(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM properties WHERE id = :id AND status = 'published' LIMIT 1",
            ['id' => $id]
        );
    }

    /** @var list<string> */
    public const PROPERTY_TYPES = ['raft', 'resort', 'homestay', 'house', 'pool_villa', 'hotel', 'camping'];

    /** ประเภทที่พักที่ระเบิดการ์ดรายการเป็น 1 การ์ดต่อยูนิตที่เปิดขาย */
    public static function listingExpandTypes(): array
    {
        return ['raft', 'pool_villa'];
    }

    /** @param string|list<string> $types */
    public static function normalizeTypes(string|array $types): array
    {
        $list = is_array($types) ? $types : [$types];

        return array_values(array_intersect($list, self::PROPERTY_TYPES));
    }

    /** @return array{sql:string,params:array<string,mixed>} */
    private static function typeFilterSql(array $types, string $branch): array
    {
        $types = self::normalizeTypes($types);
        if ($types === []) {
            return ['sql' => '', 'params' => []];
        }
        if (count($types) === 1) {
            $key = match ($branch) {
                '1' => 'type1',
                '2' => 'type2',
                default => 'type_' . $branch,
            };

            return ['sql' => 'p.type = :' . $key, 'params' => [$key => $types[0]]];
        }
        $ph = [];
        $params = [];
        foreach ($types as $i => $t) {
            $key = 'types' . $branch . '_' . $i;
            $ph[] = ':' . $key;
            $params[$key] = $t;
        }

        return ['sql' => 'p.type IN (' . implode(',', $ph) . ')', 'params' => $params];
    }

    /** ตั้งค่า _unit_* ให้การ์ดจากข้อมูลยูนิตเดียว (ให้ listingUnitSummaryLine แสดงถูกต้อง) */
    private static function applyListingUnitStatsForCard(array &$r): void
    {
        $br = (int)($r['listing_unit_bedrooms'] ?? 0);
        $ba = (int)($r['listing_unit_bathrooms'] ?? 0);
        $cmin = (int)($r['listing_unit_cap_min'] ?? 0);
        $cmax = (int)($r['listing_unit_cap_max'] ?? 0);
        $r['_unit_br_min'] = $br;
        $r['_unit_br_max'] = $br;
        $r['_unit_ba_min'] = $ba;
        $r['_unit_ba_max'] = $ba;
        $r['_unit_br'] = $br;
        $r['_unit_ba'] = $ba;
        $r['_unit_cap_min'] = $cmin;
        $r['_unit_cap_max'] = $cmax;
    }

    /** หลังดึงแถวจาก SQL (UNION หรือ JOIN ยูนิต) */
    public static function normalizeListingCardRows(array $rows): array
    {
        foreach ($rows as &$r) {
            if (!empty($r['listing_unit_id'])) {
                self::applyListingUnitStatsForCard($r);
            }
        }
        unset($r);

        return $rows;
    }

    /**
     * @param array<string,mixed> $unit แถวจาก property_units
     * @return array<string,mixed>
     */
    public static function mergeUnitIntoPropertyRowForListing(array $property, array $unit): array
    {
        $row = $property;
        $row['listing_unit_id'] = (int)$unit['id'];
        $row['listing_unit_name'] = $unit['name'];
        $row['listing_unit_cover'] = $unit['cover_image'];
        $row['listing_unit_price'] = (float)$unit['price'];
        $row['listing_unit_bedrooms'] = (int)$unit['bedrooms'];
        $row['listing_unit_bathrooms'] = (int)$unit['bathrooms'];
        $row['listing_unit_cap_min'] = (int)$unit['capacity_min'];
        $row['listing_unit_cap_max'] = (int)$unit['capacity_max'];
        $row['listing_unit_price_includes'] = $unit['price_includes_guests'] ?? null;
        $row['listing_unit_extra_fee'] = (float)($unit['extra_person_fee'] ?? 0);
        $row['listing_unit_is_featured'] = (int)($unit['is_featured'] ?? 0);
        $row['listing_unit_homepage_priority'] = (int)($unit['homepage_priority'] ?? 0);
        self::applyListingUnitStatsForCard($row);

        return $row;
    }

    /**
     * แปลงแถวที่พักเป็นการ์ดหลายใบตามยูนิต (ใช้หน้าแรก featured/newest ฯลฯ)
     *
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    public static function expandListingRowsForDisplay(array $rows): array
    {
        $expandSet = array_flip(self::listingExpandTypes());
        $out = [];
        foreach ($rows as $p) {
            if (!empty($p['listing_unit_id'])) {
                $out[] = $p;
                continue;
            }
            $t = (string)($p['type'] ?? '');
            if (!isset($expandSet[$t])) {
                $out[] = $p;
                continue;
            }
            $units = Database::fetchAll(
                'SELECT * FROM property_units WHERE property_id = :pid AND ' . self::publicUnitCondition('') . ' ORDER BY ' . self::unitOrderSql(''),
                ['pid' => (int)$p['id']]
            );
            if ($units === []) {
                $out[] = $p;
                continue;
            }
            foreach ($units as $u) {
                $out[] = self::mergeUnitIntoPropertyRowForListing($p, $u);
            }
        }

        return $out;
    }

    /**
     * รายการพร้อม filter (frontend)
     * แพ/pool_villa ที่มียูนิตเปิดขาย: 1 แถวต่อยูนิต (ชื่อ/รูป/ราคาตามยูนิต)
     *
     * @return array{rows:array,total:int}
     */
    public static function search(array $f, int $page = 1, int $perPage = 12): array
    {
        $w1 = ["p.status = 'published'"];
        $w2 = ["p.status = 'published'"];
        $params = [];

        if (!empty($f['q'])) {
            $like = '%' . $f['q'] . '%';
            // Native MySQL prepares (emulate_prepares=false) require unique named placeholders per occurrence.
            $w1[] = '(p.name LIKE :q1_n OR p.description LIKE :q1_d OR p.zone LIKE :q1_z OR p.district LIKE :q1_di OR p.owner_intake LIKE :q1_oi)';
            $w2[] = '(p.name LIKE :q2_n OR p.description LIKE :q2_d OR p.zone LIKE :q2_z OR p.district LIKE :q2_di OR p.owner_intake LIKE :q2_oi)';
            $params['q1_n'] = $like; $params['q1_d'] = $like; $params['q1_z'] = $like; $params['q1_di'] = $like; $params['q1_oi'] = $like;
            $params['q2_n'] = $like; $params['q2_d'] = $like; $params['q2_z'] = $like; $params['q2_di'] = $like; $params['q2_oi'] = $like;
        }

        // must_have: each keyword must match at least one text field (AND between keywords, OR across fields)
        if (!empty($f['must_have']) && is_array($f['must_have'])) {
            $mhCount = 0;
            foreach (array_slice($f['must_have'], 0, 4) as $mhKw) {
                $mhKw = trim((string)$mhKw);
                if ($mhKw === '') continue;
                $mhLike = '%' . $mhKw . '%';
                $i = $mhCount;
                $w1[] = "(p.name LIKE :mh{$i}_1a OR p.description LIKE :mh{$i}_1b OR p.owner_intake LIKE :mh{$i}_1c)";
                $w2[] = "(p.name LIKE :mh{$i}_2a OR p.description LIKE :mh{$i}_2b OR p.owner_intake LIKE :mh{$i}_2c)";
                $params["mh{$i}_1a"] = $mhLike; $params["mh{$i}_1b"] = $mhLike; $params["mh{$i}_1c"] = $mhLike;
                $params["mh{$i}_2a"] = $mhLike; $params["mh{$i}_2b"] = $mhLike; $params["mh{$i}_2c"] = $mhLike;
                $mhCount++;
            }
        }
        if (!empty($f['zone'])) {
            $w1[] = 'p.zone = :zone1';
            $w2[] = 'p.zone = :zone2';
            $params['zone1'] = $f['zone'];
            $params['zone2'] = $f['zone'];
        }
        if (!empty($f['type'])) {
            $typeFilter = self::typeFilterSql([(string)$f['type']], '1');
            if ($typeFilter['sql'] !== '') {
                $w1[] = $typeFilter['sql'];
                $params = array_merge($params, $typeFilter['params']);
            }
            $typeFilter2 = self::typeFilterSql([(string)$f['type']], '2');
            if ($typeFilter2['sql'] !== '') {
                $w2[] = $typeFilter2['sql'];
                $params = array_merge($params, $typeFilter2['params']);
            }
        } elseif (!empty($f['types']) && is_array($f['types'])) {
            $typeFilter = self::typeFilterSql($f['types'], '1');
            if ($typeFilter['sql'] !== '') {
                $w1[] = $typeFilter['sql'];
                $params = array_merge($params, $typeFilter['params']);
            }
            $typeFilter2 = self::typeFilterSql($f['types'], '2');
            if ($typeFilter2['sql'] !== '') {
                $w2[] = $typeFilter2['sql'];
                $params = array_merge($params, $typeFilter2['params']);
            }
        }
        $activeTypes = !empty($f['type'])
            ? [(string)$f['type']]
            : (!empty($f['types']) && is_array($f['types']) ? self::normalizeTypes($f['types']) : []);
        if (!empty($f['raft_variant']) && in_array($f['raft_variant'], ['shore', 'towed'], true)) {
            if ($activeTypes === [] || in_array('raft', $activeTypes, true)) {
                $w1[] = "p.type = 'raft' AND p.raft_variant = :rv1";
                $w2[] = "p.type = 'raft' AND p.raft_variant = :rv2";
                $params['rv1'] = $f['raft_variant'];
                $params['rv2'] = $f['raft_variant'];
            }
        }
        if (!empty($f['pet'])) {
            $w1[] = "p.pet_policy IN ('allowed','on_request')";
            $w2[] = "p.pet_policy IN ('allowed','on_request')";
        }
        if (!empty($f['coupon'])) {
            $w1[] = 'p.coupon_enabled = 1';
            $w2[] = 'p.coupon_enabled = 1';
        }
        if (!empty($f['amenities']) && is_array($f['amenities'])) {
            $ids = implode(',', array_map('intval', $f['amenities']));
            if ($ids !== '') {
                $amTail = 'p.id IN (SELECT property_id FROM property_amenities WHERE amenity_id IN (' . $ids . ')
                    GROUP BY property_id HAVING COUNT(DISTINCT amenity_id) = ' . count($f['amenities']) . ')';
                $w1[] = $amTail;
                $w2[] = $amTail;
            }
        }

        /** กรองตามห้องนอน/ห้องน้ำขั้นต่ำของยูนิต — ใช้กับแพ/พูลวิลล่า (branch ที่ JOIN property_units) */
        $roomBranch2 = '';
        $brMin = isset($f['bedrooms_min']) ? (int)$f['bedrooms_min'] : 0;
        $baMin = isset($f['bathrooms_min']) ? (int)$f['bathrooms_min'] : 0;
        if ($brMin > 0) {
            $roomBranch2 .= ' AND u.bedrooms >= :brmin_u';
            $params['brmin_u'] = $brMin;
        }
        if ($baMin > 0) {
            $roomBranch2 .= ' AND u.bathrooms >= :bamin_u';
            $params['bamin_u'] = $baMin;
        }
        if ($roomBranch2 !== '') {
            $w1[] = "p.type NOT IN ('raft','pool_villa')";
        }

        $guestSqlBranch1 = '';
        $guestSqlBranch2 = '';
        if (!empty($f['guests'])) {
            $guestSqlBranch1 = ' AND EXISTS (SELECT 1 FROM property_units ux WHERE ux.property_id=p.id AND ' . self::publicUnitCondition('ux') . ' AND ux.capacity_min <= :g_b1_min AND ux.capacity_max >= :g_b1_max)';
            $guestSqlBranch2 = ' AND u.capacity_min <= :g_b2_min AND u.capacity_max >= :g_b2_max';
            $gVal = (int)$f['guests'];
            $params['g_b1_min'] = $gVal;
            $params['g_b1_max'] = $gVal;
            $params['g_b2_min'] = $gVal;
            $params['g_b2_max'] = $gVal;
        }

        $budgetBranch1 = '';
        $budgetBranch2 = '';
        if (!empty($f['budget_max'])) {
            $budgetBranch1 .= ' AND p.min_price <= :bmax_b1';
            $budgetBranch2 .= ' AND u.price <= :bmax_b2';
            $bv = (int)$f['budget_max'];
            $params['bmax_b1'] = $bv;
            $params['bmax_b2'] = $bv;
        }
        if (!empty($f['budget_min'])) {
            $budgetBranch1 .= ' AND p.min_price >= :bmin_b1';
            $budgetBranch2 .= ' AND u.price >= :bmin_b2';
            $bv = (int)$f['budget_min'];
            $params['bmin_b1'] = $bv;
            $params['bmin_b2'] = $bv;
        }

        $sharedSql1 = implode(' AND ', $w1);
        $sharedSql2 = implode(' AND ', $w2);

        $branch1Tail = " AND (
            p.type NOT IN ('raft','pool_villa')
            OR NOT EXISTS (
                SELECT 1 FROM property_units u0 WHERE u0.property_id = p.id AND " . self::publicUnitCondition('u0') . "
            )
        ){$guestSqlBranch1}{$budgetBranch1}";

        $branch2Tail = " AND p.type IN ('raft','pool_villa'){$guestSqlBranch2}{$budgetBranch2}{$roomBranch2}";

        $sortKey = $f['sort'] ?? 'recommended';
        $tieBreaker = 'COALESCE(listing_sort_rank, 999999) ASC, listing_unit_id ASC';

        $orderPrimary = match ($sortKey) {
            'price_asc' => "listing_sort_price ASC, is_featured DESC, priority DESC, rating_avg DESC, id DESC, {$tieBreaker}",
            'price_desc' => "listing_sort_price DESC, is_featured DESC, priority DESC, rating_avg DESC, id DESC, {$tieBreaker}",
            'rating' => "rating_avg DESC, rating_count DESC, is_featured DESC, priority DESC, id DESC, {$tieBreaker}",
            'newest' => "created_at DESC, is_featured DESC, priority DESC, rating_avg DESC, id DESC, {$tieBreaker}",
            default => "is_featured DESC, priority DESC, rating_avg DESC, id DESC, {$tieBreaker}",
        };

        $listingExtrasBranch1 = <<<'SQL'
, CAST(NULL AS UNSIGNED) AS listing_unit_id
, CAST(NULL AS CHAR(160)) AS listing_unit_name
, CAST(NULL AS CHAR(255)) AS listing_unit_cover
, CAST(NULL AS DECIMAL(10,2)) AS listing_unit_price
, CAST(NULL AS UNSIGNED) AS listing_unit_bedrooms
, CAST(NULL AS UNSIGNED) AS listing_unit_bathrooms
, CAST(NULL AS UNSIGNED) AS listing_unit_cap_min
, CAST(NULL AS UNSIGNED) AS listing_unit_cap_max
, p.min_price AS listing_sort_price
, CAST(NULL AS SIGNED) AS listing_sort_rank
, (SELECT o.membership_tier FROM owners o WHERE o.id = p.owner_id LIMIT 1) AS owner_membership_tier
, (SELECT o.membership_expires_at FROM owners o WHERE o.id = p.owner_id LIMIT 1) AS owner_membership_expires_at
SQL;

        $listingExtrasBranch2 = <<<'SQL'
, u.id AS listing_unit_id
, u.name AS listing_unit_name
, u.cover_image AS listing_unit_cover
, u.price AS listing_unit_price
, u.bedrooms AS listing_unit_bedrooms
, u.bathrooms AS listing_unit_bathrooms
, u.capacity_min AS listing_unit_cap_min
, u.capacity_max AS listing_unit_cap_max
, u.price AS listing_sort_price
, u.sort_order AS listing_sort_rank
, (SELECT o.membership_tier FROM owners o WHERE o.id = p.owner_id LIMIT 1) AS owner_membership_tier
, (SELECT o.membership_expires_at FROM owners o WHERE o.id = p.owner_id LIMIT 1) AS owner_membership_expires_at
SQL;

        $sqlBranch1 = "SELECT p.*{$listingExtrasBranch1} FROM properties p WHERE {$sharedSql1}{$branch1Tail}";
        $sqlBranch2 = "SELECT p.*{$listingExtrasBranch2}
            FROM properties p
            INNER JOIN property_units u ON u.property_id = p.id AND " . self::publicUnitCondition('u') . "
            WHERE {$sharedSql2}{$branch2Tail}";

        $unionSql = "SELECT * FROM (({$sqlBranch1}) UNION ALL ({$sqlBranch2})) merged";

        $total = (int)Database::fetch("SELECT COUNT(*) AS c FROM ({$unionSql}) cnt", $params)['c'];
        $offset = max(0, ($page - 1) * $perPage);

        $rows = Database::fetchAll(
            "{$unionSql} ORDER BY {$orderPrimary} LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total];
    }


    public static function unitOrderSql(string $alias = ''): string
    {
        $p = $alias !== '' ? $alias . '.' : '';
        if (Database::tableHasColumn('property_units', 'homepage_priority')) {
            return "{$p}homepage_priority DESC, {$p}is_featured DESC, {$p}sort_order ASC, {$p}id ASC";
        }

        return "{$p}sort_order ASC, {$p}id ASC";
    }

    /** @param array<int,array<string,mixed>> $rows */
    public static function sortHomepageListingRows(array $rows, bool $boostCoupon = false): array
    {
        usort($rows, static function (array $a, array $b) use ($boostCoupon): int {
            $score = static function (array $r) use ($boostCoupon): array {
                $parts = [
                    max((int)($r['listing_unit_is_featured'] ?? 0), (int)($r['is_featured'] ?? 0)),
                ];
                if ($boostCoupon) {
                    $parts[] = (int)($r['coupon_enabled'] ?? 0);
                }
                $parts[] = max((int)($r['listing_unit_homepage_priority'] ?? 0), (int)($r['priority'] ?? 0));
                $parts[] = (float)($r['rating_avg'] ?? 0);
                $parts[] = (int)($r['id'] ?? 0);
                $parts[] = (int)($r['listing_unit_id'] ?? 0);

                return $parts;
            };
            $sa = $score($a);
            $sb = $score($b);
            foreach ($sa as $i => $va) {
                $vb = $sb[$i];
                if ($va === $vb) {
                    continue;
                }
                return $vb <=> $va;
            }

            return 0;
        });

        return $rows;
    }

    public static function featuredForHomepage(int $limit = 8): array
    {
        $limit = max(1, min(24, $limit));
        $out = [];
        $seen = [];

        if (Database::tableHasColumn('property_units', 'is_featured')) {
            $unitOk = self::publicUnitCondition('u');
            $unitRows = Database::fetchAll(
                "SELECT p.*, u.id AS _fu_id FROM properties p
                 INNER JOIN property_units u ON u.property_id = p.id AND {$unitOk}
                 WHERE p.status = 'published' AND u.is_featured = 1
                 ORDER BY u.homepage_priority DESC, p.priority DESC, p.rating_avg DESC, u.id ASC
                 LIMIT {$limit}"
            );
            foreach ($unitRows as $pr) {
                $uid = (int)$pr['_fu_id'];
                unset($pr['_fu_id']);
                $unit = Database::fetch('SELECT * FROM property_units WHERE id = :id', ['id' => $uid]);
                if (!$unit) {
                    continue;
                }
                $out[] = self::mergeUnitIntoPropertyRowForListing($pr, $unit);
                $seen['u:' . $uid] = true;
            }
        }

        if (count($out) < $limit) {
            foreach (self::featured($limit * 3) as $p) {
                foreach (self::expandListingRowsForDisplay([$p]) as $row) {
                    $key = !empty($row['listing_unit_id'])
                        ? 'u:' . (int)$row['listing_unit_id']
                        : 'p:' . (int)$row['id'];
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;
                    $out[] = $row;
                    if (count($out) >= $limit) {
                        break 2;
                    }
                }
            }
        }

        return self::sortHomepageListingRows(array_slice($out, 0, $limit));
    }

    /** @param string|list<string> $types */
    public static function featuredByType(string|array $types, int $limit = 8, bool $boostCoupon = false): array
    {
        $types = self::normalizeTypes($types);
        if ($types === []) {
            return [];
        }

        $limit = max(1, min(24, $limit));
        $out = [];
        $seen = [];
        $typeFilter = self::typeFilterSql($types, 't');
        $typeSql = $typeFilter['sql'] !== '' ? ' AND ' . $typeFilter['sql'] : '';
        $typeParams = $typeFilter['params'];

        if (Database::tableHasColumn('property_units', 'is_featured')) {
            $unitOk = self::publicUnitCondition('u');
            $unitRows = Database::fetchAll(
                "SELECT p.*, u.id AS _fu_id FROM properties p
                 INNER JOIN property_units u ON u.property_id = p.id AND {$unitOk}
                 WHERE p.status = 'published' AND u.is_featured = 1{$typeSql}
                 ORDER BY u.homepage_priority DESC, p.priority DESC, p.rating_avg DESC, u.id ASC
                 LIMIT {$limit}",
                $typeParams
            );
            foreach ($unitRows as $pr) {
                $uid = (int)$pr['_fu_id'];
                unset($pr['_fu_id']);
                $unit = Database::fetch('SELECT * FROM property_units WHERE id = :id', ['id' => $uid]);
                if (!$unit) {
                    continue;
                }
                $out[] = self::mergeUnitIntoPropertyRowForListing($pr, $unit);
                $seen['u:' . $uid] = true;
            }
        }

        if (count($out) < $limit) {
            $inTypes = implode(',', array_map(static fn (string $t): string => "'" . $t . "'", $types));
            $propRows = Database::fetchAll(
                "SELECT * FROM properties WHERE status='published' AND is_featured=1 AND type IN ({$inTypes})
                 ORDER BY priority DESC, rating_avg DESC LIMIT " . ($limit * 3)
            );
            foreach ($propRows as $p) {
                foreach (self::expandListingRowsForDisplay([$p]) as $row) {
                    $key = !empty($row['listing_unit_id'])
                        ? 'u:' . (int)$row['listing_unit_id']
                        : 'p:' . (int)$row['id'];
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;
                    $out[] = $row;
                    if (count($out) >= $limit) {
                        break 2;
                    }
                }
            }
        }

        return self::sortHomepageListingRows(array_slice($out, 0, $limit), $boostCoupon);
    }

    public static function featured(int $limit = 6): array
    {
        return Database::fetchAll(
            "SELECT * FROM properties WHERE status='published' AND is_featured=1
             ORDER BY priority DESC, rating_avg DESC LIMIT $limit"
        );
    }

    public static function newest(int $limit = 4): array
    {
        return Database::fetchAll(
            "SELECT * FROM properties WHERE status='published'
             ORDER BY created_at DESC LIMIT $limit"
        );
    }

    /** @param string|list<string> $types */
    public static function newestByType(string|array $types, int $limit = 4): array
    {
        $types = self::normalizeTypes($types);
        if ($types === []) {
            return [];
        }
        $limit = max(1, min(24, $limit));
        $inTypes = implode(',', array_map(static fn (string $t): string => "'" . $t . "'", $types));

        return Database::fetchAll(
            "SELECT * FROM properties WHERE status='published' AND type IN ({$inTypes})
             ORDER BY created_at DESC LIMIT {$limit}"
        );
    }

    /** คำจำกัดความแถบ «แพตามโซน» บนหน้าแรก — zones ต้องตรงกับค่าใน properties.zone */
    public static function homeZoneSectionDefinitions(): array
    {
        $sections = \App\Support\HomepageSections::zoneSections();
        $out = [];
        foreach ($sections as $z) {
            if (empty($z['enabled'])) {
                continue;
            }
            $out[] = [
                'id'    => $z['id'],
                'title' => $z['title'],
                'zones' => $z['zones'],
            ];
        }

        return $out;
    }

    /** @deprecated ใช้ homeZoneSectionDefinitions() */
    public static function homeRaftZoneSectionDefinitions(): array
    {
        return self::homeZoneSectionDefinitions();
    }

    /**
     * แพที่เผยแพร่ในโซนที่ระบุ (สำหรับหน้าแรก)
     *
     * @param array<int,string> $zones
     * @return array<int,array<string,mixed>>
     */
    public static function publishedPropertiesInZones(array $zones, int $limit = 8): array
    {
        return self::publishedInZonesByType($zones, self::PROPERTY_TYPES, $limit);
    }

    /** @param string|list<string> $types */
    public static function publishedInZonesByType(array $zones, string|array $types, int $limit = 8): array
    {
        $zones = array_values(array_unique(array_filter(array_map(static fn ($z) => trim((string)$z), $zones))));
        $types = self::normalizeTypes($types);
        if ($zones === [] || $types === []) {
            return [];
        }
        $limit = max(1, min(24, $limit));
        $ph = [];
        $params = [];
        foreach ($zones as $i => $z) {
            $k = 'z' . $i;
            $ph[] = ':' . $k;
            $params[$k] = $z;
        }
        $zoneIn = implode(',', $ph);
        $typeFilter = self::typeFilterSql($types, 'z');
        $typeSql = $typeFilter['sql'] !== '' ? ' AND ' . $typeFilter['sql'] : '';
        $params = array_merge($params, $typeFilter['params']);
        $unitOk = self::publicUnitCondition('u');
        $sql = "SELECT p.* FROM properties p
                WHERE p.status='published' AND p.zone IN ($zoneIn){$typeSql}
                AND EXISTS (
                    SELECT 1 FROM property_units u
                    WHERE u.property_id = p.id AND {$unitOk}
                )
                ORDER BY p.is_featured DESC, p.priority DESC, p.rating_avg DESC, p.id DESC
                LIMIT $limit";
        $rows = Database::fetchAll($sql, $params);

        return self::attachUnitStats(self::attachGalleryThumbnails($rows));
    }

    /** @deprecated ใช้ publishedPropertiesInZones() */
    public static function publishedRaftsInZones(array $zones, int $limit = 8): array
    {
        return self::publishedPropertiesInZones($zones, $limit);
    }

    public static function units(int $propertyId): array
    {
        return Database::fetchAll(
            'SELECT * FROM property_units WHERE property_id = :id AND ' . self::publicUnitCondition('') . ' ORDER BY sort_order, id',
            ['id' => $propertyId]
        );
    }

    public static function gallery(int $propertyId): array
    {
        $unitOnly = Database::tableHasColumn('property_images', 'unit_id')
            ? ' AND unit_id IS NULL'
            : '';
        try {
            return Database::fetchAll(
                "SELECT * FROM property_images WHERE property_id = :id{$unitOnly} ORDER BY is_cover DESC, sort_order, id",
                ['id' => $propertyId]
            );
        } catch (\PDOException $e) {
            if ($unitOnly === '') {
                throw $e;
            }

            return Database::fetchAll(
                "SELECT * FROM property_images WHERE property_id = :id ORDER BY is_cover DESC, sort_order, id",
                ['id' => $propertyId]
            );
        }
    }

    /** รูปห้อง (สูงสุดตามที่จำกัดใน UnitController) */
    public static function unitGalleryForUnit(int $propertyId, int $unitId): array
    {
        if (!Database::tableHasColumn('property_images', 'unit_id')) {
            return [];
        }
        try {
            return Database::fetchAll(
                "SELECT id, path FROM property_images WHERE property_id = :p AND unit_id = :u ORDER BY is_cover DESC, sort_order ASC, id ASC",
                ['p' => $propertyId, 'u' => $unitId]
            );
        } catch (\PDOException $e) {
            error_log('[Paekan] unitGalleryForUnit: ' . $e->getMessage());

            return [];
        }
    }

    /** @param list<string> $paths */
    private static function galleryThumbPathsFromList(array $paths, string $cover, int $maxThumbs): array
    {
        if ($maxThumbs <= 0) {
            return [];
        }
        $thumbs = [];
        foreach ($paths as $p) {
            if ($p === '' || $p === $cover) {
                continue;
            }
            $thumbs[] = $p;
            if (count($thumbs) >= $maxThumbs) {
                break;
            }
        }

        return $thumbs;
    }

    public static function attachGalleryThumbnails(array $rows, int $maxThumbs = 4): array
    {
        if ($maxThumbs <= 0 || $rows === []) {
            return $rows;
        }
        $propertyIds = [];
        $unitIds = [];
        foreach ($rows as $r) {
            $pid = (int)($r['id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $uid = (int)($r['listing_unit_id'] ?? 0);
            if ($uid > 0) {
                $unitIds[$uid] = true;
            } else {
                $propertyIds[$pid] = true;
            }
        }
        $hasUnitColumn = Database::tableHasColumn('property_images', 'unit_id');
        $byPid = [];
        $byUnit = [];
        if ($propertyIds !== []) {
            $ids = array_keys($propertyIds);
            sort($ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $unitOnly = $hasUnitColumn ? ' AND unit_id IS NULL' : '';
            $sql = "SELECT property_id, path FROM property_images WHERE property_id IN ($placeholders){$unitOnly}
                    ORDER BY property_id, is_cover DESC, sort_order ASC, id ASC";
            try {
                $imgRows = Database::fetchAll($sql, $ids);
            } catch (\PDOException $e) {
                if ($unitOnly === '') {
                    throw $e;
                }
                $sql = "SELECT property_id, path FROM property_images WHERE property_id IN ($placeholders)
                    ORDER BY property_id, is_cover DESC, sort_order ASC, id ASC";
                $imgRows = Database::fetchAll($sql, $ids);
            }
            foreach ($imgRows as $img) {
                $pid = (int)$img['property_id'];
                if (!isset($byPid[$pid])) {
                    $byPid[$pid] = [];
                }
                $byPid[$pid][] = $img['path'];
            }
        }
        if ($unitIds !== [] && $hasUnitColumn) {
            $uids = array_keys($unitIds);
            sort($uids);
            $placeholders = implode(',', array_fill(0, count($uids), '?'));
            try {
                $imgRows = Database::fetchAll(
                    "SELECT unit_id, path FROM property_images WHERE unit_id IN ($placeholders)
                     ORDER BY unit_id, is_cover DESC, sort_order ASC, id ASC",
                    $uids
                );
                foreach ($imgRows as $img) {
                    $uid = (int)$img['unit_id'];
                    if (!isset($byUnit[$uid])) {
                        $byUnit[$uid] = [];
                    }
                    $byUnit[$uid][] = $img['path'];
                }
            } catch (\PDOException $e) {
                error_log('[Paekan] attachGalleryThumbnails unit: ' . $e->getMessage());
            }
        }
        foreach ($rows as &$r) {
            $pid = (int)($r['id'] ?? 0);
            $uid = (int)($r['listing_unit_id'] ?? 0);
            if ($uid > 0) {
                $cover = (string)($r['listing_unit_cover'] ?? '');
                if ($cover === '') {
                    $cover = (string)($r['cover_image'] ?? '');
                }
                $paths = $byUnit[$uid] ?? [];
                if ($paths === []) {
                    $paths = $byPid[$pid] ?? [];
                }
            } else {
                $cover = (string)($r['cover_image'] ?? '');
                $paths = $byPid[$pid] ?? [];
            }
            $r['_gallery_thumb_paths'] = self::galleryThumbPathsFromList($paths, $cover, $maxThumbs);
        }
        unset($r);

        return $rows;
    }

    /** สรุปห้องนอน/ห้องน้ำ/ความจุจาก units (ใช้การ์ดหน้าแรกเดสก์ท็อป) */
    public static function attachUnitStats(array $rows): array
    {
        if ($rows === []) {
            return $rows;
        }
        $ids = [];
        foreach ($rows as $r) {
            if (!empty($r['id'])) {
                $ids[(int)$r['id']] = true;
            }
        }
        $ids = array_keys($ids);
        sort($ids);
        if ($ids === []) {
            return $rows;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT property_id,
                       MIN(bedrooms) AS br_min,
                       MAX(bedrooms) AS br_max,
                       MIN(bathrooms) AS ba_min,
                       MAX(bathrooms) AS ba_max,
                       MIN(capacity_min) AS cap_min,
                       MAX(capacity_max) AS cap_max
                FROM property_units WHERE " . self::publicUnitCondition('') . " AND property_id IN ($placeholders)
                GROUP BY property_id";
        $stats = Database::fetchAll($sql, $ids);
        $map = [];
        foreach ($stats as $s) {
            $map[(int)$s['property_id']] = $s;
        }
        foreach ($rows as &$r) {
            if (!empty($r['listing_unit_id'])) {
                continue;
            }
            $pid = (int)($r['id'] ?? 0);
            $m = $map[$pid] ?? null;
            $brMin = $m ? (int)$m['br_min'] : 0;
            $brMax = $m ? (int)$m['br_max'] : 0;
            $baMin = $m ? (int)$m['ba_min'] : 0;
            $baMax = $m ? (int)$m['ba_max'] : 0;
            $r['_unit_br_min'] = $brMin;
            $r['_unit_br_max'] = $brMax;
            $r['_unit_ba_min'] = $baMin;
            $r['_unit_ba_max'] = $baMax;
            // โค้ดเก่าที่ยังอ่านค่าเดียว: ใช้ช่วงบนสุดเป็นตัวแทน
            $r['_unit_br'] = $brMax;
            $r['_unit_ba'] = $baMax;
            $r['_unit_cap_min'] = $m ? (int)$m['cap_min'] : 0;
            $r['_unit_cap_max'] = $m ? (int)$m['cap_max'] : 0;
        }
        unset($r);

        return $rows;
    }

    /** ช่วงตัวเลขไทย เช่น "3 ห้องนอน" หรือ "1–3 ห้องนอน" */
    private static function formatIntRangeLabel(int $min, int $max, string $suffix): string
    {
        if ($max <= 0) {
            return '';
        }
        if ($min <= 0 || $min === $max) {
            return $max . ' ' . $suffix;
        }

        return $min . '–' . $max . ' ' . $suffix;
    }

    /**
     * ข้อความบรรทัดห้องนอน/ห้องน้ำ/ความจุบนการ์ดรายการ — โรงแรม/รีสอร์ทแยกโหมดจากแพ/บ้าน
     */
    public static function listingUnitSummaryLine(array $property): string
    {
        $type = (string)($property['type'] ?? '');
        $cmin = (int)($property['_unit_cap_min'] ?? 0);
        $cmax = (int)($property['_unit_cap_max'] ?? 0);
        $brMin = (int)($property['_unit_br_min'] ?? 0);
        $brMax = (int)($property['_unit_br_max'] ?? 0);
        $baMin = (int)($property['_unit_ba_min'] ?? 0);
        $baMax = (int)($property['_unit_ba_max'] ?? 0);

        if ($brMax === 0 && !empty($property['_unit_br'])) {
            $brMin = $brMax = (int)$property['_unit_br'];
        }
        if ($baMax === 0 && !empty($property['_unit_ba'])) {
            $baMin = $baMax = (int)$property['_unit_ba'];
        }

        if (in_array($type, ['hotel', 'resort'], true)) {
            $parts = [];
            if ($cmax > 0) {
                $parts[] = ($cmin > 0 && $cmin !== $cmax)
                    ? 'รองรับ ' . $cmin . '–' . $cmax . ' คน'
                    : 'รองรับสูงสุด ' . $cmax . ' คน';
            }
            $parts[] = 'หลายแบบห้องพัก';

            return implode(' · ', array_filter($parts));
        }

        $parts = [];
        $brLbl = self::formatIntRangeLabel($brMin, $brMax, 'ห้องนอน');
        $baLbl = self::formatIntRangeLabel($baMin, $baMax, 'ห้องน้ำ');
        if ($brLbl !== '') {
            $parts[] = $brLbl;
        }
        if ($baLbl !== '') {
            $parts[] = $baLbl;
        }
        if ($cmax > 0) {
            $parts[] = 'พักได้ ' . (($cmin > 0 && $cmin !== $cmax) ? $cmin . '–' . $cmax : (string)$cmax) . ' คน';
        }

        return implode(' · ', $parts);
    }

    public static function amenities(int $propertyId): array
    {
        return Database::fetchAll(
            "SELECT a.* FROM amenities a
             JOIN property_amenities pa ON pa.amenity_id = a.id
             WHERE pa.property_id = :id ORDER BY a.sort_order",
            ['id' => $propertyId]
        );
    }

    public static function reviews(int $propertyId, int $limit = 6, int $offset = 0): array
    {
        $limit  = (int)$limit;
        $offset = (int)$offset;
        return Database::fetchAll(
            "SELECT * FROM reviews WHERE property_id = :id AND is_approved=1
             ORDER BY created_at DESC LIMIT $limit OFFSET $offset",
            ['id' => $propertyId]
        );
    }

    public static function incrementView(int $id): void
    {
        Database::query("UPDATE properties SET view_count = view_count + 1 WHERE id = :id", ['id' => $id]);
    }

    public static function recalcMinPrice(int $propertyId): void
    {
        $cnt = (int)Database::fetch(
            'SELECT COUNT(*) AS c FROM property_units WHERE property_id = :id AND ' . self::publicUnitCondition(''),
            ['id' => $propertyId]
        )['c'];
        if ($cnt === 0) {
            return;
        }
        $row = Database::fetch(
            'SELECT MIN(price) AS m FROM property_units WHERE property_id = :id AND ' . self::publicUnitCondition(''),
            ['id' => $propertyId]
        );
        Database::update('properties', ['min_price' => (float)($row['m'] ?? 0)], 'id = :id', ['id' => $propertyId]);
    }

    public static function syncPropertyAmenities(int $propertyId, array $amenityIds): void
    {
        Database::delete('property_amenities', 'property_id = :p', ['p' => $propertyId]);
        foreach ($amenityIds as $aid) {
            $aid = (int)$aid;
            if ($aid > 0) {
                Database::insert('property_amenities', ['property_id' => $propertyId, 'amenity_id' => $aid]);
            }
        }
    }

    public static function uniqueSlug(string $name, ?string $nameEn = null, ?int $exceptPropertyId = null): string
    {
        $base = property_slug_base($name, $nameEn);
        $slug = $base;
        $i = 1;
        while (true) {
            $row = Database::fetch('SELECT id FROM properties WHERE slug = :s', ['s' => $slug]);
            if (!$row || ($exceptPropertyId !== null && (int)$row['id'] === $exceptPropertyId)) {
                return $slug;
            }
            $slug = $base . '-' . (++$i);
        }
    }

    /** Resolve slug for save — custom admin input or auto from names. */
    public static function resolveSlugForSave(
        string $name,
        ?string $nameEn,
        ?string $customSlug,
        bool $useCustomSlug,
        ?int $exceptPropertyId = null
    ): string {
        if ($useCustomSlug && trim((string)$customSlug) !== '') {
            $base = slugify((string)$customSlug) ?: property_slug_base($name, $nameEn);
            $slug = $base;
            $i = 1;
            while (true) {
                $row = Database::fetch('SELECT id FROM properties WHERE slug = :s', ['s' => $slug]);
                if (!$row || ($exceptPropertyId !== null && (int)$row['id'] === $exceptPropertyId)) {
                    return $slug;
                }
                $slug = $base . '-' . (++$i);
            }
        }

        return self::uniqueSlug($name, $nameEn, $exceptPropertyId);
    }

    public static function distinctZones(): array
    {
        return Database::fetchAll("SELECT DISTINCT zone FROM properties WHERE status='published' AND zone IS NOT NULL AND zone <> '' ORDER BY zone");
    }

    /**
     * ตัวเลือกโซนสำหรับฟอร์มเพิ่ม/แก้ไขที่พัก — รวมตาราง zones + โซนที่เคยมีในฐานข้อมูล + ค่าปัจจุบัน (ตอนแก้ไข)
     *
     * @return list<string>
     */
    public static function zonesForSelect(?string $ensureInclude = null): array
    {
        return Zone::namesForSelectMerged($ensureInclude);
    }

    /**
     * ผูก path รูปปกจุดหมายจาก settings key home_zone_cover_images (JSON: ชื่อโซน => path ใน uploads)
     *
     * @param array<int,array<string,mixed>> $zones จาก distinctZones()
     * @return array<int,array<string,mixed>>
     */
    public static function attachZoneCoverImages(array $zones): array
    {
        $raw = Setting::get('home_zone_cover_images', '{}');
        $map = json_decode((string)$raw, true);
        if (!is_array($map)) {
            $map = [];
        }
        foreach ($zones as &$z) {
            $name = (string)($z['zone'] ?? '');
            $path = ($name !== '' && isset($map[$name]) && $map[$name] !== '') ? (string)$map[$name] : null;
            $z['destination_image'] = $path;
        }
        unset($z);

        return $zones;
    }

    /** ป้ายชื่อฟิลด์ใน owner_intake JSON — ตรงกับ docs/property-paper-intake-mapping.md */
    public static function ownerIntakeFieldLabels(): array
    {
        return [
            'group_packages' => 'หมู่คณะ / Package',
            'day_trip_no_overnight' => 'ไม่ค้างคืน / ล่องแพ',
            'activities_pricing' => 'กิจกรรม / เครื่องเล่น / ราคา',
            'seasonal_note' => 'โลว์–ไฮซีซัน (เพิ่มเติม)',
            'whole_house_extra' => 'เหมาหลัง — ค่าใช้จ่ายเพิ่ม',
            'whole_house_food' => 'เหมาหลัง — สั่งอาหาร',
            'child_policy' => 'ราคาเด็ก',
            'pets_note' => 'สัตว์เลี้ยง (รายละเอียด)',
        ];
    }

    /** @return array<string,mixed> */
    public static function decodeOwnerIntake(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_array($raw)) {
            return $raw;
        }
        $j = json_decode((string)$raw, true);

        return is_array($j) ? $j : [];
    }

    public static function encodeOwnerIntakeFromPost(array $post): ?string
    {
        $out = [];
        foreach (array_keys(self::ownerIntakeFieldLabels()) as $k) {
            $v = trim((string)($post['intake_' . $k] ?? ''));
            if ($v !== '') {
                $out[$k] = $v;
            }
        }

        return $out === [] ? null : json_encode($out, JSON_UNESCAPED_UNICODE);
    }

    public static function normalizeRaftVariant(string $type, mixed $postVal): ?string
    {
        if ($type !== 'raft') {
            return null;
        }
        $v = is_string($postVal) ? $postVal : '';

        return in_array($v, ['shore', 'towed'], true) ? $v : null;
    }

    /** สำหรับการ์ดแนวนอนโหมดรายละเอียด — สูงสุด $max บรรทัดสั้น */
    public static function ownerIntakeCompactLines(array $property, int $max = 2): array
    {
        $max = max(0, $max);
        if ($max === 0) {
            return [];
        }
        $intake = self::decodeOwnerIntake($property['owner_intake'] ?? null);
        if ($intake === []) {
            return [];
        }
        $labels = self::ownerIntakeFieldLabels();
        $out = [];
        foreach ($labels as $k => $label) {
            if (empty($intake[$k]) || !is_string($intake[$k])) {
                continue;
            }
            $t = trim($intake[$k]);
            if ($t === '') {
                continue;
            }
            if (mb_strlen($t) > 90) {
                $t = mb_substr($t, 0, 87) . '…';
            }
            $out[] = ['label' => $label, 'text' => $t];
            if (count($out) >= $max) {
                break;
            }
        }

        return $out;
    }
}
