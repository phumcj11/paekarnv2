<?php /** @var array<int,array<string,mixed>> $rows */ ?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
    <div>
      <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="crown" class="w-5 h-5 text-amber-600"></i> คำสั่งซื้อสมาชิกเจ้าของแพ</h2>
      <p class="text-xs text-slate-500 mt-1">อนุมัติเมื่อลูกค้าส่งสลิปไม่ครบหรือสถานะ pending — สิทธิ์จะถูกต่อจากวันหมดปัจจุบัน · <a href="<?= url('/admin/membership/plans') ?>" class="text-primary-600 hover:underline font-medium">จัดการแพ็กเกจ</a></p>
    </div>
    <a href="<?= url('/admin/membership/plans') ?>" class="px-3 py-2 text-sm border border-slate-300 rounded-lg hover:bg-slate-50 inline-flex items-center gap-2"><i data-lucide="package" class="w-4 h-4"></i> แพ็กเกจ</a>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-600">
        <tr>
          <th class="text-left px-5 py-3">เลขที่</th>
          <th class="text-left px-5 py-3">เจ้าของแพ</th>
          <th class="text-left px-5 py-3">แพ็กเกจ</th>
          <th class="text-left px-5 py-3">ยอด</th>
          <th class="text-left px-5 py-3">สลิป</th>
          <th class="text-left px-5 py-3">สถานะ</th>
          <th class="text-right px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">ยังไม่มีคำสั่งซื้อ</td></tr>
      <?php else:
      $colors=['pending'=>'amber','paid'=>'emerald','cancelled'=>'slate'];
      foreach ($rows as $o): $c=$colors[$o['status']]??'slate'; ?>
        <tr class="hover:bg-slate-50 align-top">
          <td class="px-5 py-3 font-mono text-xs text-primary-700"><?= e($o['order_no']) ?></td>
          <td class="px-5 py-3">
            <?= e($o['owner_name'] ?? '') ?>
            <div class="text-xs text-slate-500"><?= e($o['owner_email'] ?? '') ?></div>
          </td>
          <td class="px-5 py-3"><?= e($o['plan_code']) ?><div class="text-xs text-slate-500"><?= e($o['plan_tier']) ?></div></td>
          <td class="px-5 py-3 font-semibold"><?= format_money($o['amount']) ?></td>
          <td class="px-5 py-3">
            <?php if (!empty($o['slip_path'])): ?>
              <a href="<?= e(upload_url($o['slip_path'])) ?>" target="_blank" class="inline-block"><img src="<?= e(upload_url($o['slip_path'])) ?>" alt="" class="h-16 w-auto rounded border border-slate-200 object-cover"></a>
            <?php else: ?>
              <span class="text-xs text-slate-400">—</span>
            <?php endif; ?>
          </td>
          <td class="px-5 py-3"><span class="text-xs font-semibold bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2 py-1 rounded-full"><?= e($o['status']) ?></span></td>
          <td class="px-5 py-3 text-right whitespace-nowrap">
            <?php if (($o['status'] ?? '') === 'pending'): ?>
            <form method="post" action="<?= url('/admin/membership/orders/' . (int)$o['id'] . '/approve') ?>" class="inline"><?= csrf() ?>
              <button type="submit" class="px-3 py-1.5 text-xs bg-emerald-500 text-white rounded-lg inline-flex items-center gap-1"><i data-lucide="check" class="w-3.5 h-3.5"></i> อนุมัติ</button>
            </form>
            <form method="post" action="<?= url('/admin/membership/orders/' . (int)$o['id'] . '/cancel') ?>" class="inline ml-1" onsubmit="return confirm('ยกเลิกคำสั่งซื้อนี้?');"><?= csrf() ?>
              <button type="submit" class="px-3 py-1.5 text-xs bg-slate-100 text-slate-700 border border-slate-300 rounded-lg">ยกเลิก</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
