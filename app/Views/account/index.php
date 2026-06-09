<?php /** @var array $user @var array $stats @var array $recent */ ?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-8 grid grid-cols-1 lg:grid-cols-12 gap-6">
  <?php \App\Core\View::partial('partials/account-nav'); ?>

  <div class="lg:col-span-9 space-y-6">
    <div class="bg-gradient-to-r from-primary-700 to-primary-500 text-white rounded-2xl p-6">
      <h1 class="text-2xl font-bold">สวัสดี, <?= e($user['name']) ?> 👋</h1>
      <p class="text-white/85 text-sm mt-1">ยินดีต้อนรับกลับสู่แพกาญ.com</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <?php
      $cards = [
        ['icon'=>'calendar-check','color'=>'bg-blue-50 text-blue-600','label'=>'การจองทั้งหมด','val'=>$stats['bookings']],
        ['icon'=>'ticket',        'color'=>'bg-rose-50 text-rose-600','label'=>'คูปองพร้อมใช้','val'=>$stats['coupons']],
        ['icon'=>'heart',         'color'=>'bg-pink-50 text-pink-600','label'=>'บันทึกที่พัก','val'=>$stats['favorites']],
      ];
      foreach ($cards as $c): ?>
      <div class="bg-white border border-slate-200 rounded-2xl p-5">
        <div class="w-11 h-11 rounded-xl <?= $c['color'] ?> grid place-items-center"><i data-lucide="<?= $c['icon'] ?>" class="w-5 h-5"></i></div>
        <div class="mt-3 text-3xl font-extrabold text-ink"><?= number_format($c['val']) ?></div>
        <div class="text-sm text-slate-500"><?= e($c['label']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-5">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="clock" class="w-5 h-5 text-accent-600"></i> การจองล่าสุด</h2>
        <a href="<?= url('/account/bookings') ?>" class="text-sm text-primary-700 hover:text-accent-600 font-semibold">ดูทั้งหมด →</a>
      </div>
      <?php if (empty($recent)): ?>
        <div class="text-center text-slate-500 py-8 text-sm">ยังไม่มีการจอง <a href="<?= url('/properties') ?>" class="text-primary-700 underline">ค้นหาที่พักเลย</a></div>
      <?php else: ?>
      <div class="divide-y divide-slate-100">
        <?php foreach ($recent as $b): ?>
        <a href="<?= url('/property/' . $b['property_slug']) ?>" class="flex items-center gap-3 py-3 hover:bg-slate-50 -mx-2 px-2 rounded-lg">
          <img src="<?= e(upload_url($b['cover_image'])) ?>" class="w-14 h-14 rounded-lg object-cover">
          <div class="flex-1">
            <div class="font-semibold text-sm"><?= e($b['property_name']) ?></div>
            <div class="text-xs text-slate-500"><?= format_date_th($b['check_in']) ?> – <?= format_date_th($b['check_out']) ?></div>
          </div>
          <div class="text-right">
            <div class="font-bold text-primary-700"><?= format_money($b['total_price']) ?></div>
            <div class="text-xs text-slate-500"><?= e($b['code']) ?></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>
