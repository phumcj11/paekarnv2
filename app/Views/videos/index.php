<?php
/** @var array<int,array<string,mixed>> $rows */
/** @var int $page */
/** @var int $totalPages */

use App\Models\ReviewVideo;

$videoGroups     = ReviewVideo::partitionByOrientation($rows ?? []);
$landscapeVideos = $videoGroups['landscape'];
$portraitVideos  = $videoGroups['portrait'];
?>
<section class="bg-primary-700 text-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
    <h1 class="text-2xl md:text-3xl font-bold flex items-center gap-2"><i data-lucide="video" class="w-7 h-7"></i> วิดีโอและคลิปรีวิว</h1>
    <p class="text-white/85 mt-1 max-w-2xl">วิดีโอรีวิว YouTube แนวนอน 16:9 และคลิปแนวตั้ง 9:16 จาก Shorts · TikTok · Instagram Reels</p>
  </div>
</section>

<?php if (empty($rows)): ?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-16 text-center text-slate-500">
  ยังไม่มีวิดีโอในขณะนี้ — กลับมาดูใหม่เร็วๆ นี้
</section>
<?php else: ?>
<?php if ($landscapeVideos !== []): ?>
<?php \App\Core\View::partial('partials/review_videos_grid', [
    'videos'       => $landscapeVideos,
    'sectionId'    => 'videos-landscape',
    'eyebrow'      => 'YouTube',
    'title'        => 'วิดีโอรีวิว',
    'subtitle'     => 'คลิปแนวนอน 16:9 — กดเล่นเมื่อพร้อม',
    'moreUrl'      => null,
    'sectionClass' => 'py-10 bg-cloud border-b border-slate-100',
    'with_anchor'  => true,
]); ?>
<?php endif; ?>
<?php if ($portraitVideos !== []): ?>
<?php \App\Core\View::partial('partials/review_videos_carousel', [
    'videos'       => $portraitVideos,
    'sectionId'    => 'videos-shorts',
    'eyebrow'      => 'Short clips',
    'title'        => 'คลิปรีวิวแนวตั้ง',
    'subtitle'     => 'คลิปแนวตั้ง 9:16 — เลื่อนดู Shorts · TikTok · Reels',
    'moreUrl'      => null,
    'sectionClass' => 'py-10',
    'with_anchor'  => true,
]); ?>
<?php endif; ?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
  <?php \App\Core\View::partial('partials/pagination', ['page' => $page, 'totalPages' => $totalPages, 'baseUrl' => url('/videos'), 'query' => []]); ?>
</section>
<?php endif; ?>
