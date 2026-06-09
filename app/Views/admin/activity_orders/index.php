<?php
/** @var array<int,array<string,mixed>> $rows */
/** @var array<string,string> $statuses */
/** @var array<string,mixed> $revenue */
/** @var array<int,array<string,mixed>> $byProvider */
/** @var array<int,array<string,mixed>> $leadRows */
/** @var bool $hasSettlement */
/** @var string $filterMonth */
/** @var string $filterPayout */
$payoutLabels = ['' => 'ทั้งหมด', 'pending' => 'รอโอน provider', 'paid' => 'โอนแล้ว'];
?>
<div class="mb-5 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
  <div>
    <h1 class="text-xl font-bold text-primary-800">คำสั่งซื้อกิจกรรม</h1>
    <p class="text-sm text-slate-600 mt-0.5">ติดตาม voucher · รายได้แพกาญ · settlement ให้ provider</p>
  </div>
  <form method="get" class="flex flex-wrap items-end gap-2">
    <div>
      <label class="text-xs text-slate-500 block mb-1">เดือน</label>
      <input type="month" name="month" value="<?= e($filterMonth) ?>" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
    </div>
    <?php if ($hasSettlement): ?>
    <div>
      <label class="text-xs text-slate-500 block mb-1">Settlement</label>
      <select name="payout" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
        <?php foreach ($payoutLabels as $k => $label): ?>
          <option value="<?= e($k) ?>" <?= $filterPayout === $k ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <button class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-semibold">กรอง</button>
  </form>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
  <?php
  $kpis = [
    ['รายได้แพกาญ (คอม)', $revenue['commission'] ?? 0, 'text-emerald-700', 'percent'],
    ['โอนให้ provider', $revenue['payout'] ?? 0, 'text-sky-700', 'wallet'],
    ['รอโอน provider', $revenue['pending_payout'] ?? 0, 'text-amber-700', 'clock'],
    ['GMV (ยอดขาย)', $revenue['gross'] ?? 0, 'text-primary-700', 'trending-up'],
  ];
  foreach ($kpis as [$label, $val, $cls, $icon]):
  ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-4">
    <div class="flex items-center justify-between gap-2">
      <span class="text-xs font-semibold text-slate-500"><?= e($label) ?></span>
      <i data-lucide="<?= $icon ?>" class="w-4 h-4 <?= $cls ?>"></i>
    </div>
    <div class="mt-1 text-xl font-bold <?= $cls ?>"><?= format_money((float)$val) ?></div>
    <div class="text-[10px] text-slate-400 mt-0.5"><?= (int)($revenue['order_count'] ?? 0) ?> ออเดอร์ (paid+)</div>
  </div>
  <?php endforeach; ?>
</div>

<?php if ($byProvider !== []): ?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden mb-5">
  <div class="px-4 py-3 border-b border-slate-100 font-bold text-sm">สรุปตามผู้ให้บริการ — <?= e($filterMonth) ?></div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-600">
        <tr>
          <th class="px-4 py-2 text-left">Provider</th>
          <th class="px-4 py-2 text-right">ออเดอร์</th>
          <th class="px-4 py-2 text-right">คอมมิชชัน</th>
          <th class="px-4 py-2 text-right">รอโอน</th>
          <th class="px-4 py-2 text-right">โอนแล้ว</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php foreach ($byProvider as $pr): ?>
        <tr class="hover:bg-slate-50/80">
          <td class="px-4 py-2 font-semibold"><?= e($pr['provider_name'] ?? '—') ?></td>
          <td class="px-4 py-2 text-right"><?= (int)($pr['order_count'] ?? 0) ?></td>
          <td class="px-4 py-2 text-right text-emerald-700 font-semibold"><?= format_money($pr['commission'] ?? 0) ?></td>
          <td class="px-4 py-2 text-right text-amber-700"><?= format_money($pr['pending_payout'] ?? 0) ?></td>
          <td class="px-4 py-2 text-right text-sky-700"><?= format_money($pr['paid_payout'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($leadRows !== []): ?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden mb-5">
  <div class="px-4 py-3 border-b border-slate-100 font-bold text-sm">Lead clicks (LINE/โทร) — <?= e($filterMonth) ?></div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-600">
        <tr>
          <th class="px-4 py-2 text-left">สินค้า</th>
          <th class="px-4 py-2 text-left">Provider</th>
          <th class="px-4 py-2 text-right">LINE</th>
          <th class="px-4 py-2 text-right">โทร</th>
          <th class="px-4 py-2 text-right">รวม</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php foreach ($leadRows as $lr): ?>
        <tr>
          <td class="px-4 py-2"><?= e($lr['product_title'] ?? '') ?></td>
          <td class="px-4 py-2 text-slate-600"><?= e($lr['provider_name'] ?? '—') ?></td>
          <td class="px-4 py-2 text-right"><?= (int)($lr['line_clicks'] ?? 0) ?></td>
          <td class="px-4 py-2 text-right"><?= (int)($lr['phone_clicks'] ?? 0) ?></td>
          <td class="px-4 py-2 text-right font-semibold"><?= (int)($lr['click_count'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-200">
        <tr class="text-left">
          <th class="px-4 py-3 font-semibold">Order</th>
          <th class="px-4 py-3 font-semibold">สินค้า</th>
          <th class="px-4 py-3 font-semibold">Provider</th>
          <th class="px-4 py-3 font-semibold">ผู้ซื้อ</th>
          <th class="px-4 py-3 font-semibold">รวม</th>
          <th class="px-4 py-3 font-semibold">คอม / จ่าย</th>
          <?php if ($hasSettlement): ?><th class="px-4 py-3 font-semibold">Settlement</th><?php endif; ?>
          <th class="px-4 py-3 font-semibold">สถานะ</th>
          <th class="px-4 py-3 w-28"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php if ($rows === []): ?>
        <tr><td colspan="<?= $hasSettlement ? 9 : 8 ?>" class="px-4 py-10 text-center text-slate-500">ยังไม่มีคำสั่งซื้อ</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r):
        $eligible = in_array($r['status'], ['paid','confirmed','redeemed'], true);
        $paidOut = $hasSettlement && !empty($r['provider_paid_at']);
      ?>
        <tr class="hover:bg-slate-50/80">
          <td class="px-4 py-3 font-mono text-xs"><?= e($r['order_no']) ?><div class="text-slate-400"><?= e($r['created_at']) ?></div></td>
          <td class="px-4 py-3">
            <div class="font-semibold"><?= e($r['product_title']) ?></div>
            <?php if (!empty($r['option_name'])): ?><div class="text-xs text-slate-500"><?= e($r['option_name']) ?> × <?= (int)$r['quantity'] ?></div><?php endif; ?>
          </td>
          <td class="px-4 py-3 text-xs"><?= e($r['provider_name'] ?? '—') ?></td>
          <td class="px-4 py-3"><?= e($r['buyer_name']) ?><div class="text-xs text-slate-500"><?= e($r['buyer_phone']) ?></div></td>
          <td class="px-4 py-3 font-semibold text-primary-700"><?= format_money($r['total_price']) ?></td>
          <td class="px-4 py-3 text-xs">
            <div class="text-emerald-700">+<?= format_money($r['commission_amount']) ?></div>
            <div class="text-sky-700">→ <?= format_money($r['provider_payout']) ?></div>
          </td>
          <?php if ($hasSettlement): ?>
          <td class="px-4 py-3 text-xs">
            <?php if (!$eligible): ?>
              <span class="text-slate-400">—</span>
            <?php elseif ($paidOut): ?>
              <span class="text-emerald-700 font-semibold">โอนแล้ว</span>
              <div class="text-slate-400"><?= e($r['provider_paid_at']) ?></div>
            <?php else: ?>
              <span class="text-amber-700 font-semibold">รอโอน</span>
            <?php endif; ?>
          </td>
          <?php endif; ?>
          <td class="px-4 py-3"><?= e($statuses[$r['status']] ?? $r['status']) ?></td>
          <td class="px-4 py-3"><a href="<?= url('/admin/activity-orders/' . $r['id']) ?>" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs font-medium">ดู</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
