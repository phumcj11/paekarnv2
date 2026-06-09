<?php /** @var array<int,array<string,mixed>> $rows */ ?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
  <a href="<?= url('/admin/membership/orders') ?>" class="text-sm text-primary-600 hover:underline inline-flex items-center gap-1"><i data-lucide="shopping-cart" class="w-4 h-4"></i> คำสั่งซื้อสมาชิก</a>
  <a href="<?= url('/admin/membership/plans/create') ?>" class="px-4 py-2 bg-accent-500 hover:bg-accent-600 text-white rounded-lg text-sm font-semibold inline-flex items-center gap-2"><i data-lucide="plus" class="w-4 h-4"></i> เพิ่มแพ็กเกจ</a>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
  <div class="p-5 border-b border-slate-100">
    <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="package" class="w-5 h-5 text-accent-600"></i> แพ็กเกจสมาชิกเจ้าของแพ</h2>
    <p class="text-xs text-slate-500 mt-1">ใช้เปิด/ปิดการขาย (active), ราคา และระยะเวลา — เจ้าของแพเห็นเฉพาะแพ็กเกจที่ active</p>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-600">
        <tr>
          <th class="text-left px-5 py-3">รหัส</th>
          <th class="text-left px-5 py-3">Tier</th>
          <th class="text-left px-5 py-3">ระยะเวลา</th>
          <th class="text-left px-5 py-3">ราคา</th>
          <th class="text-left px-5 py-3">ลำดับ</th>
          <th class="text-left px-5 py-3">ขาย</th>
          <th class="text-right px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">ยังไม่มีแพ็กเกจในระบบ</td></tr>
      <?php else: foreach ($rows as $p):
        $life = (int)($p['is_lifetime'] ?? 0) === 1;
        $dur = $p['duration_days'] ?? null;
      ?>
        <tr class="hover:bg-slate-50">
          <td class="px-5 py-3 font-mono font-semibold text-primary-800"><?= e($p['code']) ?></td>
          <td class="px-5 py-3"><?= e($p['tier']) ?></td>
          <td class="px-5 py-3"><?= $life ? 'ตลอดชีพ' : ($dur ? (int)$dur . ' วัน' : '-') ?></td>
          <td class="px-5 py-3 font-semibold"><?= format_money($p['price']) ?></td>
          <td class="px-5 py-3"><?= (int)($p['sort_order'] ?? 0) ?></td>
          <td class="px-5 py-3"><?= (int)($p['is_active'] ?? 0) === 1 ? '<span class="text-emerald-700 font-medium">เปิด</span>' : '<span class="text-slate-400">ปิด</span>' ?></td>
          <td class="px-5 py-3 text-right whitespace-nowrap">
            <a href="<?= url('/admin/membership/plans/' . (int)$p['id'] . '/edit') ?>" class="px-3 py-1.5 text-xs bg-primary-600 text-white rounded-lg inline-flex items-center gap-1">แก้ไข</a>
            <form method="post" action="<?= url('/admin/membership/plans/' . (int)$p['id'] . '/delete') ?>" class="inline ml-1" onsubmit="return confirm('ลบแพ็กเกจนี้? (ได้เมื่อไม่มีออเดอร์อ้างอิง)');"><?= csrf() ?>
              <button type="submit" class="px-3 py-1.5 text-xs bg-rose-50 text-rose-700 border border-rose-200 rounded-lg">ลบ</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
