<?php
/** @var array<int,array<string,mixed>> $rows */
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
  <div>
    <h1 class="text-xl font-bold text-primary-800">โพสต์ Facebook (แนะนำ)</h1>
    <p class="text-sm text-slate-600 mt-0.5">วางลิงก์โพสต์สาธารณะ — แสดงบนหน้ารีวิวด้วย Embedded Post (ต้องตั้ง Meta App ID ในการตั้งค่า)</p>
  </div>
  <a href="<?= url('/admin/review-facebook-posts/create') ?>" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-accent-500 hover:bg-accent-600 text-white rounded-xl font-semibold text-sm shrink-0">
    <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มโพสต์
  </a>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-200">
        <tr class="text-left">
          <th class="px-4 py-3 font-semibold text-slate-700">ลำดับ</th>
          <th class="px-4 py-3 font-semibold text-slate-700">หัวข้อ</th>
          <th class="px-4 py-3 font-semibold text-slate-700">ลิงก์</th>
          <th class="px-4 py-3 font-semibold text-slate-700">ใช้งาน</th>
          <th class="px-4 py-3 font-semibold text-slate-700 w-44"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (empty($rows)): ?>
          <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">ยังไม่มีรายการ — เพิ่มจากปุ่มด้านบน</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr class="hover:bg-slate-50/80">
              <td class="px-4 py-3 align-top font-mono text-xs"><?= (int)$r['sort_order'] ?></td>
              <td class="px-4 py-3 align-top font-semibold text-slate-900"><?= e((string)$r['title']) ?></td>
              <td class="px-4 py-3 align-top">
                <a href="<?= e((string)$r['post_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-accent-700 hover:underline break-all text-xs font-mono"><?= e((string)$r['post_url']) ?></a>
              </td>
              <td class="px-4 py-3 align-top"><?= !empty($r['is_active']) ? '<span class="text-emerald-600 font-medium">เปิด</span>' : '<span class="text-slate-400">ปิด</span>' ?></td>
              <td class="px-4 py-3 align-top">
                <div class="flex flex-col gap-1.5">
                  <a href="<?= url('/admin/review-facebook-posts/' . $r['id'] . '/edit') ?>" class="inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs font-medium">แก้ไข</a>
                  <form method="post" action="<?= url('/admin/review-facebook-posts/' . $r['id'] . '/delete') ?>" onsubmit="return confirm('ลบโพสต์นี้?')"><?= csrf() ?>
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-medium">ลบ</button>
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
