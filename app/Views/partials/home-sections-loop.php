<?php
/** @var array<int,array<string,mixed>> $homeSectionPlan @var array<string,array<string,mixed>|null> $homeBanners @var array<string,array<int,array<string,mixed>>> $bannersBySlot */
$homeSectionPlan = $homeSectionPlan ?? [];
$homeBanners = $homeBanners ?? [];
$bannersBySlot = $bannersBySlot ?? [];

$renderBannerSlot = static function (string $slot, bool $eager = false) use ($homeBanners): void {
    $banner = $homeBanners[$slot] ?? null;
    if ($banner === null) {
        return;
    }
    \App\Core\View::partial('partials/banners/banner-slot', [
        'slot' => $slot,
        'banner' => $banner,
        'eagerLoad' => $eager,
    ]);
};

$renderLegacyBannerGrid = static function (array $slots) use ($bannersBySlot): void {
    $items = [];
    foreach ($slots as $slot) {
        foreach ($bannersBySlot[$slot] ?? [] as $row) {
            $items[] = $row;
        }
    }
    if ($items === []) {
        return;
    }
    \App\Core\View::partial('partials/home-banner-slot', ['items' => $items]);
};

foreach ($homeSectionPlan as $section):
    $bannersBefore = $section['banners_before'] ?? [];
    foreach ($bannersBefore as $slot) {
        if (\App\Models\Banner::isBSlot($slot)) {
            $renderBannerSlot($slot);
        } else {
            $renderLegacyBannerGrid([$slot]);
        }
    }

    $type = (string) ($section['type'] ?? '');
    $payload = $section['payload'] ?? [];

    switch ($type) {
        case 'featured':
            \App\Core\View::partial('partials/home-featured-type-section', $payload);
            break;

        case 'trust':
            \App\Core\View::partial('partials/home-section-trust');
            break;

        case 'coupon-mobile':
            \App\Core\View::partial('partials/home-section-coupon-mobile');
            break;

        case 'zones-popular':
            \App\Core\View::partial('partials/home-section-zones-popular', $payload);
            break;

        case 'newest-raft':
            \App\Core\View::partial('partials/home-section-newest-raft', array_merge($payload, [
                'sponsoredBanner' => $homeBanners['home_b06'] ?? null,
            ]));
            break;

        case 'zone-raft':
            \App\Core\View::partial('partials/home-section-zone-raft', $payload);
            break;

        case 'activities':
            \App\Core\View::partial('partials/home-section-activities', $payload);
            break;

        case 'reviews-youtube':
            \App\Core\View::partial('partials/home-section-reviews-youtube', $payload);
            break;

        case 'reviews-guest':
            \App\Core\View::partial('partials/guest_review_cards_section', [
                'reviews'             => $payload['reviews'] ?? [],
                'section_extra_class' => 'mt-14',
            ]);
            break;

        case 'blog':
            \App\Core\View::partial('partials/home-section-blog', $payload);
            break;

        case 'cta-bottom':
            \App\Core\View::partial('partials/home-section-cta-bottom');
            break;
    }

    $bannersAfter = $section['banners_after'] ?? [];
    foreach ($bannersAfter as $slot) {
        $renderBannerSlot($slot);
    }
endforeach;
