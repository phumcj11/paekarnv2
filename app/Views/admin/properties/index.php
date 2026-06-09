<?php /** @var array $rows @var int $page @var int $totalPages @var int $total */ ?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap items-center gap-3 justify-between w-full sm:w-auto">
      <div>
        <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="hotel" class="w-5 h-5 text-accent-600"></i> จัดการที่พัก</h2>
        <p class="text-sm text-slate-500">ทั้งหมด <?= number_format($total) ?> รายการ</p>
      </div>
      <a href="<?= url('/admin/properties/create') ?>" class="px-4 py-2 bg-accent-500 hover:bg-accent-600 text-white rounded-lg text-sm font-semibold inline-flex items-center gap-1.5 shrink-0"><i data-lucide="plus" class="w-4 h-4"></i> เพิ่มที่พัก</a>
    </div>
    <form method="get" class="flex gap-2">
      <input type="text" name="q" placeholder="ค้นหา..." value="<?= e($_GET['q'] ?? '') ?>" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
      <select name="status" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
        <option value="">ทุกสถานะ</option>
        <?php foreach (['draft','pending','published','rejected','archived'] as $st): ?>
          <option value="<?= $st ?>" <?= ($_GET['status']??'')===$st?'selected':'' ?>><?= $st ?></option>
        <?php endforeach; ?>
      </select>
      <button class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">กรอง</button>
    </form>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-600">
        <tr>
          <th class="text-left px-5 py-3">ที่พัก</th>
          <th class="text-left px-5 py-3">โซน / เจ้าของ</th>
          <th class="text-left px-5 py-3">ราคาต่ำสุด</th>
          <th class="text-left px-5 py-3">คะแนน</th>
          <th class="text-left px-5 py-3">สถานะ</th>
          <th class="text-right px-5 py-3">จัดการ</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php
      $colors=['draft'=>'slate','pending'=>'amber','published'=>'emerald','rejected'=>'rose','archived'=>'slate'];
      foreach ($rows as $p): $c=$colors[$p['status']]??'slate'; ?>
        <tr class="hover:bg-slate-50">
          <td class="px-5 py-3">
            <div class="flex items-center gap-2.5">
              <img src="<?= e(upload_url($p['cover_image'])) ?>" class="w-10 h-10 rounded-lg object-cover">
              <div>
                <a href="<?= url('/admin/properties/' . $p['id']) ?>" class="font-semibold hover:text-primary-700"><?= e($p['name']) ?></a>
                <div class="text-xs text-slate-500"><?= e($p['type']) ?>
                  <?php if ($p['is_featured']): ?> · <span class="text-accent-600">⭐ Featured</span><?php endif; ?>
                </div>
              </div>
            </div>
          </td>
          <td class="px-5 py-3 text-slate-600"><?= e($p['zone']) ?><div class="text-xs text-slate-400"><?= e($p['owner_name'] ?? '-') ?></div></td>
          <td class="px-5 py-3 font-semibold text-primary-700"><?= format_money($p['min_price']) ?></td>
          <td class="px-5 py-3">⭐ <?= number_format($p['rating_avg'],1) ?> · <?= $p['rating_count'] ?></td>
          <td class="px-5 py-3"><span class="text-xs font-semibold bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2 py-1 rounded-full"><?= e($p['status']) ?></span></td>
          <td class="px-5 py-3 text-right space-x-1 whitespace-nowrap">
            <a href="<?= url('/admin/properties/' . $p['id']) ?>" class="px-3 py-1.5 text-xs bg-primary-600 text-white rounded-lg inline-flex items-center gap-1"><i data-lucide="eye" class="w-3.5 h-3.5"></i> ดู</a>
            <a href="<?= url('/admin/properties/' . $p['id'] . '/edit') ?>" class="px-3 py-1.5 text-xs border border-slate-300 hover:bg-slate-50 rounded-lg inline-flex items-center gap-1"><i data-lucide="pencil" class="w-3.5 h-3.5"></i> แก้ไข</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="p-4">
    <?php $q=$_GET; unset($q['page']); \App\Core\View::partial('partials/pagination', ['page'=>$page,'totalPages'=>$totalPages,'baseUrl'=>url('/admin/properties'),'query'=>$q]); ?>
  </div>
</div>
