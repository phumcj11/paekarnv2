<?php /** @var array $rows */ ?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-8 grid grid-cols-1 lg:grid-cols-12 gap-6">
  <?php \App\Core\View::partial('partials/account-nav'); ?>
  <div class="lg:col-span-9">
    <h1 class="text-2xl font-bold mb-4 flex items-center gap-2"><i data-lucide="ticket" class="w-6 h-6 text-rose-600"></i> คูปองของฉัน</h1>
    <?php if (empty($rows)): ?>
      <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-10 text-center">
        <i data-lucide="ticket-x" class="w-12 h-12 mx-auto text-slate-400"></i>
        <h3 class="mt-3 font-semibold">ยังไม่มีคูปอง</h3>
        <a href="<?= url('/coupons/buy') ?>" class="mt-3 inline-block px-5 py-2.5 bg-accent-500 text-white rounded-xl">ซื้อคูปอง</a>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <?php
      $statusColors = [
          'unused'    => 'bg-emerald-50 border-emerald-300',
          'reserved'  => 'bg-amber-50 border-amber-300',
          'used'      => 'bg-slate-100 border-slate-300',
          'expired'   => 'bg-rose-50 border-rose-300',
          'revoked'   => 'bg-rose-50 border-rose-300',
          'cancelled' => 'bg-slate-100 border-slate-400',
      ];
      $statusLabels = [
          'unused'    => 'ใช้งานได้',
          'reserved'  => 'จองใช้ไว้',
          'used'      => 'ใช้แล้ว',
          'expired'   => 'หมดอายุ',
          'revoked'   => 'เพิกถอน',
          'cancelled' => 'ยกเลิก',
      ];
      foreach ($rows as $c): ?>
        <div class="rounded-2xl border-2 border-dashed p-5 <?= $statusColors[$c['status']] ?? 'bg-white border-slate-300' ?>">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold px-2 py-1 rounded-full bg-white border"><?= e($statusLabels[$c['status']] ?? $c['status']) ?></span>
            <i data-lucide="ticket" class="w-5 h-5 text-rose-500"></i>
          </div>
          <div class="text-3xl font-extrabold text-primary-700 mt-2">฿<?= number_format($c['face_value']) ?></div>
          <div class="text-xs text-slate-500">มูลค่าใช้จริง</div>
          <hr class="my-3 border-dashed">
          <div class="text-xs text-slate-500">CODE</div>
          <div class="font-mono font-bold text-lg"><?= e($c['code']) ?></div>
          <div class="text-xs text-slate-500 mt-2">หมดอายุ <?= format_date_th($c['expires_at']) ?></div>
          <?php if ($c['status']==='used'): ?>
            <div class="text-xs text-slate-500 mt-1">ใช้เมื่อ <?= format_date_th($c['used_at']) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
