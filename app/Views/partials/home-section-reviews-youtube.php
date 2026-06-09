<?php

/** @var array<int,array<string,mixed>> $reviewVideos */

use App\Models\ReviewVideo;

if (empty($reviewVideos)) {
    return;
}

$videoGroups     = ReviewVideo::partitionByOrientation($reviewVideos);
$landscapeVideos = $videoGroups['landscape'];
$portraitVideos  = $videoGroups['portrait'];

if ($landscapeVideos !== []) {
    \App\Core\View::partial('partials/review_videos_grid', [
        'videos'       => $landscapeVideos,
        'sectionId'    => 'reviews-youtube',
        'eyebrow'      => 'YouTube',
        'title'        => 'วิดีโอรีวิว',
        'subtitle'     => 'คลิปรีวิวแพ ที่พัก และที่เที่ยวกาญจนบุรี — คัดโดยทีมแพกาญ',
        'moreUrl'      => url('/videos'),
        'sectionClass' => 'bg-cloud border-y border-slate-100 mt-14 py-14',
        'with_anchor'  => false,
    ]);
}

if ($portraitVideos !== []) {
    \App\Core\View::partial('partials/review_videos_carousel', [
        'videos'       => $portraitVideos,
        'sectionId'    => 'reviews-shorts',
        'eyebrow'      => 'Short clips',
        'title'        => 'คลิปรีวิวแนวตั้ง',
        'subtitle'     => 'YouTube Shorts · TikTok · Reels — คัดโดยทีมแพกาญ',
        'moreUrl'      => url('/videos'),
        'sectionClass' => 'bg-white border-y border-slate-100 mt-14 py-14',
        'with_anchor'  => false,
    ]);
}

?>
