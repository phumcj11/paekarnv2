<?php
use App\Core\Session;
use App\Models\Property;
use App\Models\Setting;

/** @var array<string,string> $types */
/** @var list<string> $districts */
$err = Session::flash('error');
$siteName = (string) Setting::get('site_name', 'แพกาญ.com');
$zoneChoices = Property::zonesForSelect();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>สมัครผู้ให้บริการ — <?= e($siteName) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script src="https://unpkg.com/lucide@latest"></script>
<style>body{font-family:'Sarabun',system-ui}</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-teal-50 via-white to-slate-100 px-4 py-8">
  <div class="max-w-2xl mx-auto">
    <div class="text-center mb-6">
      <div class="w-14 h-14 mx-auto rounded-2xl bg-teal-600 text-white grid place-items-center">
        <i data-lucide="handshake" class="w-7 h-7"></i>
      </div>
      <h1 class="mt-3 text-2xl font-bold text-slate-800">สมัครเป็นผู้ให้บริการ</h1>
      <p class="text-sm text-slate-600 mt-1">รถเช่า · ทัวร์ · เรือ · ไกด์ — ลงสินค้าและรับออเดอร์เองหลังอนุมัติ</p>
    </div>

    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-6 sm:p-7">
      <?php if ($err): ?>
        <div class="mb-4 px-4 py-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-lg text-sm flex items-center gap-2">
          <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i><?= e($err) ?>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= url('/provider/register') ?>" class="space-y-3" id="providerRegisterForm">
        <?= csrf() ?>
        <?= \App\Services\RegistrationSpamGuard::hiddenFields() ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="text-sm font-medium mb-1 block">ชื่อ-นามสกุล <span class="text-rose-500">*</span></label>
            <input type="text" name="name" required value="<?= old('name') ?>" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
          </div>
          <div>
            <label class="text-sm font-medium mb-1 block">เบอร์โทร <span class="text-rose-500">*</span></label>
            <input type="tel" name="phone" required value="<?= old('phone') ?>" inputmode="tel" autocomplete="tel"
                   placeholder="0812345678" pattern="0[689][0-9]{8}"
                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
          </div>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">ชื่อธุรกิจ / ร้าน <span class="text-rose-500">*</span></label>
          <input type="text" name="business_name" required value="<?= old('business_name') ?>" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="text-sm font-medium mb-1 block">ประเภทบริการ</label>
            <select name="type" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
              <?php foreach ($types as $k => $lab): ?>
                <option value="<?= e($k) ?>" <?= old('type', 'car_rental') === $k ? 'selected' : '' ?>><?= e($lab) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="text-sm font-medium mb-1 block">อำเภอ</label>
            <select name="district" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
              <option value="">— เลือก —</option>
              <?php foreach ($districts as $d): ?>
                <option value="<?= e($d) ?>" <?= old('district') === $d ? 'selected' : '' ?>><?= e($d) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">LINE ID</label>
          <input type="text" name="line_id" value="<?= old('line_id') ?>" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">อีเมล (ใช้เข้าระบบ) <span class="text-rose-500">*</span></label>
          <input type="email" name="email" required value="<?= old('email') ?>" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="text-sm font-medium mb-1 block">รหัสผ่าน <span class="text-rose-500">*</span></label>
            <input type="password" name="password" required minlength="8" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
          </div>
          <div>
            <label class="text-sm font-medium mb-1 block">ยืนยันรหัสผ่าน <span class="text-rose-500">*</span></label>
            <input type="password" name="password_confirm" required minlength="8" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
          </div>
        </div>
        <p class="text-xs text-slate-500">หลังสมัคร ทีมงานจะตรวจสอบและอนุมัติบัญชีก่อนเปิดใช้งานเต็มรูปแบบ</p>
        <button type="submit" id="providerRegisterSubmit" class="w-full py-3 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-xl">สมัครเป็นพาร์ทเนอร์</button>
        <div class="text-center text-sm text-slate-600">
          มีบัญชีแล้ว? <a href="<?= url('/provider/login') ?>" class="text-teal-600 font-semibold hover:underline">เข้าสู่ระบบ</a>
        </div>
      </form>
    </div>
  </div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  lucide.createIcons();
  const form = document.getElementById('providerRegisterForm');
  const btn = document.getElementById('providerRegisterSubmit');
  if (form && btn) {
    form.addEventListener('submit', () => {
      btn.disabled = true;
      btn.textContent = 'กำลังส่ง...';
    });
  }
});
</script>
</body>
</html>
