<?php

namespace App\Controllers;



use App\Core\Controller;

use App\Core\Database;

use App\Models\Property;
use App\Models\PropertyLeadClick;
use App\Support\PropertyBookingCapabilities;



class PropertyController extends Controller

{

    public function index(): void

    {

        $redirect = self::canonicalTypeRedirect();

        if ($redirect !== null) {

            header('Location: ' . $redirect, true, 301);

            exit;

        }

        $this->renderListing();

    }



    public function rafts(): void

    {

        $this->renderListing('raft', self::typePageConfig('raft'));

    }



    public function resorts(): void

    {

        $this->renderListing('resort', self::typePageConfig('resort'));

    }



    public function hotels(): void

    {

        $this->renderListing('hotel', self::typePageConfig('hotel'));

    }



    public function stays(): void

    {

        $subtype = trim((string)($_GET['type'] ?? ''));

        $allowed = ['homestay', 'house'];

        $forcedType = in_array($subtype, $allowed, true) ? $subtype : null;



        $this->renderListing($forcedType, self::typePageConfig('stays'), ['homestay', 'house']);

    }



    public function poolVillas(): void

    {

        $this->renderListing('pool_villa', self::typePageConfig('pool_villa'));

    }



    public function camping(): void

    {

        $this->renderListing('camping', self::typePageConfig('camping'));

    }



    /** @return array<string,mixed> */

    private static function typePageConfig(string $key): array

    {

        return match ($key) {

            'raft' => [

                'path' => '/rafts',

                'title' => 'แพพักกาญจนบุรี',

                'subtitle' => 'รวมแพพักริมเขื่อนและริมแม่น้ำในกาญจนบุรี เลือกแพริมน้ำ แพลาก โซน และจำนวนผู้เข้าพัก',

                'eyebrow' => 'Raft House Collection',

                'icon' => 'anchor',

                'gradient' => 'raft',

                'chips' => [

                    ['icon' => 'anchor', 'label' => 'แพริมน้ำ & แพลาก'],

                    ['icon' => 'bed-double', 'label' => 'เลือกตามห้องนอน'],

                    ['icon' => 'users', 'label' => 'เหมาะกับครอบครัวและกลุ่มเพื่อน'],

                ],

            ],

            'resort' => [

                'path' => '/resorts',

                'title' => 'รีสอร์ทกาญจนบุรี',

                'subtitle' => 'ค้นหารีสอร์ทในกาญจนบุรี ใกล้ธรรมชาติ ริมแม่น้ำ และเขื่อน พร้อมฟิลเตอร์โซนและงบประมาณ',

                'eyebrow' => 'Resort Collection',

                'icon' => 'trees',

                'gradient' => 'default',

                'chips' => [

                    ['icon' => 'trees', 'label' => 'ใกล้ธรรมชาติ'],

                    ['icon' => 'map-pin', 'label' => 'เลือกตามโซน'],

                    ['icon' => 'ticket', 'label' => 'ใช้คูปองได้'],

                ],

            ],

            'hotel' => [

                'path' => '/hotels',

                'title' => 'โรงแรมกาญจนบุรี',

                'subtitle' => 'รวมโรงแรมในกาญจนบุรี เลือกตามโซน งบประมาณ และสิ่งอำนวยความสะดวก',

                'eyebrow' => 'Hotel Collection',

                'icon' => 'building-2',

                'gradient' => 'default',

                'chips' => [

                    ['icon' => 'building-2', 'label' => 'โรงแรมคุณภาพ'],

                    ['icon' => 'map-pin', 'label' => 'เลือกตามโซน'],

                    ['icon' => 'ticket', 'label' => 'ใช้คูปองได้'],

                ],

            ],

            'stays' => [

                'path' => '/stays',

                'title' => 'ที่พักกาญจนบุรี — โฮมสเตย์ & บ้านพัก',

                'subtitle' => 'ค้นหาโฮมสเตย์และบ้านพักในกาญจนบุรี เหมาะกับครอบครัวและกลุ่มเพื่อน',

                'eyebrow' => 'Homestay & House Stay',

                'icon' => 'home',

                'gradient' => 'default',

                'subtype_tabs' => [

                    '' => 'ทั้งหมด',

                    'homestay' => 'โฮมสเตย์',

                    'house' => 'บ้านพัก',

                ],

                'chips' => [

                    ['icon' => 'home', 'label' => 'โฮมสเตย์ & บ้านพัก'],

                    ['icon' => 'users', 'label' => 'เหมาะกับครอบครัว'],

                    ['icon' => 'map-pin', 'label' => 'เลือกตามโซน'],

                ],

            ],

            'pool_villa' => [

                'path' => '/pool-villas',

                'title' => 'บ้านพูลวิลล่ากาญจนบุรี',

                'subtitle' => 'รวมบ้านพูลวิลล่าและบ้านพักมีสระในกาญจนบุรี เลือกจำนวนห้องนอน ความจุ และโซนที่เดินทางสะดวก',

                'eyebrow' => 'Pool Villa Collection',

                'icon' => 'waves',

                'gradient' => 'pool',

                'chips' => [

                    ['icon' => 'waves', 'label' => 'สระว่ายน้ำ'],

                    ['icon' => 'bed-double', 'label' => 'เลือกตามห้องนอน'],

                    ['icon' => 'users', 'label' => 'เหมาะกับครอบครัวและกลุ่มเพื่อน'],

                ],

            ],

            'camping' => [

                'path' => '/camping',

                'title' => 'แคมป์และลานกางเต็นท์กาญจนบุรี',

                'subtitle' => 'ค้นหาแคมป์ ลานกางเต็นท์ และที่พักสายธรรมชาติในกาญจนบุรี พร้อมฟิลเตอร์โซน งบประมาณ และสิ่งอำนวยความสะดวก',

                'eyebrow' => 'Camping & Nature Stay',

                'icon' => 'tent',

                'gradient' => 'camping',

                'chips' => [

                    ['icon' => 'tent', 'label' => 'สายแคมป์'],

                    ['icon' => 'trees', 'label' => 'ใกล้ธรรมชาติ'],

                    ['icon' => 'map-pin', 'label' => 'เลือกตามโซนท่องเที่ยว'],

                ],

            ],

            default => [],

        };

    }



    /**

     * @param array<string,mixed>|null $typePage

     * @param list<string>|null $forcedTypes

     */

    private function renderListing(?string $forcedType = null, ?array $typePage = null, ?array $forcedTypes = null): void

    {

        $perPage = (int)config('app.paginate.properties', 12);

        $page    = max(1, (int)($_GET['page'] ?? 1));



        $rv = $_GET['raft_variant'] ?? '';

        $rv = in_array($rv, ['shore', 'towed'], true) ? $rv : '';



        $guestsInt = max(0, (int)($_GET['guests'] ?? 0));

        if ($guestsInt > 0) {

            $guestsInt = min($guestsInt, 120);

        }

        $guestsStr = $guestsInt > 0 ? (string)$guestsInt : '';



        $typeFromQuery = trim((string)($_GET['type'] ?? ''));

        $resolvedType = $forcedType ?? ($typeFromQuery !== '' ? $typeFromQuery : '');

        $resolvedTypes = $forcedTypes;



        if ($resolvedType !== '' && $resolvedTypes !== null) {

            if (!in_array($resolvedType, $resolvedTypes, true)) {

                $resolvedType = '';

            } else {

                $resolvedTypes = null;

            }

        }



        $f = [

            'q'         => trim((string)($_GET['q'] ?? '')),

            'zone'      => $_GET['zone'] ?? '',

            'type'      => $resolvedType,

            'types'     => $resolvedTypes ?? [],

            'raft_variant' => $rv,

            'guests'    => $guestsStr,

            'budget_min'=> $_GET['budget_min'] ?? '',

            'budget_max'=> $_GET['budget_max'] ?? '',

            'pet'       => !empty($_GET['pet']),

            'coupon'    => !empty($_GET['coupon']),

            'amenities' => $_GET['amenities'] ?? [],

            'sort'      => $_GET['sort'] ?? 'recommended',

            'bedrooms_min'  => max(0, (int)($_GET['bedrooms_min'] ?? 0)),

            'bathrooms_min' => max(0, (int)($_GET['bathrooms_min'] ?? 0)),

        ];



        $result = Property::search($f, $page, $perPage);

        $result['rows'] = Property::normalizeListingCardRows($result['rows']);

        $result['rows'] = Property::attachGalleryThumbnails($result['rows']);

        $result['rows'] = Property::attachUnitStats($result['rows']);



        $amenities = Database::fetchAll("SELECT * FROM amenities ORDER BY sort_order, name");

        $zones     = Property::distinctZones();

        $types     = ['raft'=>'แพพัก','resort'=>'รีสอร์ท','homestay'=>'โฮมสเตย์','house'=>'บ้านพัก','pool_villa'=>'บ้านพูลวิลล่า','hotel'=>'โรงแรม','camping'=>'แคมป์ปิ้ง'];



        $seo = self::buildPropertiesListingSeo($f, $page, $types, $typePage);



        $this->view('properties/index', [

            'meta_title'       => $seo['meta_title'],

            'meta_description' => $seo['meta_description'],

            'meta_canonical'   => $seo['meta_canonical'],

            'rows'      => $result['rows'],

            'total'     => $result['total'],

            'page'      => $page,

            'perPage'   => $perPage,

            'totalPages'=> max(1, (int)ceil($result['total'] / $perPage)),

            'filter'    => $f,

            'amenities' => $amenities,

            'zones'     => $zones,

            'types'     => $types,

            'type_page' => $typePage,

        ]);

    }



    private static function canonicalTypeRedirect(): ?string

    {

        $type = trim((string)($_GET['type'] ?? ''));

        if ($type === '') {

            return null;

        }



        $extraKeys = ['q', 'zone', 'raft_variant', 'guests', 'budget_min', 'budget_max', 'pet', 'coupon', 'amenities', 'sort', 'bedrooms_min', 'bathrooms_min', 'page'];

        foreach ($extraKeys as $key) {

            if ($key === 'page') {

                if ((int)($_GET['page'] ?? 1) > 1) {

                    return null;

                }

                continue;

            }

            if ($key === 'amenities') {

                $am = $_GET['amenities'] ?? [];

                if (is_array($am) ? $am !== [] : ($am !== '' && $am !== null)) {

                    return null;

                }

                continue;

            }

            if (!empty($_GET[$key])) {

                return null;

            }

        }



        $zone = trim((string)($_GET['zone'] ?? ''));

        $qs = $zone !== '' ? '?' . http_build_query(['zone' => $zone]) : '';



        return match ($type) {

            'raft' => url('/rafts' . $qs),

            'resort' => url('/resorts' . $qs),

            'hotel' => url('/hotels' . $qs),

            'homestay', 'house' => self::staysRedirectUrl($type, $zone),

            'pool_villa' => url('/pool-villas' . $qs),

            'camping' => url('/camping' . $qs),

            default => null,

        };

    }

    private static function staysRedirectUrl(string $type, string $zone): string
    {
        $params = [];
        if ($zone !== '') {
            $params['zone'] = $zone;
        }
        if ($type !== '') {
            $params['type'] = $type;
        }

        return url('/stays' . ($params !== [] ? '?' . http_build_query($params) : ''));
    }



    public function show(string $slug): void

    {

        $property = Property::findBySlug($slug);

        if (!$property) { http_response_code(404); $this->view('errors/404'); return; }



        Property::incrementView((int)$property['id']);

        $units      = Property::units((int)$property['id']);

        foreach ($units as &$u) {

            $g = Property::unitGalleryForUnit((int)$property['id'], (int)$u['id']);

            $u['_gallery_paths'] = array_column($g, 'path');

        }

        unset($u);

        $gallery    = Property::gallery((int)$property['id']);

        $amenities  = Property::amenities((int)$property['id']);

        $reviews    = Property::reviews((int)$property['id'], 6);

        $reviewCount= (int)$property['rating_count'];



        $similar = Database::fetchAll(

            "SELECT * FROM properties WHERE status='published' AND id <> :id AND zone = :zone

             ORDER BY rating_avg DESC LIMIT 3",

            ['id' => $property['id'], 'zone' => $property['zone']]

        );



        $similar = Property::attachUnitStats(Property::attachGalleryThumbnails($similar));



        $coverImg = (string)($property['cover_image'] ?? '');

        $metaOg   = $coverImg !== '' ? upload_url($coverImg) : '';



        $descPlain = strip_tags((string)($property['meta_description'] ?: $property['description'] ?? ''));

        $schema = [

            '@context' => 'https://schema.org',

            '@type'    => 'LodgingBusiness',

            'name'     => $property['name'],

            'description' => mb_substr($descPlain, 0, 5000),

            'url'      => url('/property/' . $property['slug']),

        ];

        if ($coverImg !== '') {

            $schema['image'] = [$metaOg];

        }

        $addr = ['@type' => 'PostalAddress'];

        if (!empty($property['address'])) {

            $addr['streetAddress'] = $property['address'];

        }

        $addr['addressRegion'] = $property['province'] ?? 'กาญจนบุรี';

        if (!empty($property['district'])) {

            $addr['addressLocality'] = $property['district'];

        }

        if (count($addr) > 1) {

            $schema['address'] = $addr;

        }

        if (!empty($property['latitude']) && !empty($property['longitude'])) {

            $schema['geo'] = [

                '@type'     => 'GeoCoordinates',

                'latitude'  => (float)$property['latitude'],

                'longitude' => (float)$property['longitude'],

            ];

        }

        if (!empty($property['phone'])) {

            $schema['telephone'] = $property['phone'];

        }



        $highlightUnitId = (int)($_GET['unit'] ?? 0);

        if ($highlightUnitId > 0) {

            $unitOk = false;

            foreach ($units as $u) {

                if ((int)$u['id'] === $highlightUnitId) {

                    $unitOk = true;

                    break;

                }

            }

            if (!$unitOk) {

                $highlightUnitId = 0;

            }

        }



        $this->view('properties/show', [

            'meta_title'       => $property['meta_title'] ?: ($property['name'] . ' — แพกาญ.com'),

            'meta_description' => $property['meta_description'] ?: mb_substr(strip_tags($property['description'] ?? ''), 0, 200),

            'meta_og_image'    => $metaOg,

            'meta_canonical'   => url('/property/' . $property['slug']),

            'schema_org_json'  => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),

            'property'    => $property,

            'units'       => $units,

            'gallery'     => $gallery,

            'amenities'   => $amenities,

            'reviews'     => $reviews,

            'reviewCount' => $reviewCount,

            'similar'     => $similar,

            'highlight_unit_id' => $highlightUnitId,

        ]);

    }



    /**

     * @param array<string,mixed> $f

     * @param array<string,string> $types

     * @return array{meta_title:string,meta_description:string,meta_canonical:string}

     */

    private static function buildPropertiesListingSeo(array $f, int $page, array $types, ?array $typePage = null): array

    {

        $site = 'แพกาญ.com';

        $baseTitle = 'ค้นหาที่พักกาญจนบุรี — แพ รีสอร์ท โรงแรม พูลวิลล่า | ' . $site;

        $baseDesc = 'เลือกที่พักในกาญจนบุรีตามความต้องการ ฟิลเตอร์ตามโซน ประเภท จำนวนคน และงบประมาณ';

        $basePath = (string)($typePage['path'] ?? '/properties');

        if ($typePage !== null) {

            $baseTitle = (string)$typePage['title'] . ' — ' . $site;

            $baseDesc = (string)$typePage['subtitle'];

        }



        $zone = trim((string)($f['zone'] ?? ''));

        $typeKey = (string)($f['type'] ?? '');

        $typeLabel = ($typeKey !== '' && isset($types[$typeKey])) ? $types[$typeKey] : '';

        $q = trim((string)($f['q'] ?? ''));

        $rv = (string)($f['raft_variant'] ?? '');

        $guests = trim((string)($f['guests'] ?? ''));

        $budgetMin = trim((string)($f['budget_min'] ?? ''));

        $budgetMax = trim((string)($f['budget_max'] ?? ''));

        $pet = !empty($f['pet']);

        $coupon = !empty($f['coupon']);

        $amenitiesRaw = $f['amenities'] ?? [];

        if (!is_array($amenitiesRaw)) {

            $amenitiesRaw = ($amenitiesRaw !== '' && $amenitiesRaw !== null) ? [$amenitiesRaw] : [];

        }

        $amenities = array_values(array_unique(array_filter(array_map(static fn ($v) => (int)$v, $amenitiesRaw), static fn (int $x): bool => $x > 0)));

        sort($amenities);

        $sort = (string)($f['sort'] ?? 'recommended');

        $bedroomsMin = (int)($f['bedrooms_min'] ?? 0);

        $bathroomsMin = (int)($f['bathrooms_min'] ?? 0);



        $isStaysSubtype = $typePage !== null

            && ($typePage['path'] ?? '') === '/stays'

            && $typeKey !== '';

        $typeCountsAsFilter = $typePage === null && $typeKey !== '';

        $hasFilters =

            $q !== '' || $zone !== '' || $typeCountsAsFilter || $isStaysSubtype || $rv !== ''

            || $guests !== '' || $budgetMin !== '' || $budgetMax !== ''

            || $pet || $coupon || $amenities !== []

            || $sort !== 'recommended'

            || $bedroomsMin > 0 || $bathroomsMin > 0;



        $needsQueryString = $page > 1 || $hasFilters;



        if (!$needsQueryString) {

            return [

                'meta_title'       => $baseTitle,

                'meta_description' => $baseDesc,

                'meta_canonical'   => url($basePath),

            ];

        }



        $cq = [];

        if ($page > 1) {

            $cq['page'] = $page;

        }

        if ($q !== '') {

            $cq['q'] = $q;

        }

        if ($zone !== '') {

            $cq['zone'] = $zone;

        }

        if ($typeKey !== '' && ($typePage === null || $isStaysSubtype)) {

            $cq['type'] = $typeKey;

        }

        if ($rv !== '') {

            $cq['raft_variant'] = $rv;

        }

        if ($guests !== '') {

            $cq['guests'] = $guests;

        }

        if ($budgetMin !== '') {

            $cq['budget_min'] = $budgetMin;

        }

        if ($budgetMax !== '') {

            $cq['budget_max'] = $budgetMax;

        }

        if ($pet) {

            $cq['pet'] = '1';

        }

        if ($coupon) {

            $cq['coupon'] = '1';

        }

        if ($amenities !== []) {

            $cq['amenities'] = $amenities;

        }

        if ($sort !== 'recommended') {

            $cq['sort'] = $sort;

        }

        if ($bedroomsMin > 0) {

            $cq['bedrooms_min'] = $bedroomsMin;

        }

        if ($bathroomsMin > 0) {

            $cq['bathrooms_min'] = $bathroomsMin;

        }

        $canonical = url($basePath) . ($cq !== [] ? '?' . http_build_query($cq) : '');



        $pageSuffix = $page > 1 ? ' · หน้า ' . $page : '';



        if (!$hasFilters) {

            return [

                'meta_title'       => $baseTitle . $pageSuffix,

                'meta_description' => $baseDesc,

                'meta_canonical'   => $canonical,

            ];

        }



        $rvLabels = ['shore' => 'แพริมฝั่ง', 'towed' => 'แพลากจูง'];

        $parts = [];

        if ($typeLabel !== '') {

            $parts[] = $typeLabel;

        }

        if ($zone !== '') {

            $parts[] = $zone;

        }

        if ($rv !== '' && isset($rvLabels[$rv])) {

            $parts[] = $rvLabels[$rv];

        }



        if ($parts !== []) {

            $head = implode(' · ', $parts);

            $title = $head . ' — ที่พักกาญจนบุรี | ' . $site;

        } elseif ($q !== '') {

            $short = mb_strlen($q) > 36 ? mb_substr($q, 0, 36) . '…' : $q;

            $title = 'ค้นหา "' . $short . '" — ที่พักกาญจนบุรี | ' . $site;

        } else {

            $title = 'ที่พักกาญจนบุรี — กรองผล | ' . $site;

        }

        $title .= $pageSuffix;



        $desc = $baseDesc;

        if ($zone !== '') {

            $desc = 'ที่พักในโซน ' . $zone . ' จังหวัดกาญจนบุรี · ' . $baseDesc;

        } elseif ($typeLabel !== '') {

            $desc = $typeLabel . 'ในกาญจนบุรี · ' . $baseDesc;

        } elseif ($q !== '') {

            $desc = 'ผลการค้นหา "' . (mb_strlen($q) > 40 ? mb_substr($q, 0, 40) . '…' : $q) . '" · ' . $baseDesc;

        }



        return [

            'meta_title'       => $title,

            'meta_description' => $desc,

            'meta_canonical'   => $canonical,

        ];

    }

    public function leadClick(string $id): void
    {
        $propertyId = (int)$id;
        $type = trim((string)($_GET['type'] ?? ''));
        $unitId = max(0, (int)($_GET['unit'] ?? 0));

        if ($propertyId <= 0 || !array_key_exists($type, PropertyLeadClick::TYPES)) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $property = Property::findByIdPublic($propertyId);
        if (!$property) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        PropertyLeadClick::record($propertyId, $unitId > 0 ? $unitId : null, $type);

        if ($type === 'phone') {
            $caps = PropertyBookingCapabilities::fromProperty($property);
            $phone = trim((string)($property['phone'] ?? ''));
            if (!$caps['allow_contact'] || $phone === '') {
                http_response_code(404);
                $this->view('errors/404');
                return;
            }
            redirect('tel:' . preg_replace('/\s+/', '', $phone));
        }

        if ($type === 'line') {
            $caps = PropertyBookingCapabilities::fromProperty($property);
            $lineUrl = PropertyBookingCapabilities::lineUrl((string)($property['line_id'] ?? ''));
            if (!$caps['show_line_contact'] || $lineUrl === '') {
                http_response_code(404);
                $this->view('errors/404');
                return;
            }
            redirect($lineUrl);
        }

        if ($type === 'coupon') {
            $params = ['property' => $propertyId];
            if ($unitId > 0) {
                $params['unit'] = $unitId;
            }
            redirect(url('/coupons/buy?' . http_build_query($params)));
        }

        if ($unitId <= 0) {
            $units = Property::units($propertyId);
            $unitId = isset($units[0]['id']) ? (int)$units[0]['id'] : 0;
        }
        if ($unitId <= 0) {
            redirect(url('/property/' . $property['slug']));
            return;
        }
        if ($type === 'map') {
            $lat = floatval($property['latitude'] ?? 0) ?: 14.0228;
            $lng = floatval($property['longitude'] ?? 0) ?: 99.5328;
            redirect('https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $lng);
            return;
        }

        redirect(PropertyBookingCapabilities::bookUrl($propertyId, $unitId, 'book'));
    }

}

