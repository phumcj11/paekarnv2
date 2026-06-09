<?php
/** @var array $owner @var array $properties */
$partnerStatus = (string) ($owner['partner_status'] ?? 'pending');
$partnerBadgeClass = match ($partnerStatus) {
    'pending'     => 'bg-amber-100 text-amber-800',
    'active'      => 'bg-emerald-100 text-emerald-800',
    'paused'      => 'bg-slate-100 text-slate-700',
    'terminated'  => 'bg-rose-100 text-rose-800',
    default       => 'bg-slate-100 text-slate-700',
};
$placeholderCover = 'https://placehold.co/96x96?text=Paekan';
?>
<a href="<?= url('/admin/owners') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> ทั้งหมด</a>
<div class="flex flex-wrap gap-2 mb-4">
  <a href="<?= url('/admin/owners/' . $owner['id'] . '/edit') ?>" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm inline-flex items-center gap-1.5"><i data-lucide="pencil" class="w-4 h-4"></i> แก้ไข</a>
  <form method="post" action="<?= url('/admin/owners/' . $owner['id'] . '/delete') ?>" class="inline" onsubmit="return confirm('ลบเจ้าของแพและบัญชีผู้ใช้นี้ — ที่พักเดิมจะไม่มีเจ้าของชั่วคราว — ยืนยัน?');"><?= csrf() ?>
    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm inline-flex items-center gap-1.5"><i data-lucide="trash-2" class="w-4 h-4"></i> ลบ</button>
  </form>
</div>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
    <div class="w-16 h-16 rounded-full bg-primary-100 text-primary-700 grid place-items-center font-bold text-2xl"><?= e(str_first_char((string) ($owner['name'] ?? ''))) ?></div>
    <h2 class="mt-3 font-bold text-lg"><?= e($owner['name']) ?></h2>
    <p class="text-sm text-slate-500"><?= e($owner['business_name'] ?? '-') ?></p>
    <hr class="my-3">
    <div class="space-y-1.5 text-sm">
      <div><i data-lucide="mail" class="w-4 h-4 inline text-slate-400"></i> <?= e($owner['email']) ?></div>
      <div><i data-lucide="phone" class="w-4 h-4 inline text-slate-400"></i> <?= e($owner['phone']) ?></div>
      <?php if (!empty($owner['line_id'])): ?>
      <div><i data-lucide="message-circle" class="w-4 h-4 inline text-slate-400"></i> LINE: <?= e($owner['line_id']) ?></div>
      <?php endif; ?>
      <?php if (isset($owner['wants_sales_help']) && (int)$owner['wants_sales_help'] === 1): ?>
      <div class="text-amber-800 font-medium"><i data-lucide="phone-call" class="w-4 h-4 inline"></i> สนใจให้ทีมช่วยขาย / โปรคูปอง</div>
      <?php endif; ?>
      <?php if (!empty($owner['bank_name']) || !empty($owner['bank_account'])): ?>
      <div><i data-lucide="landmark" class="w-4 h-4 inline text-slate-400"></i> <?= e($owner['bank_name'] ?? '-') ?> — <?= e($owner['bank_account'] ?? '-') ?></div>
      <?php endif; ?>
      <?php if (!empty($owner['bank_holder'])): ?>
      <div><i data-lucide="user" class="w-4 h-4 inline text-slate-400"></i> <?= e($owner['bank_holder']) ?></div>
      <?php endif; ?>
      <div><i data-lucide="percent" class="w-4 h-4 inline text-slate-400"></i> ส่วนลดที่ตกลง: <?= format_percent($owner['discount_agreement'] ?? 0) ?>% · คอมมิชชัน: <?= format_percent($owner['commission_rate'] ?? 0) ?>%</div>
    </div>
    <hr class="my-3">
    <div class="text-sm space-y-1">
      <div class="text-xs text-slate-500 mb-1">สมาชิกเจ้าของแพ</div>
      <div><span class="font-semibold"><?= e($owner['membership_tier'] ?? 'none') ?></span>
        <?php if (!empty($owner['membership_expires_at'])): ?>
          <span class="text-slate-600"> · หมด <?= e(format_date_th($owner['membership_expires_at'])) ?></span>
        <?php elseif (($owner['membership_tier'] ?? 'none') !== 'none'): ?>
          <span class="text-emerald-700"> · ไม่มีวันหมด (ถือเป็นตลอดชีพตามระบบ)</span>
        <?php endif; ?>
      </div>
      <?php if (!empty($owner['membership_grace_until'])): ?>
        <div class="text-xs text-amber-700">Grace ถึง <?= e(format_date_th($owner['membership_grace_until'])) ?></div>
      <?php endif; ?>
    </div>
    <hr class="my-3">
    <div class="text-sm">
      <div class="text-xs text-slate-500 mb-1">สถานะพาร์ทเนอร์</div>
      <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold <?= e($partnerBadgeClass) ?>"><i data-lucide="circle" class="w-2 h-2 fill-current"></i> <?= e($partnerStatus) ?></span>
    </div>
    <form method="post" action="<?= url('/admin/owners/' . $owner['id'] . '/status') ?>" class="mt-3 space-y-2">
      <?= csrf() ?>
      <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
        <?php foreach (['pending','active','paused','terminated'] as $s): ?>
          <option value="<?= $s ?>" <?= $partnerStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
      <button class="w-full py-2 bg-primary-600 text-white rounded-lg text-sm font-semibold inline-flex items-center justify-center gap-1.5"><i data-lucide="check" class="w-4 h-4"></i> อัปเดตสถานะ</button>
    </form>
  </div>

  <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
    <h3 class="font-bold mb-3">ที่พักทั้งหมด (<?= count($properties) ?>)</h3>
    <?php if ($properties === []): ?>
      <p class="text-sm text-slate-500 py-6 text-center">ยังไม่มีที่พักในระบบ</p>
    <?php else: ?>
    <div class="space-y-2">
    <?php foreach ($properties as $p):
        $coverSrc = upload_url(isset($p['cover_image']) ? (string) $p['cover_image'] : '');
        if ($coverSrc === '') {
            $coverSrc = $placeholderCover;
        }
        ?>
      <a href="<?= url('/admin/properties/' . (int) $p['id']) ?>" class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50">
        <img src="<?= e($coverSrc) ?>" alt="" class="w-12 h-12 rounded-lg object-cover bg-slate-100" loading="lazy">
        <div class="flex-1 min-w-0">
          <div class="font-semibold truncate"><?= e($p['name']) ?></div>
          <div class="text-xs text-slate-500"><?= e($p['zone'] ?? '') ?> · <?= e($p['status'] ?? '') ?></div>
        </div>
        <div class="text-sm font-bold text-primary-700 shrink-0"><?= format_money($p['min_price'] ?? 0) ?></div>
      </a>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
