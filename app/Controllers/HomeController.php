<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\PageCache;
use App\Models\Property;
use App\Models\ActivityProduct;
use App\Models\Review;
use App\Models\BlogPost;
use App\Models\Banner;
use App\Models\Setting;
use App\Models\ReviewVideo;
use App\Services\ZoneAdService;
use App\Support\HomepageSections;

class HomeController extends Controller
{
    public function index(): void
    {
        $cacheKey = 'home_index_v2';
        $cached   = PageCache::get($cacheKey);

        if ($cached !== null) {
            $this->view('home/index', $cached);
            return;
        }

        $boostCoupon = HomepageSections::sortBoostCoupon();
        $featuredLabels = HomepageSections::featuredLabels();

        $preparePropertyRows = static function (array $rows) use ($boostCoupon): array {
            $rows = Property::expandListingRowsForDisplay($rows);
            $rows = Property::sortHomepageListingRows($rows, $boostCoupon);
            $rows = Property::attachGalleryThumbnails($rows);

            return Property::attachUnitStats($rows);
        };

        $prepareHomepageCards = static function (array $rows): array {
            $rows = Property::attachGalleryThumbnails($rows);

            return Property::attachUnitStats($rows);
        };

        $featuredLimit = static fn (string $key, int $cap = 4): int => min($cap, max(1, (int)($featuredLabels[$key]['limit'] ?? $cap)));
        $featuredByKey = [
            'raft'   => $prepareHomepageCards(Property::featuredByType('raft', $featuredLimit('raft'), $boostCoupon)),
            'resort' => $prepareHomepageCards(Property::featuredByType('resort', $featuredLimit('resort'), $boostCoupon)),
            'hotel'  => $prepareHomepageCards(Property::featuredByType('hotel', $featuredLimit('hotel'), $boostCoupon)),
            'stay'   => $prepareHomepageCards(Property::featuredByType(['homestay', 'house'], $featuredLimit('stay'), $boostCoupon)),
        ];

        $newestRafts = array_slice(
            $preparePropertyRows(Property::newestByType('raft', 8)),
            0,
            4
        );

        $reviews = Review::latest(6);
        $reviewVideos = ReviewVideo::activeOrdered(4);
        $activities = ActivityProduct::featured(4);
        $blogs = BlogPost::published(3);
        $zones = Property::attachZoneCoverImages(Property::distinctZones());
        $amenities = Database::fetchAll('SELECT * FROM amenities ORDER BY sort_order, name');
        $stats = [
            'properties' => (int) Database::fetch("SELECT COUNT(*) c FROM properties WHERE status='published'")['c'],
            'reviews'    => (int) Database::fetch("SELECT COUNT(*) c FROM reviews WHERE is_approved=1")['c'],
            'coupons'    => (int) Database::fetch("SELECT COUNT(*) c FROM coupons")['c'],
        ];

        $bannersBySlot = Banner::groupedForHome();
        $heroSlides = [];
        foreach (array_slice($bannersBySlot['hero'] ?? [], 0, 3) as $b) {
            $heroSlides[] = [
                'img'      => upload_img((string) ($b['image_path'] ?? ''), 'md'),
                'title'    => $b['title'],
                'subtitle' => (string) ($b['subtitle'] ?? ''),
                'link'     => self::bannerResolvedUrl($b['link_url'] ?? null),
                'btn'      => (string) ($b['button_text'] ?? ''),
            ];
        }
        if (empty($heroSlides)) {
            $heroSlides[] = [
                'img'      => 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?w=900&q=70',
                'title'    => __('hero_title'),
                'subtitle' => (string) __('hero_subtitle'),
                'link'     => url('/rafts'),
                'btn'      => 'ค้นหาที่พัก',
            ];
        }

        $zoneRaftPayload = [];
        foreach (Property::homeZoneSectionDefinitions() as $def) {
            $rows = array_slice(
                $preparePropertyRows(Property::publishedInZonesByType($def['zones'], 'raft', 4)),
                0,
                4
            );
            $primaryZone = $def['zones'][0] ?? '';
            $zoneAds = [];
            if ($primaryZone !== '') {
                foreach (ZoneAdService::activeForZone($primaryZone, 4) as $ad) {
                    $zoneAds[] = [
                        'title'         => $ad['title'] ?? '',
                        'image_url'     => !empty($ad['image_path']) ? upload_img((string) $ad['image_path'], 'md') : '',
                        'link_resolved' => self::bannerResolvedUrl(isset($ad['link_url']) ? (string) $ad['link_url'] : null),
                    ];
                }
            }
            if ($rows === [] && $zoneAds === []) {
                continue;
            }
            $sectionId = HomepageSections::zoneSectionId($def['id']);
            $zoneRaftPayload[$sectionId] = [
                'id'         => $def['id'],
                'title'      => $def['title'],
                'properties' => $rows,
                'zone_ads'   => $zoneAds,
                'more_url'   => url('/rafts?' . http_build_query(['zone' => $primaryZone])),
            ];
        }

        $sectionPayload = [
            'featured-raft' => [
                'type'    => 'featured',
                'visible' => $featuredLabels['raft']['enabled'] && $featuredByKey['raft'] !== [],
                'payload' => [
                    'properties'   => $featuredByKey['raft'],
                    'title'        => $featuredLabels['raft']['title'],
                    'eyebrow'      => $featuredLabels['raft']['eyebrow'],
                    'moreUrl'      => url($featuredLabels['raft']['more_path']),
                    'sectionClass' => 'home-featured-accom max-w-7xl mx-auto px-4 sm:px-6 mt-14',
                ],
            ],
            'featured-resort' => [
                'type'    => 'featured',
                'visible' => $featuredLabels['resort']['enabled'] && $featuredByKey['resort'] !== [],
                'payload' => [
                    'properties' => $featuredByKey['resort'],
                    'title'      => $featuredLabels['resort']['title'],
                    'eyebrow'    => $featuredLabels['resort']['eyebrow'],
                    'moreUrl'    => url($featuredLabels['resort']['more_path']),
                ],
            ],
            'featured-hotel' => [
                'type'    => 'featured',
                'visible' => $featuredLabels['hotel']['enabled'] && $featuredByKey['hotel'] !== [],
                'payload' => [
                    'properties' => $featuredByKey['hotel'],
                    'title'      => $featuredLabels['hotel']['title'],
                    'eyebrow'    => $featuredLabels['hotel']['eyebrow'],
                    'moreUrl'    => url($featuredLabels['hotel']['more_path']),
                ],
            ],
            'featured-stay' => [
                'type'    => 'featured',
                'visible' => $featuredLabels['stay']['enabled'] && $featuredByKey['stay'] !== [],
                'payload' => [
                    'properties' => $featuredByKey['stay'],
                    'title'      => $featuredLabels['stay']['title'],
                    'eyebrow'    => $featuredLabels['stay']['eyebrow'],
                    'moreUrl'    => url($featuredLabels['stay']['more_path']),
                ],
            ],
            'trust' => [
                'type'    => 'trust',
                'visible' => true,
                'payload' => [],
            ],
            'coupon-mobile' => [
                'type'    => 'coupon-mobile',
                'visible' => true,
                'payload' => [],
            ],
            'zones-popular' => [
                'type'    => 'zones-popular',
                'visible' => $zones !== [],
                'payload' => ['zones' => $zones],
            ],
            'newest-raft' => [
                'type'    => 'newest-raft',
                'visible' => $newestRafts !== [],
                'payload' => ['properties' => $newestRafts],
            ],
            'activities' => [
                'type'    => 'activities',
                'visible' => $activities !== [],
                'payload' => ['activities' => $activities],
            ],
            'reviews-youtube' => [
                'type'    => 'reviews-youtube',
                'visible' => $reviewVideos !== [],
                'payload' => ['reviewVideos' => $reviewVideos],
            ],
            'reviews-guest' => [
                'type'    => 'reviews-guest',
                'visible' => $reviews !== [],
                'payload' => ['reviews' => $reviews],
            ],
            'blog' => [
                'type'    => 'blog',
                'visible' => $blogs !== [],
                'payload' => ['blogs' => $blogs],
            ],
            'cta-bottom' => [
                'type'    => 'cta-bottom',
                'visible' => true,
                'payload' => [],
            ],
        ];

        foreach ($zoneRaftPayload as $sectionId => $payload) {
            $sectionPayload[$sectionId] = [
                'type'    => 'zone-raft',
                'visible' => true,
                'payload' => $payload,
            ];
        }

        $homeSectionPlan = HomepageSections::buildRenderPlan($sectionPayload);

        $homeTitle = trim((string) Setting::get('seo_home_title', ''));
        $homeDesc = trim((string) Setting::get('seo_home_description', ''));

        $viewData = [
            'meta_title'        => $homeTitle !== '' ? $homeTitle : 'แพกาญ.com — ที่พักกาญจนบุรีตรวจสอบจริง รีวิวจริง คูปองสมาชิก',
            'meta_description'  => $homeDesc !== '' ? $homeDesc : 'จองที่พักกาญจนบุรีที่ตรวจสอบแล้ว แพ รีสอร์ท โรงแรม พูลวิลล่า โฮมสเตย์ รีวิวจริง ใช้คูปองเงินสดลดค่าที่พักได้จริง',
            'meta_canonical'    => url('/'),
            'preload_lcp_image' => !empty($bannersBySlot['hero'][0]['image_path'])
                ? upload_img((string) $bannersBySlot['hero'][0]['image_path'], 'md')
                : ($heroSlides[0]['img'] ?? ''),
            'reviews'           => $reviews,
            'reviewVideos'      => $reviewVideos,
            'activities'        => $activities,
            'blogs'             => $blogs,
            'zones'             => $zones,
            'amenities'         => $amenities,
            'stats'             => $stats,
            'heroSlides'        => $heroSlides,
            'bannersBySlot'     => $bannersBySlot,
            'homeSectionPlan'   => $homeSectionPlan,
            'heroCopy'          => self::homeHeroCopy(),
        ];
        PageCache::set($cacheKey, $viewData, 600);
        $this->view('home/index', $viewData);
    }

    /** ข้อความ Banner Hero — ค่าว่างใน settings ใช้ข้อความเริ่มต้น */
    private static function homeHeroCopy(): array
    {
        $pick = static function (string $key, string $fallback): string {
            $v = trim((string) Setting::get($key, ''));

            return $v !== '' ? $v : $fallback;
        };

        return [
            'desktop_title_line1' => $pick('home_hero_desktop_title_line1', 'จองที่พักกาญจนบุรี'),
            'desktop_title_line2' => $pick('home_hero_desktop_title_line2', 'ได้ส่วนลดทันที'),
            'mobile_title_line1'  => $pick('home_hero_mobile_title_line1', 'ที่พักกาญจนบุรี'),
            'mobile_title_line2'  => $pick('home_hero_mobile_title_line2', 'แพ รีสอร์ท โรงแรม — จองง่าย'),
            'promo'               => $pick('home_hero_promo_line', 'ซื้อคูปอง 250.- ลดทันที 500.-'),
            'bullet_1'            => $pick('home_hero_bullet_1', 'คัดสรรคุณภาพ — เฉพาะที่พักที่ได้มาตรฐาน'),
            'bullet_2'            => $pick('home_hero_bullet_2', 'รีวิวจริง 100% จากผู้เข้าพัก'),
            'bullet_3'            => $pick('home_hero_bullet_3', 'ปลอดภัย มั่นใจได้ — จองผ่านระบบที่ตรวจสอบได้'),
            'bullet_4'            => $pick('home_hero_bullet_4', 'ใช้คูปองได้กับที่พักที่ร่วมรายการ'),
        ];
    }

    private static function bannerResolvedUrl(?string $link): string
    {
        if (!$link) {
            return '';
        }
        if (preg_match('#^https?://#i', $link)) {
            return $link;
        }

        return url(ltrim($link, '/'));
    }
}
