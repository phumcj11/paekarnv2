<?php
/** @var ?array $banner แถวแรกของสล็อต home_desktop_coupon_strip */
$bannerHref = static function (?string $link): string {
    if (!$link) {
        return '';
    }
    return preg_match('#^https?://#i', $link) ? $link : url(ltrim($link, '/'));
};
$b = is_array($banner) ? $banner : [];
$fromDb = !empty($b['id']);
$titleMain = trim((string)($b['title'] ?? ''));
if ($titleMain === '') {
    $titleMain = 'คูปองเงินสด 500 บาท';
}
$titleAccent = trim((string)($b['subtitle'] ?? ''));
if (!$fromDb && $titleAccent === '') {
    $titleAccent = 'จ่ายเพียง 250 บาท';
}
$href = $bannerHref($b['link_url'] ?? null) ?: url('/coupons/buy');
$btnText = trim((string)($b['button_text'] ?? '')) ?: 'ดูรายละเอียดเพิ่มเติม';
$imgSrc = !empty($b['image_path']) ? upload_url($b['image_path']) : '';
?>
<section class="hidden md:block max-w-7xl mx-auto px-6 lg:px-8 mt-2 mb-6">
  <div class="rounded-2xl bg-gradient-to-r from-amber-100 via-orange-50 to-amber-50 border border-amber-200/70 shadow-[0_8px_40px_-20px_rgba(180,83,9,0.25)] px-6 lg:px-10 py-7 lg:py-8 flex flex-wrap items-center justify-between gap-8">
    <div class="flex items-center gap-5 min-w-0 flex-1">
      <?php if ($imgSrc !== ''): ?>
        <img src="<?= e($imgSrc) ?>" alt="" class="w-[3.25rem] h-[3.25rem] lg:w-[3.75rem] lg:h-[3.75rem] object-contain shrink-0 drop-shadow-sm" loading="lazy" decoding="async">
      <?php else: ?>
        <div class="text-[3rem] lg:text-[3.5rem] shrink-0 leading-none drop-shadow-sm" aria-hidden="true">🎁</div>
      <?php endif; ?>
      <div class="min-w-0">
        <p class="text-lg lg:text-xl font-extrabold text-forest-950 leading-snug">
          <?= e($titleMain) ?><?php if ($titleAccent !== ''): ?> <span class="text-forest-800 font-bold whitespace-nowrap"><?= e($titleAccent) ?></span><?php endif; ?>
        </p>
        <?php if ($href !== ''): ?>
          <a href="<?= e($href) ?>" class="mt-3 inline-flex items-center gap-2 bg-white text-forest-900 font-bold px-4 py-2.5 rounded-xl shadow-md ring-1 ring-slate-200/90 hover:bg-amber-50 transition text-sm">
            <?= e($btnText) ?> <i data-lucide="chevron-right" class="w-4 h-4"></i>
          </a>
        <?php endif; ?>
      </div>
    </div>
    <div class="flex flex-wrap gap-8 lg:gap-10 justify-center lg:justify-end w-full lg:w-auto">
      <?php
      $featCoupon = [
          ['zap', 'ใช้ได้ทันที'],
          ['layers', 'ใช้ได้หลายแพ'],
          ['sparkles', 'คุ้มค่าที่สุด'],
      ];
      foreach ($featCoupon as $fc): ?>
      <div class="flex flex-col items-center text-center w-[5.75rem]">
        <span class="w-14 h-14 rounded-full bg-white shadow-md ring-1 ring-amber-200/90 grid place-items-center text-forest-800 mb-2">
          <i data-lucide="<?= e($fc[0]) ?>" class="w-[22px] h-[22px]"></i>
        </span>
        <span class="text-[11px] font-bold text-forest-950 leading-tight"><?= e($fc[1]) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
