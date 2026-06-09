<?php
/** @var array<int,array<string,mixed>> $rows */
/** @var array<string,string> $categories */
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
  <div>
    <h1 class="text-xl font-bold text-primary-800">ที่เที่ยว / สถานที่</h1>
    <p class="text-sm text-slate-600 mt-0.5">จัดการ POI สำหรับหน้า «สถานที่ท่องเที่ยว» — ผูกโซนเดียวกับที่พักเพื่อแนะนำแพใกล้เคียง</p>
  </div>
  <a href="<?= url('/admin/visitor-places/create') ?>" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-accent-500 hover:bg-accent-600 text-white rounded-xl font-semibold text-sm shrink-0">
    <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มสถานที่
  </a>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-200">
        <tr class="text-left">
          <th class="px-4 py-3 font-semibold text-slate-700">ลำดับ</th>
          <th class="px-4 py-3 font-semibold text-slate-700">ชื่อ</th>
          <th class="px-4 py-3 font-semibold text-slate-700">หมวด</th>
          <th class="px-4 py-3 font-semibold text-slate-700">คะแนน</th>
          <th class="px-4 py-3 font-semibold text-slate-700">อำเภอ</th>
          <th class="px-4 py-3 font-semibold text-slate-700">โซน</th>
          <th class="px-4 py-3 font-semibold text-slate-700">Slug</th>
          <th class="px-4 py-3 font-semibold text-slate-700">ใช้งาน</th>
          <th class="px-4 py-3 w-40"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (empty($rows)): ?>
          <tr><td colspan="9" class="px-4 py-10 text-center text-slate-500">ยังไม่มีรายการ</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr class="hover:bg-slate-50/80">
              <td class="px-4 py-3 align-top font-mono text-xs"><?= (int)$r['sort_order'] ?></td>
              <td class="px-4 py-3 align-top font-semibold"><?= e($r['name']) ?></td>
              <td class="px-4 py-3 align-top"><?= e($categories[$r['category']] ?? $r['category']) ?></td>
              <td class="px-4 py-3 align-top text-xs text-slate-600">
                <?php if (!empty($r['rating_avg'])): ?>
                  <span class="text-amber-600 font-bold">★ <?= e((string)$r['rating_avg']) ?></span>
                  <span class="text-slate-400">(<?= (int)($r['rating_count'] ?? 0) ?>)</span>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 align-top text-slate-600"><?= !empty($r['district']) ? e((string)$r['district']) : '—' ?></td>
              <td class="px-4 py-3 align-top text-slate-600"><?= $r['zone'] ? e($r['zone']) : '—' ?></td>
              <td class="px-4 py-3 align-top font-mono text-xs"><?= e($r['slug']) ?></td>
              <td class="px-4 py-3 align-top"><?= !empty($r['is_active']) ? '<span class="text-emerald-600 font-medium">เปิด</span>' : '<span class="text-slate-400">ปิด</span>' ?></td>
              <td class="px-4 py-3 align-top">
                <div class="flex flex-col gap-1.5">
                  <a href="<?= url('/places/' . $r['slug']) ?>" target="_blank" rel="noopener" class="text-xs text-accent-600 hover:underline">ดูหน้าบ้าน</a>
                  <a href="<?= url('/admin/visitor-places/' . $r['id'] . '/edit') ?>" class="inline-flex items-center justify-center px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs font-medium">แก้ไข</a>
                  <form method="post" action="<?= url('/admin/visitor-places/' . $r['id'] . '/delete') ?>" onsubmit="return confirm('ลบสถานที่นี้?')"><?= csrf() ?>
                    <button type="submit" class="w-full px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-medium">ลบ</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
