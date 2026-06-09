<?php /** @var array<int,array<string,mixed>> $rows */ ?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
      <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="star" class="w-5 h-5 text-accent-600"></i> Featured กิจกรรม</h2>
      <p class="text-xs text-slate-500 mt-1 max-w-2xl">แพ็กโฆษณารายการ — ตั้ง is_featured + priority บนสินค้า · หมดอายุอัตโนมัติผ่าน cron</p>
    </div>
    <a href="<?= url('/admin/activity-featured/create') ?>" class="px-4 py-2 bg-accent-500 text-white rounded-lg text-sm font-semibold inline-flex items-center gap-1.5 shrink-0"><i data-lucide="plus" class="w-4 h-4"></i> เพิ่มแคมเปญ</a>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-600">
        <tr>
          <th class="text-left px-5 py-3">สินค้า</th>
          <th class="text-left px-5 py-3">Provider</th>
          <th class="text-right px-5 py-3">ราคา</th>
          <th class="text-center px-5 py-3">Priority</th>
          <th class="text-left px-5 py-3">ช่วงวันที่</th>
          <th class="text-center px-5 py-3">ใช้งาน</th>
          <th class="text-right px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php foreach ($rows as $r): ?>
        <tr class="hover:bg-slate-50">
          <td class="px-5 py-3 font-semibold max-w-xs truncate"><?= e($r['product_title'] ?? '') ?></td>
          <td class="px-5 py-3 text-xs text-slate-600"><?= e($r['provider_name'] ?? '—') ?></td>
          <td class="px-5 py-3 text-right"><?= format_money($r['price_paid'] ?? 0) ?></td>
          <td class="px-5 py-3 text-center font-mono"><?= (int)($r['priority_boost'] ?? 0) ?></td>
          <td class="px-5 py-3 text-xs whitespace-nowrap"><?= !empty($r['starts_at']) ? e((string)$r['starts_at']) : '—' ?> → <?= !empty($r['ends_at']) ? e((string)$r['ends_at']) : '—' ?></td>
          <td class="px-5 py-3 text-center"><?= !empty($r['is_active']) ? '<span class="text-emerald-600 font-medium">เปิด</span>' : '<span class="text-slate-400">ปิด</span>' ?></td>
          <td class="px-5 py-3 text-right whitespace-nowrap">
            <a href="<?= url('/admin/activity-featured/' . (int)$r['id'] . '/edit') ?>" class="px-3 py-1.5 text-xs bg-primary-600 text-white rounded-lg inline-flex items-center gap-1"><i data-lucide="edit" class="w-3.5 h-3.5"></i></a>
            <form method="post" action="<?= url('/admin/activity-featured/' . (int)$r['id'] . '/delete') ?>" class="inline" onsubmit="return confirm('ลบแคมเปญนี้?')"><?= csrf() ?>
              <button type="submit" class="ml-1 px-3 py-1.5 text-xs bg-rose-500 text-white rounded-lg inline-flex items-center gap-1"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if ($rows === []): ?>
        <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">ยังไม่มีแคมเปญ Featured</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
