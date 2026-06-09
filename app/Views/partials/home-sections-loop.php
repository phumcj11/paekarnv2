<?php
/** @var array<int,array<string,mixed>> $homeSectionPlan @var array<string,array<int,array<string,mixed>>> $bannersBySlot */
$homeSectionPlan = $homeSectionPlan ?? [];
$bannersBySlot = $bannersBySlot ?? [];

$slotAnchorId = static function (string $slot): string {
    return 'banner-slot-' . str_replace('_', '-', $slot);
};

foreach ($homeSectionPlan as $section):
    $bannersBefore = $section['banners_before'] ?? [];
    if ($bannersBefore !== []) {
        $bannerItems = [];
        foreach ($bannersBefore as $slot) {
            echo '<div id="' . e($slotAnchorId($slot)) . '" class="scroll-mt-28 md:scroll-mt-36" aria-hidden="true"></div>';
            $bannerItems = array_merge($bannerItems, $bannersBySlot[$slot] ?? []);
        }
        $bannerOpts = ['items' => $bannerItems];
        if (($section['id'] ?? '') === 'trust') {
            $bannerOpts['sectionClass'] = 'max-w-7xl mx-auto px-4 sm:px-6 mt-10 md:mt-11';
            $bannerOpts['gridClass'] = 'grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5';
        }
        \App\Core\View::partial('partials/home-banner-slot', $bannerOpts);
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
            \App\Core\View::partial('partials/home-section-newest-raft', $payload);
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
endforeach;
