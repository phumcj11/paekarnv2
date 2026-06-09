<?php /** @var array<int,array<string,mixed>> $activities */ ?>
<?php if (empty($activities)) { return; } ?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 mt-14">
  <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6 text-center sm:text-left">
    <div>
      <span class="text-xs font-semibold text-accent-600 uppercase tracking-wider">Activities & Services</span>
      <h2 class="text-2xl md:text-3xl font-bold text-ink">กิจกรรมท่องเที่ยวและบริการ</h2>
      <p class="text-sm text-slate-600 mt-1 max-w-xl mx-auto sm:mx-0">จองกิจกรรม รถเช่า รถนำเที่ยว และบริการในกาญจนบุรีผ่านแพกาญ</p>
    </div>
    <a href="<?= url('/activities') ?>" class="inline-flex items-center justify-center gap-1.5 text-sm font-semibold text-primary-700">
      ดูกิจกรรมทั้งหมด <i data-lucide="arrow-right" class="w-4 h-4"></i>
    </a>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <?php foreach ($activities as $activity): ?>
      <?php \App\Core\View::partial('partials/activity-card', ['activity' => $activity]); ?>
    <?php endforeach; ?>
  </div>
</section>
