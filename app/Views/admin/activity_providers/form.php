<?php
/** @var ?array<string,mixed> $provider */
/** @var array<string,string> $types */
/** @var list<string> $districtChoices */
/** @var list<string> $zoneChoices */

use App\Models\ActivityProvider as AP;

$isEdit = !empty($provider);
$action = $isEdit ? url('/admin/activity-providers/' . $provider['id']) : url('/admin/activity-providers');
$oldInput = \App\Core\Session::get('_old', []);
$typeVal = (string)($oldInput['type'] ?? ($provider['type'] ?? 'tour_operator'));
$districtVal = (string)($oldInput['district'] ?? ($provider['district'] ?? ''));
$zoneVal = (string)($oldInput['zone'] ?? ($provider['zone'] ?? ''));
$commissionType = (string)($oldInput['commission_type'] ?? ($provider['commission_type'] ?? 'percent'));
$status = (string)($oldInput['status'] ?? ($provider['status'] ?? 'active'));
?>
<a href="<?= url('/admin/activity-providers') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</a>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="max-w-4xl space-y-4">
  <?= csrf() ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="md:col-span-2">
        <label class="text-sm font-medium mb-1 block">ชื่อผู้ให้บริการ</label>
        <input type="text" name="name" required maxlength="180" value="<?= old('name', $provider['name'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium mb-1 block">โลโก้ / รูปผู้ให้บริการ</label>
        <p class="text-xs text-slate-500 mb-2">JPG, PNG, WebP — แนะนำสี่เหลี่ยมจัตุรัสหรือแนวนอน ไม่เกิน 5 MB</p>
        <?php if (!empty($provider['logo_image'])): ?>
          <img src="<?= e(upload_url($provider['logo_image'])) ?>" alt="" class="mb-3 rounded-xl border border-slate-200 max-h-40 object-contain bg-slate-50">
          <label class="inline-flex items-center gap-2 text-sm text-slate-600 mb-2">
            <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300"> ลบรูปปัจจุบัน
          </label>
        <?php endif; ?>
        <input type="url" name="logo_image_url" placeholder="URL รูป (ถ้ามี)" value="<?= old('logo_image_url', '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm mb-2">
        <input type="file" name="logo_image" accept="image/*" class="w-full text-sm">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">ประเภท</label>
        <select name="type" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <?php foreach ($types as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= $typeVal === $k ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">สถานะ</label>
        <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>เปิดใช้งาน</option>
          <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>ปิดใช้งาน</option>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">ชื่อผู้ติดต่อ</label>
        <input type="text" name="contact_name" value="<?= old('contact_name', $provider['contact_name'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">เบอร์โทร</label>
        <input type="text" name="phone" value="<?= old('phone', $provider['phone'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">LINE ID / URL</label>
        <input type="text" name="line_id" value="<?= old('line_id', $provider['line_id'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">อีเมล</label>
        <input type="email" name="email" value="<?= old('email', $provider['email'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">อำเภอ</label>
        <select name="district" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <option value="">— ไม่ระบุ —</option>
          <?php foreach ($districtChoices as $d): ?>
            <option value="<?= e($d) ?>" <?= $districtVal === $d ? 'selected' : '' ?>><?= e($d) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">โซน</label>
        <select name="zone" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <option value="">— ไม่ระบุ —</option>
          <?php foreach ($zoneChoices as $z): ?>
            <option value="<?= e($z) ?>" <?= $zoneVal === $z ? 'selected' : '' ?>><?= e($z) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium mb-1 block">ที่อยู่</label>
        <textarea name="address" rows="2" class="w-full px-3 py-2 rounded-lg border border-slate-300"><?= old('address', $provider['address'] ?? '') ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">รูปแบบคอมมิชชัน</label>
        <select name="commission_type" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <option value="percent" <?= $commissionType === 'percent' ? 'selected' : '' ?>>เปอร์เซ็นต์</option>
          <option value="fixed" <?= $commissionType === 'fixed' ? 'selected' : '' ?>>จำนวนเงินคงที่</option>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">ค่าคอมมิชชัน</label>
        <input type="number" name="commission_value" min="0" step="0.01" value="<?= old('commission_value', $provider['commission_value'] ?? '0') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium mb-1 block">หมายเหตุภายใน</label>
        <textarea name="notes" rows="3" class="w-full px-3 py-2 rounded-lg border border-slate-300"><?= old('notes', $provider['notes'] ?? '') ?></textarea>
      </div>
    </div>
  </div>
  <button class="px-6 py-2.5 bg-accent-500 hover:bg-accent-600 text-white rounded-xl font-semibold">บันทึก</button>
</form>

<?php if ($isEdit): ?>
<?php
/** @var array<string,mixed>|null $subscription */
/** @var array<string,string> $planOptions */
$planOptions = $planOptions ?? [];
$sub = $subscription ?? null;
?>
<?php if ($planOptions !== []): ?>
<div class="max-w-4xl mt-6 bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
  <h3 class="font-bold text-lg mb-3">แพ็ก Provider (manual)</h3>
  <p class="text-xs text-slate-500 mb-4">กำหนดแพ็กรายเดือน / คอมลด — ชำระนอกระบบ บันทึกที่นี่</p>
  <?php if ($sub): ?>
  <div class="mb-4 rounded-xl bg-teal-50 border border-teal-200 p-3 text-sm">
    แพ็กปัจจุบัน: <strong><?= e($planOptions[$sub['plan_key']] ?? $sub['plan_key']) ?></strong>
    <?php if ($sub['commission_override'] !== null): ?> · คอม <?= e((string)$sub['commission_override']) ?>%<?php endif; ?>
    <?php if (!empty($sub['ends_at'])): ?> · ถึง <?= e((string)$sub['ends_at']) ?><?php endif; ?>
  </div>
  <?php endif; ?>
  <form method="post" action="<?= url('/admin/activity-providers/' . $provider['id'] . '/subscription') ?>" class="grid grid-cols-1 md:grid-cols-2 gap-4"><?= csrf() ?>
    <div>
      <label class="text-sm font-medium block mb-1">แพ็ก</label>
      <select name="plan_key" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        <?php foreach ($planOptions as $k => $label): ?>
          <option value="<?= e($k) ?>" <?= ($sub['plan_key'] ?? 'partner') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-sm font-medium block mb-1">คอม override (%)</label>
      <input type="number" name="commission_override" min="0" max="100" step="0.01" value="<?= e((string)($sub['commission_override'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="ว่าง = ใช้ค่า product">
    </div>
    <div>
      <label class="text-sm font-medium block mb-1">ราคาที่เก็บ (฿)</label>
      <input type="number" name="price_paid" min="0" step="0.01" value="<?= e((string)($sub['price_paid'] ?? '0')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
    </div>
    <div>
      <label class="text-sm font-medium block mb-1">Featured slots</label>
      <input type="number" name="featured_slots" min="0" step="1" value="<?= e((string)($sub['featured_slots'] ?? '0')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
    </div>
    <div>
      <label class="text-sm font-medium block mb-1">เริ่ม</label>
      <input type="date" name="starts_at" value="<?= !empty($sub['starts_at']) ? e(date('Y-m-d', strtotime((string)$sub['starts_at']))) : '' ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
    </div>
    <div>
      <label class="text-sm font-medium block mb-1">สิ้นสุด</label>
      <input type="date" name="ends_at" value="<?= !empty($sub['ends_at']) ? e(date('Y-m-d', strtotime((string)$sub['ends_at']))) : '' ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
    </div>
    <div class="md:col-span-2">
      <label class="text-sm font-medium block mb-1">หมายเหตุ</label>
      <input type="text" name="subscription_notes" maxlength="500" value="<?= e((string)($sub['notes'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
    </div>
    <div class="md:col-span-2 flex flex-wrap gap-2">
      <button type="submit" class="px-5 py-2 bg-teal-600 text-white rounded-lg text-sm font-semibold">บันทึกแพ็ก</button>
      <?php if ($sub): ?>
      <button type="submit" name="clear_subscription" value="1" class="px-5 py-2 border border-slate-300 rounded-lg text-sm" onclick="return confirm('ยกเลิกแพ็กปัจจุบัน?')">ยกเลิกแพ็ก</button>
      <?php endif; ?>
    </div>
  </form>
</div>
<?php endif; ?>
<?php
$partnerStatus = (string)($provider['partner_status'] ?? 'active');
?>
<div class="max-w-4xl mt-6 bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
  <h3 class="font-bold text-lg mb-3">สถานะพาร์ทเนอร์</h3>
  <p class="text-sm text-slate-600 mb-4">ปัจจุบัน: <strong><?= e(AP::partnerStatusLabel($partnerStatus)) ?></strong>
    <?php if (!empty($provider['user_id'])): ?> · บัญชี login user #<?= (int)$provider['user_id'] ?><?php endif; ?>
  </p>
  <div class="flex flex-wrap gap-2">
    <?php foreach (['pending' => 'รออนุมัติ', 'active' => 'อนุมัติ / ใช้งาน', 'paused' => 'พักชั่วคราว', 'terminated' => 'ยกเลิก'] as $st => $lab): ?>
      <?php if ($st === $partnerStatus) continue; ?>
      <form method="post" action="<?= url('/admin/activity-providers/' . $provider['id'] . '/partner-status') ?>" onsubmit="return confirm('เปลี่ยนสถานะเป็น <?= e($lab) ?>?')"><?= csrf() ?>
        <input type="hidden" name="partner_status" value="<?= e($st) ?>">
        <button class="px-4 py-2 rounded-lg text-sm font-medium border border-slate-200 hover:bg-slate-50"><?= e($lab) ?></button>
      </form>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
