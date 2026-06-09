<?php
/** @var array<int,array<string,mixed>> $rows */
/** @var array<string,string> $statuses */
/** @var bool $isActive */
use App\Models\ActivityProduct;
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
  <div>
    <h2 class="text-lg font-bold text-slate-800">สินค้า / บริการของฉัน</h2>
    <p class="text-sm text-slate-500">สร้างฉบับร่าง → ส่งตรวจ → ทีมงานเผยแพร่</p>
  </div>
  <?php if ($isActive): ?>
  <a href="<?= url('/provider/products/create') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-xl font-semibold text-sm hover:bg-teal-700">
    <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มรายการ
  </a>
  <?php endif; ?>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-200">
        <tr class="text-left">
          <th class="px-4 py-3 font-semibold">สินค้า</th>
          <th class="px-4 py-3 font-semibold">ราคา</th>
          <th class="px-4 py-3 font-semibold">สถานะ</th>
          <th class="px-4 py-3 w-44"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php if ($rows === []): ?>
        <tr><td colspan="4" class="px-4 py-12 text-center text-slate-500">
          <?php if ($isActive): ?>ยังไม่มีสินค้า — <a href="<?= url('/provider/products/create') ?>" class="text-teal-600 font-semibold hover:underline">สร้างรายการแรก</a>
          <?php else: ?>รอการอนุมัติบัญชีก่อนสร้างสินค้า<?php endif; ?>
        </td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr class="hover:bg-slate-50/80">
          <td class="px-4 py-3">
            <div class="font-semibold"><?= e($r['title']) ?></div>
            <div class="font-mono text-xs text-slate-400"><?= e($r['slug']) ?></div>
            <?php if (!empty($r['review_note'])): ?>
              <div class="text-xs text-rose-600 mt-1">หมายเหตุ: <?= e($r['review_note']) ?></div>
            <?php endif; ?>
          </td>
          <td class="px-4 py-3 font-semibold"><?= format_money($r['base_price']) ?></td>
          <td class="px-4 py-3">
            <span class="font-semibold <?= $r['status'] === 'published' ? 'text-emerald-600' : ($r['status'] === 'pending_review' ? 'text-sky-600' : 'text-amber-600') ?>">
              <?= e($statuses[$r['status']] ?? $r['status']) ?>
            </span>
          </td>
          <td class="px-4 py-3">
            <div class="flex flex-col gap-1.5">
              <?php if ($r['status'] === 'published'): ?>
                <a href="<?= url('/activities/' . $r['slug']) ?>" target="_blank" class="text-xs text-teal-600 hover:underline text-center">ดูหน้าบ้าน</a>
              <?php endif; ?>
              <?php if (in_array($r['status'], ['draft', 'pending_review', 'published'], true)): ?>
                <a href="<?= url('/provider/products/' . $r['id'] . '/edit') ?>" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs text-center font-medium">แก้ไข</a>
              <?php endif; ?>
              <?php if ($isActive && in_array($r['status'], ['draft', 'pending_review'], true)): ?>
                <form method="post" action="<?= url('/provider/products/' . $r['id'] . '/submit-review') ?>"><?= csrf() ?>
                  <button class="w-full px-3 py-1.5 bg-sky-50 hover:bg-sky-100 text-sky-700 rounded-lg text-xs font-medium">ส่งตรวจ</button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
