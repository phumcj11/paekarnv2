<?php /** @var array $order */ ?>
<section class="max-w-xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-soft p-8 text-center">
  <div class="w-14 h-14 mx-auto rounded-full bg-emerald-100 text-emerald-600 grid place-items-center mb-4">
    <i data-lucide="<?= ($order['status'] ?? '') === 'paid' ? 'check-circle' : 'clock' ?>" class="w-8 h-8"></i>
  </div>
  <h1 class="text-xl font-bold text-slate-800"><?= ($order['status'] ?? '') === 'paid' ? 'ชำระและเปิดสิทธิ์แล้ว' : 'ส่งคำสั่งซื้อแล้ว' ?></h1>
  <p class="text-sm text-slate-600 mt-2">เลขที่คำสั่งซื้อ</p>
  <p class="font-mono font-bold text-lg text-primary-700 mt-1"><?= e($order['order_no']) ?></p>
  <p class="text-xs text-slate-500 mt-4">แพ็กเกจ <?= e($order['plan_code'] ?? '') ?> · สถานะ <strong><?= e($order['status']) ?></strong></p>
  <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
    <a href="<?= url('/owner/membership') ?>" class="inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl bg-primary-600 text-white font-semibold hover:bg-primary-700">ไปหน้าสมาชิก</a>
    <a href="<?= url('/owner/dashboard') ?>" class="inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl border border-slate-300 font-semibold text-slate-700 hover:bg-slate-50">แดชบอร์ด</a>
  </div>
</section>
