<?php /** @var array $rows */ ?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
    <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="briefcase" class="w-5 h-5 text-accent-600"></i> เจ้าของแพ</h2>
    <a href="<?= url('/admin/owners/create') ?>" class="px-4 py-2 bg-accent-500 hover:bg-accent-600 text-white rounded-lg text-sm font-semibold inline-flex items-center gap-1.5 shrink-0"><i data-lucide="user-plus" class="w-4 h-4"></i> เพิ่มเจ้าของแพ</a>
  </div>  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-600">
        <tr>
          <th class="text-left px-5 py-3">ชื่อ</th>
          <th class="text-left px-5 py-3">ติดต่อ</th>
          <th class="text-left px-5 py-3">ธุรกิจ</th>
          <th class="text-left px-5 py-3">บัญชีธนาคาร</th>
          <th class="text-left px-5 py-3">ที่พัก</th>
          <th class="text-left px-5 py-3">สถานะ</th>
          <th class="text-right px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php $colors=['active'=>'emerald','pending'=>'amber','paused'=>'slate','terminated'=>'rose'];
      foreach ($rows as $o): $c=$colors[$o['partner_status']]??'slate'; ?>
        <tr class="hover:bg-slate-50">
          <td class="px-5 py-3 font-semibold"><?= e($o['name']) ?></td>
          <td class="px-5 py-3 text-xs"><?= e($o['email']) ?><div class="text-slate-500"><?= e($o['phone']) ?></div></td>
          <td class="px-5 py-3"><?= e($o['business_name'] ?? '-') ?></td>
          <td class="px-5 py-3 text-xs"><?= e($o['bank_name']) ?><div class="font-mono"><?= e($o['bank_account']) ?></div></td>
          <td class="px-5 py-3"><?= $o['property_count'] ?> รายการ</td>
          <td class="px-5 py-3"><span class="text-xs font-semibold bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2 py-1 rounded-full"><?= e($o['partner_status']) ?></span></td>
          <td class="px-5 py-3 text-right space-x-1 whitespace-nowrap">
            <a href="<?= url('/admin/owners/' . $o['id']) ?>" class="px-3 py-1.5 text-xs bg-primary-600 text-white rounded-lg inline-flex items-center gap-1"><i data-lucide="eye" class="w-3.5 h-3.5"></i> ดู</a>
            <a href="<?= url('/admin/owners/' . $o['id'] . '/edit') ?>" class="px-3 py-1.5 text-xs border border-slate-300 hover:bg-slate-50 rounded-lg inline-flex items-center gap-1"><i data-lucide="pencil" class="w-3.5 h-3.5"></i> แก้ไข</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
