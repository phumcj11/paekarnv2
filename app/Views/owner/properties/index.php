<?php /** @var array $rows */ ?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
    <div>
      <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="hotel" class="w-5 h-5 text-accent-600"></i> ที่พักของฉัน</h2>
      <p class="text-sm text-slate-500">ทั้งหมด <?= number_format(count($rows)) ?> รายการ</p>
    </div>
    <a href="<?= url('/owner/properties/create') ?>" class="px-4 py-2 bg-accent-500 text-white rounded-lg text-sm font-semibold inline-flex items-center gap-1.5"><i data-lucide="plus" class="w-4 h-4"></i> เพิ่มที่พัก</a>
  </div>

  <?php if (empty($rows)): ?>
    <div class="p-12 text-center">
      <i data-lucide="hotel" class="w-12 h-12 mx-auto text-slate-400"></i>
      <h3 class="mt-3 font-semibold">ยังไม่มีที่พัก</h3>
      <p class="text-sm text-slate-500 mt-1">เริ่มต้นด้วยการเพิ่มที่พักแรกของคุณ</p>
      <a href="<?= url('/owner/properties/create') ?>" class="mt-4 inline-flex items-center gap-1.5 px-5 py-2.5 bg-accent-500 text-white rounded-xl font-semibold"><i data-lucide="plus-circle" class="w-4 h-4"></i> เพิ่มที่พัก</a>
    </div>
  <?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 p-5">
    <?php
    $statusColors = ['draft'=>'slate','pending'=>'amber','published'=>'emerald','rejected'=>'rose','archived'=>'slate'];
    $statusLabels = ['draft'=>'แบบร่าง','pending'=>'รออนุมัติ','published'=>'เผยแพร่แล้ว','rejected'=>'ถูกปฏิเสธ','archived'=>'เก็บแล้ว'];
    $statusIcons = ['draft'=>'file-text','pending'=>'clock','published'=>'check-circle','rejected'=>'x-circle','archived'=>'archive'];
    foreach ($rows as $p): $c = $statusColors[$p['status']] ?? 'slate'; $sic = $statusIcons[$p['status']] ?? 'circle-dot'; ?>
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-soft transition flex flex-col">
      <div class="aspect-[4/3] bg-slate-100 relative">
        <img src="<?= e(upload_url($p['cover_image']) ?: 'https://placehold.co/600x450') ?>" class="w-full h-full object-cover">
        <span class="absolute top-2 left-2 px-2 py-0.5 text-xs font-semibold bg-<?= $c ?>-100 text-<?= $c ?>-700 rounded-full inline-flex items-center gap-1 shadow-sm">
          <i data-lucide="<?= e($sic) ?>" class="w-3 h-3 shrink-0"></i><?= e($statusLabels[$p['status']] ?? $p['status']) ?></span>
      </div>
      <div class="p-4 flex-1 flex flex-col">
        <h3 class="font-bold truncate"><?= e($p['name']) ?></h3>
        <p class="text-xs text-slate-500"><?= e($p['zone']) ?></p>
        <div class="grid grid-cols-3 gap-2 mt-3 text-center text-xs">
          <div class="bg-slate-50 rounded-lg p-2"><div class="font-bold tabular-nums"><?= $p['unit_count'] ?? 0 ?></div><div class="text-slate-500 inline-flex items-center justify-center gap-0.5 mt-0.5"><i data-lucide="bed-double" class="w-3 h-3 opacity-70"></i>ห้อง</div></div>
          <div class="bg-slate-50 rounded-lg p-2"><div class="font-bold tabular-nums"><?= $p['booking_count'] ?? 0 ?></div><div class="text-slate-500 inline-flex items-center justify-center gap-0.5 mt-0.5"><i data-lucide="calendar-check" class="w-3 h-3 opacity-70"></i>จอง</div></div>
          <div class="bg-slate-50 rounded-lg p-2"><div class="font-bold tabular-nums"><?= number_format($p['rating_avg'],1) ?></div><div class="text-slate-500 inline-flex items-center justify-center gap-0.5 mt-0.5"><i data-lucide="star" class="w-3 h-3 opacity-70"></i>คะแนน</div></div>
        </div>
        <div class="mt-auto pt-3 flex gap-2">
          <a href="<?= url('/owner/properties/' . $p['id'] . '/edit') ?>" class="flex-1 px-3 py-1.5 text-xs bg-primary-600 text-white rounded-lg inline-flex items-center justify-center gap-1"><i data-lucide="edit" class="w-3.5 h-3.5"></i> แก้ไข</a>
          <a href="<?= url('/owner/properties/' . $p['id'] . '/units') ?>" class="flex-1 px-3 py-1.5 text-xs bg-accent-500 text-white rounded-lg inline-flex items-center justify-center gap-1"><i data-lucide="bed-double" class="w-3.5 h-3.5"></i> ห้อง/แพ</a>
          <a href="<?= url('/owner/properties/' . $p['id'] . '/line') ?>" title="LINE & ปฏิทิน" class="px-3 py-1.5 text-xs border border-[#06C755]/40 text-[#06C755] hover:bg-[#06C755]/5 rounded-lg inline-flex items-center justify-center gap-1"><i data-lucide="message-circle" class="w-3.5 h-3.5"></i></a>
          <a href="<?= url('/owner/properties/' . $p['id'] . '/availability') ?>" title="ปฏิทินวันว่าง" class="px-3 py-1.5 text-xs border border-slate-300 hover:bg-slate-50 rounded-lg inline-flex items-center justify-center gap-1"><i data-lucide="calendar" class="w-3.5 h-3.5"></i></a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
