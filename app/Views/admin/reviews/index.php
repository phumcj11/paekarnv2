<?php /** @var array $rows */ ?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="message-circle" class="w-5 h-5 text-accent-600"></i> จัดการรีวิว</h2>
    <a href="<?= url('/admin/reviews/create') ?>" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-accent-500 hover:bg-accent-600 text-white text-sm font-semibold rounded-lg shrink-0">
      <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มรีวิว
    </a>
  </div>
  <div class="divide-y divide-slate-100">
    <?php foreach ($rows as $r): ?>
    <div class="p-5">
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <span class="font-semibold"><?= e($r['reviewer_name']) ?></span>
            <span class="text-xs text-slate-500">→ <?= e($r['property_name']) ?></span>
            <?php if (!$r['is_approved']): ?><span class="text-[10px] bg-amber-100 text-amber-700 font-semibold px-1.5 py-0.5 rounded-full">รออนุมัติ</span><?php endif; ?>
          </div>
          <div class="mt-1"><?= star_html((float)$r['rating']) ?> <span class="text-xs text-slate-500"><?= format_date_th($r['created_at']) ?></span></div>
          <?php if ($r['title']): ?><div class="mt-1 font-semibold"><?= e($r['title']) ?></div><?php endif; ?>
          <p class="text-sm text-slate-700 mt-1"><?= e($r['content']) ?></p>
        </div>
        <div class="flex flex-col gap-1.5 shrink-0">
          <a href="<?= url('/admin/reviews/' . $r['id'] . '/edit') ?>" class="px-3 py-1.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs rounded-lg inline-flex items-center justify-center gap-1 w-full">
            <i data-lucide="pencil" class="w-3.5 h-3.5"></i> แก้ไข
          </a>
          <?php if (!$r['is_approved']): ?>
          <form method="post" action="<?= url('/admin/reviews/' . $r['id'] . '/approve') ?>"><?= csrf() ?><button class="px-3 py-1.5 bg-emerald-500 text-white text-xs rounded-lg inline-flex items-center gap-1 w-full justify-center"><i data-lucide="check" class="w-3.5 h-3.5"></i> อนุมัติ</button></form>
          <?php endif; ?>
          <form method="post" action="<?= url('/admin/reviews/' . $r['id'] . '/delete') ?>" onsubmit="return confirm('ยืนยันลบรีวิวนี้?')"><?= csrf() ?><button class="px-3 py-1.5 bg-rose-500 text-white text-xs rounded-lg inline-flex items-center gap-1 w-full justify-center"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i> ลบ</button></form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?><div class="p-10 text-center text-slate-500">ไม่มีรีวิว</div><?php endif; ?>
  </div>
</div>
