<?php
$trustHeroImg = \App\Models\Setting::get('trust_hero_image', '');
if (!is_string($trustHeroImg) || trim($trustHeroImg) === '') {
    $trustHeroImg = 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=1600&q=86';
} elseif (!preg_match('#^https?://#i', $trustHeroImg)) {
    $trustHeroImg = function_exists('upload_url') ? (upload_url($trustHeroImg) ?: $trustHeroImg) : $trustHeroImg;
}
$trustTiles = [
    ['icon' => 'message-circle', 'iconBadge' => 'bg-gradient-to-br from-lime-400 to-emerald-600', 'num' => 'text-emerald-600', 'title' => 'รีวิวจริงจากผู้เข้าพัก', 'title_color' => 'text-emerald-600', 'sub' => 'ตรวจสอบได้ มั่นใจได้ว่าไม่มีรีวิวปลอม', 'accent' => 'from-lime-400/18 via-emerald-100/55 to-transparent', 'bar' => 'from-lime-500 to-emerald-500', 'dots' => ['bg-lime-500', 'bg-emerald-500', 'bg-emerald-400']],
    ['icon' => 'tag', 'iconBadge' => 'bg-gradient-to-br from-sky-400 to-blue-600', 'num' => 'text-blue-600', 'title' => 'ราคาดีที่สุด', 'title_color' => 'text-blue-600', 'sub' => 'ค้นหาที่พักจากหลายแห่ง เปรียบเทียบราคาได้ง่าย', 'accent' => 'from-sky-400/16 via-blue-100/50 to-transparent', 'bar' => 'from-sky-500 to-blue-600', 'dots' => ['bg-sky-500', 'bg-blue-500', 'bg-emerald-400']],
    ['icon' => 'shield-check', 'iconBadge' => 'bg-gradient-to-br from-cyan-400 to-teal-600', 'num' => 'text-teal-600', 'title' => 'ปลอดภัย มั่นใจได้', 'title_color' => 'text-teal-600', 'sub' => 'จองผ่านระบบที่โปร่งใส ข้อมูลชัดเจน ติดต่อได้จริง', 'accent' => 'from-cyan-400/16 via-teal-100/50 to-transparent', 'bar' => 'from-cyan-500 to-teal-600', 'dots' => ['bg-cyan-500', 'bg-teal-500', 'bg-sky-400']],
    ['icon' => 'gem', 'iconBadge' => 'bg-gradient-to-br from-fuchsia-400 to-violet-700', 'num' => 'text-violet-600', 'title' => 'คัดสรรคุณภาพ', 'title_color' => 'text-violet-600', 'sub' => 'เลือกเฉพาะที่พักมาตรฐาน ประสบการณ์ดี คุ้มค่าทุกการเดินทาง', 'accent' => 'from-fuchsia-400/14 via-violet-100/50 to-transparent', 'bar' => 'from-violet-500 to-fuchsia-500', 'dots' => ['bg-violet-500', 'bg-purple-500', 'bg-fuchsia-400']],
];
$trustBar = [
    ['icon' => 'shield-check', 'title' => 'ที่พักผ่านการคัดกรอง', 'sub' => 'มาตรฐานและความสะอาด'],
    ['icon' => 'calendar-check', 'title' => 'จองง่าย ได้ทันที', 'sub' => 'ยืนยันไว ไม่ยุ่งยาก'],
    ['icon' => 'headphones', 'title' => 'ทีมงานพร้อมดูแล', 'sub' => 'ช่วยเหลือทุกขั้นตอน'],
    ['icon' => 'lock', 'title' => 'ข้อมูลปลอดภัย', 'sub' => 'มั่นใจในความเป็นส่วนตัว'],
];
?>
<section class="hidden md:block max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-14 sm:mt-16 mb-4">
  <div class="overflow-hidden rounded-[1.65rem] border border-white/80 shadow-[0_22px_54px_-22px_rgba(15,50,30,0.28)]"
       style="background:linear-gradient(128deg,#eaffef 0%,#dffced 30%,#dff5ff 68%,#f5fbff 100%);">
    <div class="flex flex-col lg:flex-row lg:items-stretch">
      <div class="relative order-first min-h-[230px] overflow-hidden rounded-t-[1.65rem] bg-gradient-to-br from-sky-100 via-sky-50 to-emerald-50 lg:order-last lg:min-h-[322px] lg:flex-1 lg:rounded-t-none lg:rounded-tr-[1.65rem]">
        <img src="<?= htmlspecialchars($trustHeroImg, ENT_QUOTES, 'UTF-8') ?>" alt="ที่พักริมน้ำกาญจนบุรี" class="absolute inset-0 h-full w-full object-cover" loading="lazy" decoding="async" width="1600" height="900">
        <div class="absolute inset-0 pointer-events-none bg-gradient-to-b from-white/10 via-transparent to-slate-900/10"></div>
        <div class="pointer-events-none absolute inset-y-0 left-0 hidden w-[46%] bg-gradient-to-r from-white via-white/70 to-transparent lg:block"></div>
        <div class="absolute top-3.5 right-4 lg:top-5 lg:right-6">
          <div class="inline-flex items-center gap-2 rounded-full bg-white/95 backdrop-blur-sm border border-white/90 shadow-lg px-3.5 py-1.5 lg:px-4 lg:py-2">
            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-emerald-600 text-white shadow">
              <i data-lucide="map-pin" class="h-3.5 w-3.5"></i>
            </span>
            <span class="text-[11px] font-semibold text-slate-700 lg:text-[12.5px] whitespace-nowrap">กาญจนบุรี&nbsp;·&nbsp;แพ&nbsp;·&nbsp;พูลวิลล่า&nbsp;·&nbsp;รีสอร์ท</span>
          </div>
        </div>
      </div>
      <div class="relative z-10 flex flex-col justify-center bg-white px-7 py-8 sm:px-11 sm:py-10 lg:px-12 lg:py-11 xl:px-14 lg:w-[54%] lg:shrink-0 rounded-b-[2.75rem] lg:rounded-b-none lg:rounded-tl-[1.65rem] lg:rounded-br-[5.5rem] shadow-[0_4px_24px_-8px_rgba(15,23,42,0.10)] lg:shadow-[6px_0_40px_-16px_rgba(15,23,42,0.14)]">
        <span class="inline-flex w-fit items-center gap-1.5 rounded-full border border-emerald-300 bg-emerald-50 px-3.5 py-1.5 text-[11.5px] font-bold tracking-wide text-emerald-800">
          <i data-lucide="shield-check" class="h-3.5 w-3.5 shrink-0 stroke-[2.5px] text-emerald-600"></i>
          มาตรฐานที่พักกาญจนบุรี
        </span>
        <h3 class="font-heading mt-4 text-[2.65rem] font-extrabold leading-[0.98] tracking-tight text-slate-900 sm:text-[3.25rem] lg:text-[3.35rem] xl:text-[3.6rem]">
          <span class="block text-slate-900">ทำไมต้องเลือก</span>
          <span class="block text-teal-600 font-black">แพกาญ.com</span>
        </h3>
        <div class="mt-5 flex items-center gap-2" aria-hidden="true">
          <span class="h-1 w-12 rounded-full bg-gradient-to-r from-emerald-500 to-teal-400"></span>
          <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
        </div>
        <p class="mt-4 max-w-xl text-[15px] font-medium leading-relaxed text-slate-700">
          รวมแพ รีสอร์ท และบ้านพักที่คัดแล้ว — อ่านรีวิวจริง เปรียบเทียบราคา และจองด้วย<span class="font-extrabold text-emerald-600">ความมั่นใจในที่เดียว</span>
        </p>
      </div>
    </div>
    <div class="grid grid-cols-1 gap-4 px-5 pb-6 pt-6 sm:grid-cols-2 sm:px-7 lg:grid-cols-4 lg:px-8 lg:pb-5 lg:pt-0">
      <?php foreach ($trustTiles as $i => $t): ?>
        <article class="group relative flex min-h-[14.2rem] flex-col overflow-hidden rounded-2xl border border-white/90 bg-white p-5 pb-5 shadow-[0_12px_30px_-14px_rgba(15,50,30,0.22)] transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_18px_42px_-18px_rgba(15,50,30,0.28)]">
          <div class="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-tr <?= e($t['accent']) ?>" aria-hidden="true"></div>
          <div class="absolute right-5 top-4 text-[1.5rem] font-extrabold leading-none tabular-nums <?= e($t['num']) ?>"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></div>
          <div class="relative mb-4">
            <span class="relative inline-grid h-14 w-14 place-items-center rounded-full <?= e($t['iconBadge']) ?> text-white shadow-[0_10px_22px_-10px_rgba(15,23,42,0.35)] ring-4 ring-white transition duration-200 group-hover:scale-105">
              <i data-lucide="<?= e($t['icon']) ?>" class="h-[26px] w-[26px]"></i>
            </span>
          </div>
          <h4 class="relative font-heading pr-8 text-[1.08rem] font-extrabold leading-snug <?= e($t['title_color']) ?>"><?= e($t['title']) ?></h4>
          <p class="relative mt-2 flex-1 text-[13px] font-medium leading-relaxed text-slate-700"><?= e($t['sub']) ?></p>
          <div class="relative mt-5 flex items-center gap-2" aria-hidden="true">
            <span class="h-[3px] w-9 rounded-full bg-gradient-to-r <?= e($t['bar']) ?>"></span>
            <?php foreach ($t['dots'] as $dot): ?><span class="h-1.5 w-1.5 rounded-full <?= e($dot) ?>"></span><?php endforeach; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="relative mx-5 mb-5 overflow-hidden rounded-2xl bg-gradient-to-r from-lime-500 via-teal-500 to-sky-500 shadow-[inset_0_1px_0_rgba(255,255,255,0.24)] sm:mx-7 lg:mx-8">
      <div class="absolute inset-0 bg-gradient-to-b from-white/[0.18] to-transparent pointer-events-none" aria-hidden="true"></div>
      <div class="relative grid grid-cols-1 gap-4 px-5 py-5 sm:grid-cols-2 sm:px-7 lg:grid-cols-4 lg:gap-0 lg:divide-x lg:divide-white/30 lg:px-6 lg:py-5">
        <?php foreach ($trustBar as $row): ?>
          <div class="flex items-center gap-3.5 lg:px-6 lg:first:pl-0 lg:last:pr-0">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-white/95 text-teal-600 shadow-sm">
              <i data-lucide="<?= e($row['icon']) ?>" class="h-5 w-5" stroke-width="2.4"></i>
            </span>
            <div>
              <div class="font-heading text-[14px] font-extrabold text-white leading-snug"><?= e($row['title']) ?></div>
              <div class="mt-0.5 text-[12px] font-medium text-white/90"><?= e($row['sub']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
