<?php /** @var array<int,array<string,mixed>> $zones */ ?>
<?php if (empty($zones)) { return; } ?>
<section id="zones-home" class="max-w-7xl mx-auto px-4 sm:px-6 mt-14 scroll-mt-28 md:scroll-mt-36">
  <div class="mb-5 flex flex-col gap-1 md:flex-row md:items-end md:justify-between">
    <div>
      <span class="text-xs font-bold text-forest-700 uppercase tracking-wider">ท่องเที่ยวกาญจน์</span>
      <h2 class="text-2xl md:text-3xl font-extrabold text-ink mt-1">จุดหมายยอดนิยม</h2>
    </div>
    <a href="<?= url('/properties') ?>" class="hidden md:inline-flex items-center gap-1 text-sm font-bold text-forest-800 hover:text-forest-600 mt-2 md:mt-0">
      ดูทั้งหมด <i data-lucide="chevron-right" class="w-4 h-4"></i>
    </a>
  </div>
  <div class="grid grid-cols-2 gap-3 pb-2 md:flex md:flex-nowrap md:gap-4 md:overflow-x-auto no-scrollbar md:py-1">
    <?php
    $emojis = ['เขื่อนศรีนครินทร์'=>'🏞️','สังขละบุรี'=>'🌅','ริมแม่น้ำแคว'=>'🌊','แม่น้ำแคว'=>'🌊','แควใหญ่'=>'🌊','ริมแม่น้ำแควน้อย'=>'🛶','แควน้อย'=>'🛶','ทองผาภูมิ'=>'⛰️','อุทยานไทรโยค'=>'🌲','น้ำตกไทรโยคน้อย'=>'💦','อุทยานเอราวัณ'=>'🏔️'];
    foreach ($zones as $z):
      $emoji = $emojis[$z['zone']] ?? '📍';
      $destUrl = !empty($z['destination_image']) ? upload_url((string)$z['destination_image']) : null;
    ?>
    <a href="<?= url('/properties?zone=' . urlencode($z['zone'])) ?>"
       class="min-w-0 w-full px-4 py-3 md:w-[7.25rem] md:shrink-0 md:flex-col md:items-stretch md:px-0 md:py-0 md:gap-2 md:bg-transparent md:border-0 md:hover:bg-transparent bg-white border border-slate-200 hover:border-forest-300 hover:bg-forest-50/50 rounded-2xl flex md:flex-col items-center gap-3 md:gap-2 transition group">
      <?php if ($destUrl): ?>
      <span class="relative shrink-0 w-[3.75rem] h-[3.75rem] sm:w-16 sm:h-16 md:w-full md:aspect-square rounded-xl md:rounded-2xl overflow-hidden bg-slate-100 ring-1 ring-slate-200/90 md:ring-forest-100/80 shadow-sm md:shadow-inner">
        <img src="<?= e($destUrl) ?>" alt="" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy">
      </span>
      <?php else: ?>
      <span class="text-2xl shrink-0 w-[3.75rem] h-[3.75rem] sm:w-16 sm:h-16 flex md:w-full md:aspect-square items-center justify-center rounded-xl md:rounded-2xl md:bg-gradient-to-br md:from-forest-100 md:to-emerald-100 md:text-[2rem] md:shadow-inner md:ring-1 md:ring-forest-100/80 md:group-hover:from-forest-200 md:transition"><?= $emoji ?></span>
      <?php endif; ?>
      <span class="font-semibold text-slate-700 md:text-center md:text-[13px] md:leading-tight md:font-bold md:text-forest-900"><?= e($z['zone']) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</section>
