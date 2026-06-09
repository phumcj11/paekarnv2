<?php /** @var array $rows @var int $page @var int $totalPages @var int $total */ ?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
    <div>
      <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="shopping-cart" class="w-5 h-5 text-rose-600"></i> คำสั่งซื้อคูปอง</h2>
      <p class="text-sm text-slate-500"><?= number_format($total) ?> รายการ · <a href="<?= url('/admin/coupons') ?>" class="text-primary-700 hover:underline">← รายการคูปอง</a></p>
    </div>
    <div class="flex flex-wrap gap-2">
      <a href="<?= url('/admin/coupons/orders/create') ?>" class="px-4 py-2 bg-accent-500 text-white rounded-lg text-sm inline-flex items-center gap-1"><i data-lucide="plus" class="w-4 h-4"></i> ออกคูปอง</a>
      <a href="<?= url('/admin/coupons/orders/export.csv') ?>" class="px-4 py-2 border border-slate-300 rounded-lg text-sm hover:bg-slate-50 inline-flex items-center gap-1"><i data-lucide="download" class="w-4 h-4"></i> ส่งออก CSV</a>
    </div>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-600">
        <tr>
          <th class="text-left px-5 py-3">เลขที่</th>
          <th class="text-left px-5 py-3">ผู้ซื้อ</th>
          <th class="text-left px-5 py-3">จำนวน</th>
          <th class="text-left px-5 py-3">รวม</th>
          <th class="text-left px-5 py-3">วันที่</th>
          <th class="text-left px-5 py-3">สถานะ</th>
          <th class="text-right px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php $colors=['pending'=>'amber','paid'=>'emerald','cancelled'=>'slate','refunded'=>'rose'];
      foreach ($rows as $o): $c=$colors[$o['status']]??'slate'; ?>
        <tr class="hover:bg-slate-50">
          <td class="px-5 py-3 font-mono text-xs text-primary-700"><?= e($o['order_no']) ?></td>
          <td class="px-5 py-3"><?= e($o['buyer_name']) ?><div class="text-xs text-slate-500"><?= e($o['buyer_phone']) ?></div></td>
          <td class="px-5 py-3"><?= $o['quantity'] ?> ใบ × ฿<?= number_format($o['face_value']) ?></td>
          <td class="px-5 py-3 font-semibold"><?= format_money($o['total_price']) ?></td>
          <td class="px-5 py-3 text-xs"><?= format_date_th($o['created_at']) ?></td>
          <td class="px-5 py-3"><span class="text-xs font-semibold bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2 py-1 rounded-full"><?= e($o['status']) ?></span></td>
          <td class="px-5 py-3 text-right">
            <a href="<?= url('/admin/coupons/orders/' . $o['id']) ?>" class="px-3 py-1.5 text-xs bg-primary-600 text-white rounded-lg inline-flex items-center gap-1"><i data-lucide="eye" class="w-3.5 h-3.5"></i> ดู</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="p-4">
    <?php $q = $_GET; unset($q['page']); \App\Core\View::partial('partials/pagination', ['page'=>$page,'totalPages'=>$totalPages,'baseUrl'=>url('/admin/coupons/orders'),'query'=>$q]); ?>
  </div>
</div>
