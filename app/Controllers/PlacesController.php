<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\ActivityProduct;
use App\Models\Banner;
use App\Models\Property;
use App\Models\VisitorPlace;

class PlacesController extends Controller
{
    public function index(): void
    {
        $perPage = 12;
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        $category = isset($_GET['category']) ? trim((string)$_GET['category']) : '';
        $category = $category === '' ? null : $category;
        $zone     = isset($_GET['zone']) ? trim((string)$_GET['zone']) : '';
        $zone     = $zone === '' ? null : $zone;
        $district = isset($_GET['district']) ? trim((string)$_GET['district']) : '';
        $district = $district === '' ? null : $district;
        $q         = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $q         = $q === '' ? null : (function_exists('mb_substr') ? mb_substr($q, 0, 80) : substr($q, 0, 80));
        $tag       = isset($_GET['tag']) ? trim((string)$_GET['tag']) : '';
        $tag       = preg_match('/^[a-z0-9_]+$/', $tag) ? $tag : '';
        $tag       = $tag === '' ? null : $tag;
        $openNow   = !empty($_GET['open_now']);

        // Geolocation params — validate within Thailand bounding box
        $lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
        $lng = isset($_GET['lng']) ? (float)$_GET['lng'] : null;
        if ($lat !== null && $lng !== null) {
            if ($lat < 5.5 || $lat > 20.5 || $lng < 97.5 || $lng > 105.7) {
                $lat = null;
                $lng = null;
            }
        }
        $hasGeo = $lat !== null && $lng !== null;

        $sort = isset($_GET['sort']) ? trim((string)$_GET['sort']) : '';
        $sort = in_array($sort, ['latest', 'nearest'], true) ? $sort : null;
        if ($hasGeo && $sort === null) {
            $sort = 'nearest';
        }

        if ($hasGeo) {
            $total = VisitorPlace::publishedCountGeo($lat, $lng, $category, $zone, $district, $q, $tag, $openNow);
            $rows  = VisitorPlace::publishedPageGeo($lat, $lng, $category, $zone, $district, $perPage, $offset, $sort, $q, $tag, $openNow);
        } else {
            $total = VisitorPlace::publishedCount($category, $zone, $district, $q, $tag, $openNow);
            $rows  = VisitorPlace::publishedPage($category, $zone, $district, $perPage, $offset, $sort, $q, $tag, $openNow);
        }
        $pages = max(1, (int) ceil($total / $perPage));

        $query = array_filter([
            'category' => $category,
            'zone'     => $zone,
            'district' => $district,
            'lat'      => $hasGeo ? (string)$lat : null,
            'lng'      => $hasGeo ? (string)$lng : null,
            'sort'     => $sort,
            'q'        => $q,
            'tag'      => $tag,
            'open_now' => $openNow ? '1' : null,
        ], static fn ($v) => $v !== null && $v !== '');
        $isCafe = $category === 'cafe';

        $this->view('places/index', [
            'meta_title'       => $isCafe ? 'คาเฟ่ใกล้ฉัน กาญจนบุรี — แพกาญ.com' : 'ที่เที่ยวกาญจนบุรี — คู่มือทริป | แพกาญ.com',
            'meta_description' => $isCafe ? 'ค้นหาคาเฟ่กาญจนบุรีใกล้คุณ พร้อมระยะทาง โซน เวลาเปิดปิด และจุดถ่ายรูป' : 'ค้นหาที่เที่ยว คาเฟ่ และจุดเช็คอินในกาญจนบุรีตามอำเภอและโซนที่พัก',
            'meta_canonical'   => $page > 1 ? url('/places?' . http_build_query(array_merge($query, ['page' => $page]))) : url('/places' . ($query ? '?' . http_build_query($query) : '')),
            'placeBanners'     => Banner::placesPageContent(),
            'preload_lcp_image' => Banner::placesPageContent()['places_hero']['image'] ?? '',
            'rows'             => $rows,
            'page'             => $page,
            'totalPages'       => $pages,
            'categories'       => VisitorPlace::CATEGORIES,
            'zoneChoices'      => Property::zonesForSelect(),
            'districtChoices'  => VisitorPlace::DISTRICTS,
            'filterCategory'   => $category,
            'filterZone'       => $zone,
            'filterDistrict'   => $district,
            'filterLat'        => $lat,
            'filterLng'        => $lng,
            'filterSort'       => $sort,
            'filterQ'          => $q,
            'filterTag'        => $tag,
            'filterOpenNow'    => $openNow,
            'filterQuery'      => $query,
        ]);
    }

    public function show(string $slug): void
    {
        $place = VisitorPlace::findPublishedBySlug($slug);
        if (!$place) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $zone = trim((string)($place['zone'] ?? ''));
        if ($zone !== '') {
            $rawNearby = Database::fetchAll(
                "SELECT * FROM properties WHERE status='published' AND zone = :z
                 ORDER BY is_featured DESC, rating_avg DESC, rating_count DESC LIMIT 8",
                ['z' => $zone]
            );
        } else {
            $rawNearby = Database::fetchAll(
                "SELECT * FROM properties WHERE status='published'
                 ORDER BY is_featured DESC, rating_avg DESC, rating_count DESC LIMIT 8"
            );
        }
        $nearby = Property::attachUnitStats(Property::attachGalleryThumbnails($rawNearby));
        $relatedActivities = ActivityProduct::relatedToPlace($place, 4);

        $metaTitle = trim((string)($place['meta_title'] ?? ''));
        if ($metaTitle === '') {
            $metaTitle = $place['name'] . ' — ที่เที่ยวกาญจนบุรี | แพกาญ.com';
        }
        $metaDesc = trim((string)($place['meta_description'] ?? ''));
        if ($metaDesc === '') {
            $metaDesc = trim((string)($place['excerpt'] ?? ''));
        }
        if ($metaDesc === '') {
            $metaDesc = $place['name'] . ' · แพกาญ.com';
        }

        $og = VisitorPlace::coverImageUrl($place);

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'TouristAttraction',
            'name'     => $place['name'],
            'description' => $metaDesc,
            'url'      => url('/places/' . rawurlencode($place['slug'])),
        ];
        if ($og !== '') {
            $schema['image'] = $og;
        }
        if ($place['latitude'] !== null && $place['longitude'] !== null
            && $place['latitude'] !== '' && $place['longitude'] !== '') {
            $schema['geo'] = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => (float)$place['latitude'],
                'longitude' => (float)$place['longitude'],
            ];
        }

        $this->view('places/show', [
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDesc,
            'meta_canonical'   => url('/places/' . $place['slug']),
            'meta_og_image'    => $og,
            'schema_org_json'  => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'place'            => $place,
            'nearbyProperties' => $nearby,
            'relatedActivities'=> $relatedActivities,
            'categories'       => VisitorPlace::CATEGORIES,
        ]);
    }
}
