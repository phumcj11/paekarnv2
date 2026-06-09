<?php

declare(strict_types=1);

/** @var array<int,array<string,mixed>> $facebookPosts */
/** @var string $facebookAppId */

if ($facebookPosts === []) {
    return;
}

$appIdTrimmed = trim($facebookAppId);
$appIdJson    = json_encode($appIdTrimmed, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<section id="reviews-facebook" class="bg-white border-b border-slate-100 py-14 scroll-mt-28 md:scroll-mt-36">
  <div class="max-w-7xl mx-auto px-4 sm:px-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
      <div>
        <span class="text-xs font-semibold text-blue-700 uppercase tracking-wider">Facebook picks</span>
        <h2 class="text-2xl md:text-3xl font-bold text-ink">โพสต์จากเพจ Facebook</h2>
        <p class="text-sm text-slate-600 mt-1 max-w-xl">คัดโพสต์ท่องเที่ยวหรือรีวิวจากเพจที่เลือก — โหลดจาก Meta เฉพาะหน้านี้</p>
      </div>
    </div>

    <?php if ($appIdTrimmed === ''): ?>
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900 leading-relaxed">
      <strong class="font-semibold">ยังไม่ได้ตั้ง Meta App ID</strong> — เพื่อฝังโพสต์ต้องใส่ App ID ที่
      <a href="<?= e(url('/admin/settings')) ?>" class="underline font-semibold">การตั้งค่าระบบ → Social → Meta App ID</a>
      (สร้างแอปได้ที่ developers.facebook.com)
    </div>
    <?php else: ?>
    <div id="fb-root"></div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
      <?php foreach ($facebookPosts as $post): ?>
        <?php
        $href = (string)($post['post_url'] ?? '');
        if ($href === '') {
            continue;
        }
        ?>
      <div class="rounded-2xl border border-slate-200 bg-cloud overflow-hidden shadow-soft">
        <?php if (trim((string)($post['title'] ?? '')) !== ''): ?>
        <div class="px-4 py-3 border-b border-slate-100 bg-white">
          <h3 class="font-semibold text-slate-900"><?= e((string)$post['title']) ?></h3>
          <?php $desc = trim((string)($post['description'] ?? '')); ?>
          <?php if ($desc !== ''): ?>
          <p class="text-xs text-slate-600 mt-1 line-clamp-2"><?= e($desc) ?></p>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="flex justify-center p-4 bg-slate-50/80 min-h-[220px]">
          <div class="fb-post w-full max-w-[560px]" data-href="<?= e($href) ?>" data-width="550" data-show-text="true"></div>
        </div>
        <div class="px-4 py-2 text-center border-t border-slate-100 bg-white">
          <a href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-blue-700 hover:underline inline-flex items-center gap-1">
            เปิดบน Facebook <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <script>
      window.fbAsyncInit = function() {
        FB.init({
          appId: <?= $appIdJson ?>,
          xfbml: true,
          version: 'v21.0'
        });
      };
    </script>
    <script async defer crossorigin="anonymous" src="https://connect.facebook.net/th_TH/sdk.js"></script>
    <?php endif; ?>
  </div>
</section>
