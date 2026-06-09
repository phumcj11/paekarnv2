<?php
/**
 * @var string $prefill
 * @var array{type:string,data:array<string,mixed>}|null $result
 */
?>
<section class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
  <h1 class="text-2xl md:text-3xl font-bold flex items-center gap-2">
    <i data-lucide="search" class="w-7 h-7 text-accent-600"></i> ติดตามคำสั่งซื้อ
  </h1>
  <p class="text-slate-600 mt-1">กรอกหมายเลขจอง / Voucher Code พร้อมเบอร์โทรที่ใช้ตอนสั่งซื้อ</p>

  <form method="post" action="<?= url('/track-order') ?>" class="mt-6 bg-white border border-slate-200 rounded-2xl p-5 space-y-3">
    <?= csrf() ?>
    <div>
      <label class="text-sm font-medium text-slate-700 mb-1 block">หมายเลข / รหัส</label>
      <input type="text" name="code" required value="<?= e($prefill) ?>"
             placeholder="BK / PKAN- / AV- ..." autocomplete="off"
             class="w-full px-3 py-2.5 rounded-lg border border-slate-300 font-mono uppercase focus:border-primary-500 outline-none">
    </div>
    <div>
      <label class="text-sm font-medium text-slate-700 mb-1 block">เบอร์โทรศัพท์ที่ใช้สั่งซื้อ</label>
      <input type="tel" name="phone" inputmode="tel" pattern="[0-9\-+ ]{9,15}"
             placeholder="08x-xxx-xxxx"
             class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none">
      <div class="text-xs text-slate-500 mt-1">ระบบใช้ตรวจสอบว่าคุณเป็นเจ้าของคำสั่งซื้อจริง</div>
    </div>
    <button type="submit"
            class="w-full py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg inline-flex items-center justify-center gap-2">
      <i data-lucide="search" class="w-4 h-4"></i> ค้นหา
    </button>
  </form>

  <?php if ($result === null && $prefill !== ''): ?>
    <div class="mt-5 p-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 text-sm flex items-start gap-2">
      <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
      <div>
        <div class="font-semibold">ไม่พบคำสั่งซื้อ</div>
        <div>ตรวจสอบรหัสและเบอร์โทรอีกครั้ง หรือติดต่อทีมงาน LINE</div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($result !== null):
    $type = $result['type'];
    $data = $result['data'];
  ?>
    <div class="mt-6 bg-white border border-slate-200 rounded-2xl overflow-hidden">
      <?php if ($type === 'activity'):
        $cover = !empty($data['cover_image']) ? upload_url((string)$data['cover_image']) : 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?w=1200&q=80';
      ?>
        <img src="<?= e($cover) ?>" class="w-full h-40 object-cover" alt="">
        <div class="p-5">
          <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="font-bold text-lg"><?= e($data['product_title'] ?? 'กิจกรรม') ?></h2>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700"><?= e($data['status'] ?? 'pending') ?></span>
          </div>
          <div class="text-sm text-slate-600 mt-1">หมายเลข: <b><?= e($data['order_no']) ?></b></div>
          <div class="mt-3 rounded-xl border-2 border-dashed border-accent-300 bg-accent-50/30 p-4">
            <div class="text-xs text-slate-500">VOUCHER CODE</div>
            <div class="font-mono font-bold text-xl text-primary-700 break-all"><?= e($data['voucher_code']) ?></div>
            <?php if (!empty($data['travel_date'])): ?>
              <div class="text-sm text-slate-600 mt-2">วันใช้บริการ: <?= e($data['travel_date']) ?> <?= e($data['time_slot'] ?? '') ?></div>
            <?php endif; ?>
            <div class="text-sm text-slate-600 mt-1">จำนวน <?= (int)$data['quantity'] ?> · รวม <?= format_money($data['total_price']) ?></div>
          </div>
        </div>
      <?php elseif ($type === 'coupon'): ?>
        <div class="p-5">
          <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="font-bold text-lg">คำสั่งซื้อคูปอง</h2>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700"><?= e($data['status'] ?? 'pending') ?></span>
          </div>
          <div class="text-sm text-slate-600 mt-1">หมายเลข: <b><?= e($data['order_no']) ?></b> · จำนวน <?= (int)$data['quantity'] ?> ใบ · รวม <?= format_money($data['total_price']) ?></div>
          <?php if (!empty($data['coupons'])): ?>
            <div class="mt-3 space-y-2">
              <?php foreach ($data['coupons'] as $c): ?>
                <div class="flex items-center justify-between p-3 rounded-lg border border-slate-200 bg-slate-50">
                  <div>
                    <div class="font-mono font-bold text-primary-700"><?= e($c['code']) ?></div>
                    <div class="text-xs text-slate-500">หมดอายุ <?= format_date_th($c['expires_at']) ?></div>
                  </div>
                  <span class="text-xs px-2 py-1 rounded-full bg-white border border-slate-200"><?= e($c['status']) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php elseif ($type === 'booking'):
        $cover = !empty($data['cover_image']) ? upload_url((string)$data['cover_image']) : '';
      ?>
        <?php if ($cover !== ''): ?><img src="<?= e($cover) ?>" class="w-full h-40 object-cover" alt=""><?php endif; ?>
        <div class="p-5">
          <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="font-bold text-lg"><?= e($data['property_name']) ?></h2>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700"><?= e($data['status'] ?? 'pending') ?></span>
          </div>
          <div class="text-sm text-slate-600 mt-1">เลขที่จอง: <b><?= e($data['code']) ?></b></div>
          <div class="text-sm text-slate-600">หน่วยพัก: <?= e($data['unit_name']) ?></div>
          <div class="grid grid-cols-2 gap-3 mt-3 text-sm">
            <div><div class="text-xs text-slate-500">เช็คอิน</div><div class="font-semibold"><?= format_date_th($data['check_in']) ?></div></div>
            <div><div class="text-xs text-slate-500">เช็คเอาท์</div><div class="font-semibold"><?= format_date_th($data['check_out']) ?></div></div>
          </div>
          <div class="mt-3 p-3 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between">
            <span>รวม</span><span class="font-bold text-primary-700"><?= format_money($data['total_price']) ?></span>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="mt-6 text-center text-sm text-slate-500">
    มีปัญหา? <a href="<?= e(\App\Models\Setting::get('line_oa', '#')) ?>" target="_blank" class="text-emerald-700 font-semibold">ติดต่อทีมงาน LINE</a>
  </div>
</section>
