<?php
/** @var array|null $plan */
$isEdit = !empty($plan);
$action = $isEdit ? url('/admin/membership/plans/' . (int)$plan['id']) : url('/admin/membership/plans');
?>
<a href="<?= url('/admin/membership/plans') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับรายการแพ็กเกจ</a>

<form method="post" action="<?= $action ?>" class="max-w-xl bg-white rounded-2xl border border-slate-200 shadow-soft p-6 space-y-4">
  <?= csrf() ?>

  <?php if ($isEdit): ?>
    <div>
      <label class="text-sm font-medium text-slate-700 mb-1 block">รหัสแพ็กเกจ</label>
      <input type="text" readonly value="<?= e($plan['code']) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 font-mono text-sm">
      <p class="text-xs text-slate-500 mt-1">แก้รหัสไม่ได้ — สร้างแพ็กเกจใหม่ถ้าต้องการรหัสใหม่</p>
    </div>
  <?php else: ?>
    <div>
      <label class="text-sm font-medium text-slate-700 mb-1 block">รหัสแพ็กเกจ <span class="text-rose-500">*</span></label>
      <input type="text" name="code" required maxlength="40" value="<?= e(old('code')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono uppercase" placeholder="เช่น VIP_TRIAL">
    </div>
  <?php endif; ?>

  <div>
    <label class="text-sm font-medium text-slate-700 mb-1 block">Tier</label>
    <select name="tier" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white">
      <?php $t = old('tier', $plan['tier'] ?? 'standard'); ?>
      <option value="standard" <?= $t === 'standard' ? 'selected' : '' ?>>standard — Starter (CRM, Automation, AI, Broadcast, Analytics, คูปอง)</option>
      <option value="vip" <?= $t === 'vip' ? 'selected' : '' ?>>vip — Pro (+ Lead หาที่พัก, Boost ค้นหา/แพว่าง)</option>
    </select>
    <p class="text-xs text-slate-500 mt-1">เลือก tier ให้ตรงสิทธิ์ที่ต้องการเปิด — ดูตารางเปรียบเทียบด้านล่าง</p>
  </div>

  <div class="flex items-center gap-3">
    <label class="inline-flex items-center gap-2 cursor-pointer text-sm">
      <input type="checkbox" name="is_lifetime" value="1" id="mp_life" <?= old('is_lifetime', !empty($plan['is_lifetime']) ? '1' : '') === '1' ? 'checked' : '' ?> class="rounded border-slate-300">
      <span>ตลอดชีพ</span>
    </label>
  </div>

  <div id="mp_days_wrap">
    <label class="text-sm font-medium text-slate-700 mb-1 block">ระยะเวลา (วัน)</label>
    <input type="number" name="duration_days" min="1" max="36500" value="<?= e(old('duration_days', $plan['duration_days'] ?? '30')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
    <p class="text-xs text-slate-500 mt-1">ใช้เมื่อไม่ใช่ตลอดชีพ</p>
  </div>

  <div>
    <label class="text-sm font-medium text-slate-700 mb-1 block">ราคา (บาท)</label>
    <input type="number" name="price" step="0.01" min="0" required value="<?= e(old('price', $plan['price'] ?? '0')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
  </div>

  <div>
    <label class="text-sm font-medium text-slate-700 mb-1 block">ลำดับแสดง</label>
    <input type="number" name="sort_order" value="<?= e(old('sort_order', $plan['sort_order'] ?? '0')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
  </div>

  <label class="inline-flex items-center gap-2 cursor-pointer text-sm">
    <input type="checkbox" name="is_active" value="1" <?= old('is_active', ($plan['is_active'] ?? 1) ? '1' : '') === '1' ? 'checked' : '' ?> class="rounded border-slate-300">
    <span>เปิดการขาย</span>
  </label>

  <button type="submit" class="w-full py-3 bg-accent-500 hover:bg-accent-600 text-white rounded-xl font-bold"><?= $isEdit ? 'บันทึกแพ็กเกจ' : 'สร้างแพ็กเกจ' ?></button>
</form>

<div class="max-w-xl mt-8">
  <?php $compact = true; require __DIR__ . '/../../partials/membership_tier_comparison.php'; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var cb = document.getElementById('mp_life');
  var wrap = document.getElementById('mp_days_wrap');
  function sync() {
    if (!wrap) return;
    wrap.style.opacity = cb && cb.checked ? '0.5' : '1';
    wrap.querySelectorAll('input').forEach(function (inp) { inp.disabled = cb && cb.checked; });
  }
  if (cb) cb.addEventListener('change', sync);
  sync();
});
</script>
