<?php /** @var array $rows */ ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 grid grid-cols-1 lg:grid-cols-4 gap-6">
  <?php \App\Core\View::partial('partials/account-nav'); ?>
  <div class="lg:col-span-3">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
      <div class="p-5 border-b border-slate-100">
        <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="bell" class="w-5 h-5 text-accent-600"></i> การแจ้งเตือนทั้งหมด</h2>
        <p class="text-sm text-slate-500 mt-1">รวม <?= count($rows) ?> รายการ</p>
      </div>
      <div class="divide-y divide-slate-100">
      <?php if (empty($rows)): ?>
        <div class="p-12 text-center text-slate-500">
          <i data-lucide="inbox" class="w-12 h-12 mx-auto text-slate-300"></i>
          <p class="mt-3">ยังไม่มีการแจ้งเตือน</p>
        </div>
      <?php else: foreach ($rows as $n): ?>
        <a href="<?= e($n['link'] ? url($n['link']) : '#') ?>" class="flex items-start gap-3 p-4 hover:bg-slate-50 <?= !$n['read_at'] ? 'bg-accent-50/40' : '' ?>">
          <div class="w-10 h-10 rounded-xl bg-accent-100 text-accent-700 grid place-items-center"><i data-lucide="bell" class="w-5 h-5"></i></div>
          <div class="flex-1 min-w-0">
            <div class="font-semibold text-sm"><?= e($n['title']) ?></div>
            <div class="text-sm text-slate-600 mt-0.5"><?= e($n['message']) ?></div>
            <div class="text-xs text-slate-400 mt-1"><?= format_date_th($n['created_at']) ?> · <?= e($n['type']) ?></div>
          </div>
        </a>
      <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>
