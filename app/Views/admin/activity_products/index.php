<?php
/** @var array<int,array<string,mixed>> $rows */
/** @var array<string,string> $categories */
/** @var array<string,string> $modes */
/** @var array<string,string> $statuses */
/** @var string $filter */
/** @var int $pendingCount */
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
  <div>
    <h1 class="text-xl font-bold text-primary-800">สินค้า / กิจกรรม</h1>
    <p class="text-sm text-slate-600 mt-0.5">กิจกรรม รถเช่า รถนำเที่ยว และบริการที่ขายผ่านแพกาญ</p>
  </div>
  <a href="<?= url('/admin/activity-products/create') ?>" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-accent-500 hover:bg-accent-600 text-white rounded-xl font-semibold text-sm">
    <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มสินค้า
  </a>
</div>

<div class="flex flex-wrap gap-2 mb-4">
  <a href="<?= url('/admin/activity-products') ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium <?= $filter === '' ? 'bg-primary-700 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>">ทั้งหมด</a>
  <a href="<?= url('/admin/activity-products?status=pending_review') ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium <?= $filter === 'pending_review' ? 'bg-sky-600 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>">
    รอตรวจ<?= $pendingCount > 0 ? ' (' . $pendingCount . ')' : '' ?>
  </a>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-200">
        <tr class="text-left">
          <th class="px-4 py-3 font-semibold">สินค้า</th>
          <th class="px-4 py-3 font-semibold">หมวด</th>
          <th class="px-4 py-3 font-semibold">ผู้ให้บริการ</th>
          <th class="px-4 py-3 font-semibold">อำเภอ/โซน</th>
          <th class="px-4 py-3 font-semibold">ราคา</th>
          <th class="px-4 py-3 font-semibold">ขาย</th>
          <th class="px-4 py-3 font-semibold">สถานะ</th>
          <th class="px-4 py-3 w-40"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php if ($rows === []): ?>
        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-500">ยังไม่มีสินค้า</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr class="hover:bg-slate-50/80">
          <td class="px-4 py-3">
            <div class="font-semibold"><?= e($r['title']) ?></div>
            <div class="font-mono text-xs text-slate-400"><?= e($r['slug']) ?></div>
            <?php if (!empty($r['is_featured'])): ?><span class="text-[10px] text-amber-700 bg-amber-100 px-1.5 py-0.5 rounded">Featured</span><?php endif; ?>
          </td>
          <td class="px-4 py-3"><?= e($categories[$r['category']] ?? $r['category']) ?></td>
          <td class="px-4 py-3 text-slate-600"><?= e($r['provider_name'] ?? '—') ?></td>
          <td class="px-4 py-3 text-slate-600"><?= e(trim(($r['district'] ?? '') . ' ' . ($r['zone'] ?? '')) ?: '—') ?></td>
          <td class="px-4 py-3 font-semibold text-primary-700"><?= format_money($r['base_price']) ?></td>
          <td class="px-4 py-3"><?= e($modes[$r['booking_mode']] ?? $r['booking_mode']) ?></td>
          <td class="px-4 py-3">
            <?php
            $cls = match ($r['status']) {
                'published' => 'text-emerald-600',
                'pending_review' => 'text-sky-600',
                'draft' => 'text-amber-600',
                default => 'text-slate-400',
            };
            ?>
            <span class="<?= $cls ?> font-semibold"><?= e($statuses[$r['status']] ?? $r['status']) ?></span>
          </td>
          <td class="px-4 py-3">
            <div class="flex flex-col gap-1.5">
              <?php if ($r['status'] === 'published'): ?><a href="<?= url('/activities/' . $r['slug']) ?>" target="_blank" class="text-xs text-accent-600 hover:underline">ดูหน้าบ้าน</a><?php endif; ?>
              <a href="<?= url('/admin/activity-products/' . $r['id'] . '/edit') ?>" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs text-center font-medium">แก้ไข</a>
              <?php if ($r['status'] === 'pending_review'): ?>
              <form method="post" action="<?= url('/admin/activity-products/' . $r['id'] . '/publish') ?>"><?= csrf() ?>
                <button class="w-full px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg text-xs font-medium">เผยแพร่</button>
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
