<?php /** @var array $order @var array $coupons */
$colors = ['pending'=>'amber','paid'=>'emerald','cancelled'=>'slate','refunded'=>'rose'];
$c = $colors[$order['status']] ?? 'slate';
$dt = static function (?string $sqlDt): string {
    if (!$sqlDt) return '';
    $t = strtotime($sqlDt);
    return $t ? date('Y-m-d\TH:i', $t) : '';
};
?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
  <a href="<?= url('/admin/coupons/orders') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1"><i data-lucide="arrow-left" class="w-4 h-4"></i> คำสั่งซื้อทั้งหมด</a>
  <?php if ($order['status'] !== 'cancelled'): ?>
  <form method="post" action="<?= url('/admin/coupons/orders/' . (int)$order['id'] . '/cancel') ?>" onsubmit="return confirm('ยกเลิกคำสั่งซื้อและคูปองที่ยังไม่ใช้?')"><?= csrf() ?>
    <button type="submit" class="px-4 py-2 border border-rose-200 text-rose-700 rounded-lg text-sm">ยกเลิกคำสั่งซื้อ</button>
  </form>
  <?php endif; ?>
</div>

<div class="grid lg:grid-cols-2 gap-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
    <div class="font-mono text-xl font-bold text-primary-700"><?= e($order['order_no']) ?></div>
    <span class="inline-block mt-2 text-xs font-semibold bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2 py-1 rounded-full"><?= e($order['status']) ?></span>

    <form method="post" action="<?= url('/admin/coupons/orders/' . (int)$order['id']) ?>" class="mt-4 space-y-3"><?= csrf() ?>
      <div class="grid sm:grid-cols-2 gap-3">
        <div>
          <label class="text-xs text-slate-500 block mb-1">ชื่อผู้ซื้อ</label>
          <input type="text" name="buyer_name" value="<?= e($order['buyer_name']) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
        </div>
        <div>
          <label class="text-xs text-slate-500 block mb-1">เบอร์</label>
          <input type="text" name="buyer_phone" value="<?= e($order['buyer_phone']) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
        </div>
      </div>
      <div>
        <label class="text-xs text-slate-500 block mb-1">อีเมล</label>
        <input type="email" name="buyer_email" value="<?= e($order['buyer_email'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
      </div>
      <div class="grid grid-cols-3 gap-3">
        <div>
          <label class="text-xs text-slate-500 block mb-1">มูลค่า</label>
          <input type="number" step="0.01" name="face_value" value="<?= e((string)$order['face_value']) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
        </div>
        <div>
          <label class="text-xs text-slate-500 block mb-1">ราคาขาย</label>
          <input type="number" step="0.01" name="sale_price" value="<?= e((string)$order['sale_price']) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
        </div>
        <div>
          <label class="text-xs text-slate-500 block mb-1">จำนวน</label>
          <input type="number" name="quantity" value="<?= (int)$order['quantity'] ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
        </div>
      </div>
      <div class="grid sm:grid-cols-2 gap-3">
        <div>
          <label class="text-xs text-slate-500 block mb-1">รวม</label>
          <input type="number" step="0.01" name="total_price" value="<?= e((string)$order['total_price']) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
        </div>
        <div>
          <label class="text-xs text-slate-500 block mb-1">สถานะ</label>
          <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
            <?php foreach (['pending','paid','cancelled','refunded'] as $st): ?>
            <option value="<?= $st ?>" <?= $order['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-semibold">บันทึกคำสั่งซื้อ</button>
    </form>

    <?php if (!empty($order['slip_path'])): ?>
    <hr class="my-4">
    <h3 class="font-bold text-sm mb-2">สลิป</h3>
    <a href="<?= e(upload_url($order['slip_path'])) ?>" target="_blank">
      <img src="<?= e(upload_url($order['slip_path'])) ?>" class="max-h-48 rounded border border-slate-200">
    </a>
    <?php endif; ?>

    <?php if ($order['status'] === 'pending'): ?>
    <form method="post" action="<?= url('/admin/coupons/orders/' . (int)$order['id'] . '/approve') ?>" class="mt-3"><?= csrf() ?>
      <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm">อนุมัติชำระเงิน</button>
    </form>
    <?php endif; ?>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
    <h3 class="font-bold mb-3">คูปองในออเดอร์ (<?= count($coupons) ?>)</h3>
    <ul class="space-y-2 text-sm">
      <?php foreach ($coupons as $cp):
        $cl = ['unused'=>'emerald','used'=>'blue','cancelled'=>'slate'][$cp['status']] ?? 'slate'; ?>
      <li class="flex items-center justify-between border border-slate-100 rounded-lg px-3 py-2">
        <a href="<?= url('/admin/coupons/' . (int)$cp['id']) ?>" class="font-mono font-semibold text-primary-700"><?= e($cp['code']) ?></a>
        <span class="text-xs bg-<?= $cl ?>-100 text-<?= $cl ?>-700 px-2 py-0.5 rounded-full"><?= e($cp['status']) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
