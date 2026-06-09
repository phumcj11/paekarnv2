<?php /** @var array<int,array<string,mixed>> $rows */ ?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
      <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="map-pin" class="w-5 h-5 text-accent-600"></i> โซนที่พัก</h2>
      <p class="text-xs text-slate-500 mt-1 max-w-xl">รายการมาตรฐานสำหรับ dropdown — ค่าในระบบยังเก็บที่ <span class="font-mono">properties.zone</span> เป็นข้อความ (ต้องตรงชื่อ) · แก้ชื่อโซนที่นี่จะอัปเดตที่พัก / ที่เที่ยว / รูปปกโซนหน้าแรกให้อัตโนมัติ</p>
    </div>
    <a href="<?= url('/admin/zones/create') ?>" class="px-4 py-2 bg-accent-500 text-white rounded-lg text-sm font-semibold inline-flex items-center gap-1.5 shrink-0"><i data-lucide="plus" class="w-4 h-4"></i> เพิ่มโซน</a>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-600">
        <tr>
          <th class="text-left px-5 py-3">ชื่อโซน</th>
          <th class="text-left px-5 py-3">ลำดับ</th>
          <th class="text-left px-5 py-3">ที่พัก (ทั้งหมด / เผยแพร่)</th>
          <th class="text-left px-5 py-3">ที่เที่ยว</th>
          <th class="text-right px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php foreach ($rows as $z): ?>
        <tr class="hover:bg-slate-50">
          <td class="px-5 py-3 font-semibold text-slate-900"><?= e($z['name']) ?></td>
          <td class="px-5 py-3 tabular-nums"><?= (int)($z['sort_order'] ?? 0) ?></td>
          <td class="px-5 py-3"><?= (int)($z['cnt_properties'] ?? 0) ?> / <?= (int)($z['cnt_published'] ?? 0) ?></td>
          <td class="px-5 py-3"><?= (int)($z['cnt_visitor_places'] ?? 0) ?></td>
          <td class="px-5 py-3 text-right whitespace-nowrap">
            <a href="<?= url('/admin/zones/' . (int)$z['id'] . '/edit') ?>" class="px-3 py-1.5 text-xs bg-primary-600 text-white rounded-lg inline-flex items-center gap-1"><i data-lucide="edit" class="w-3.5 h-3.5"></i> แก้ไข</a>
            <?php if ((int)($z['cnt_properties'] ?? 0) === 0 && (int)($z['cnt_visitor_places'] ?? 0) === 0): ?>
            <form method="post" action="<?= url('/admin/zones/' . (int)$z['id'] . '/delete') ?>" class="inline" onsubmit="return confirm('ลบโซนนี้ออกจากรายการมาตรฐาน? (ข้อมูลที่พักไม่ถูกลบ)')"><?= csrf() ?>
              <button type="submit" class="ml-1 px-3 py-1.5 text-xs bg-rose-500 text-white rounded-lg inline-flex items-center gap-1"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
