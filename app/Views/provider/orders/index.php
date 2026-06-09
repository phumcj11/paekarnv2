<?php
/** @var array<int,array<string,mixed>> $rows */
/** @var array<string,string> $statuses */
/** @var string $filter */
/** @var bool $isActive */
?>
<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
  <div>
    <h2 class="text-lg font-bold text-slate-800">คำสั่งซื้อ</h2>
    <p class="text-sm text-slate-500">ออเดอร์จากสินค้าของคุณเท่านั้น</p>
  </div>
  <form method="get" class="flex gap-2 items-center">
    <select name="status" class="px-3 py-2 rounded-lg border border-slate-300 text-sm" onchange="this.form.submit()">
      <option value="">ทุกสถานะ</option>
      <?php foreach ($statuses as $k => $lab): ?>
        <option value="<?= e($k) ?>" <?= $filter === $k ? 'selected' : '' ?>><?= e($lab) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-200">
        <tr class="text-left">
          <th class="px-4 py-3 font-semibold">เลขที่</th>
          <th class="px-4 py-3 font-semibold">สินค้า</th>
          <th class="px-4 py-3 font-semibold">ลูกค้า</th>
          <th class="px-4 py-3 font-semibold">วันใช้บริการ</th>
          <th class="px-4 py-3 font-semibold">ยอด</th>
          <th class="px-4 py-3 font-semibold">สถานะ</th>
          <th class="px-4 py-3 w-24"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php if ($rows === []): ?>
        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">ยังไม่มีคำสั่งซื้อ</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr class="hover:bg-slate-50/80">
          <td class="px-4 py-3 font-mono text-xs"><?= e($r['order_no']) ?></td>
          <td class="px-4 py-3"><?= e($r['product_title'] ?? '') ?></td>
          <td class="px-4 py-3">
            <div><?= e($r['buyer_name']) ?></div>
            <div class="text-xs text-slate-500"><?= e($r['buyer_phone']) ?></div>
          </td>
          <td class="px-4 py-3 text-slate-600"><?= e($r['travel_date'] ?: '—') ?></td>
          <td class="px-4 py-3 font-semibold"><?= format_money($r['total_price']) ?></td>
          <td class="px-4 py-3"><span class="font-semibold"><?= e($statuses[$r['status']] ?? $r['status']) ?></span></td>
          <td class="px-4 py-3">
            <a href="<?= url('/provider/orders/' . $r['id']) ?>" class="text-teal-600 hover:underline text-xs font-semibold">ดู</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
