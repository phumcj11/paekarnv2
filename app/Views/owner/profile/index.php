<?php /** @var array $user @var ?array $owner */ ?>

<form method="post" action="<?= url('/owner/profile') ?>" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <?= csrf() ?>

  <div class="lg:col-span-2 space-y-4">

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h3 class="font-bold flex items-center gap-2 mb-4"><i data-lucide="user" class="w-5 h-5 text-accent-600"></i> ข้อมูลส่วนตัว</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm font-medium mb-1 block">ชื่อ-นามสกุล</label>
          <input type="text" name="name" value="<?= e($user['name']) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">เบอร์โทร</label>
          <input type="tel" name="phone" value="<?= e($user['phone']) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">อีเมล (ใช้เข้าระบบ)</label>
          <input type="email" value="<?= e($user['email']) ?>" disabled class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-500">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">รหัสผ่านใหม่ (ปล่อยว่างถ้าไม่เปลี่ยน)</label>
          <input type="password" name="password" minlength="8" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h3 class="font-bold flex items-center gap-2 mb-4"><i data-lucide="building" class="w-5 h-5 text-accent-600"></i> ข้อมูลกิจการ</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm font-medium mb-1 block">ชื่อกิจการ / ที่พัก</label>
          <input type="text" name="business_name" value="<?= e($owner['business_name'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">เลขประจำตัวผู้เสียภาษี</label>
          <input type="text" name="tax_id" value="<?= e($owner['tax_id'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div class="md:col-span-2">
          <label class="text-sm font-medium mb-1 block">หมายเหตุ</label>
          <textarea name="notes" rows="2" class="w-full px-3 py-2 rounded-lg border border-slate-300"><?= e($owner['notes'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h3 class="font-bold flex items-center gap-2 mb-4"><i data-lucide="landmark" class="w-5 h-5 text-accent-600"></i> บัญชีธนาคาร</h3>
      <p class="text-xs text-slate-500 mb-4">ข้อมูลนี้ใช้สำหรับโอนค่าจองให้คุณ (เฉพาะเจ้าหน้าที่เห็น)</p>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <label class="text-sm font-medium mb-1 block">ธนาคาร</label>
          <select name="bank_name" class="w-full px-3 py-2 rounded-lg border border-slate-300">
            <option value="">— เลือก —</option>
            <?php foreach (['SCB','KBANK','BBL','KTB','BAY','TTB','GSB','BAAC','UOB','CIMB','LH','TISCO','KKP'] as $bk): ?>
              <option value="<?= $bk ?>" <?= ($owner['bank_name'] ?? '')===$bk?'selected':'' ?>><?= $bk ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">เลขบัญชี</label>
          <input type="text" name="bank_account" value="<?= e($owner['bank_account'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">ชื่อบัญชี</label>
          <input type="text" name="bank_holder" value="<?= e($owner['bank_holder'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
      </div>
    </div>

  </div>

  <aside>
    <div class="lg:sticky lg:top-24 space-y-4">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
        <button class="w-full py-2.5 bg-accent-500 hover:bg-accent-600 text-white font-semibold rounded-lg inline-flex items-center justify-center gap-2"><i data-lucide="save" class="w-4 h-4"></i> บันทึก</button>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-2 text-sm">
        <h4 class="font-bold flex items-center gap-2"><i data-lucide="badge-check" class="w-5 h-5 text-accent-600"></i> สถานะพาร์ทเนอร์</h4>
        <?php $st = $owner['partner_status'] ?? 'pending'; $sc = ['pending'=>'amber','active'=>'emerald','paused'=>'slate','terminated'=>'rose'][$st] ?? 'slate'; ?>
        <div>
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-<?= $sc ?>-100 text-<?= $sc ?>-700 text-xs font-semibold">
            <i data-lucide="circle" class="w-2 h-2 fill-current"></i> <?= e($st) ?>
          </span>
        </div>
        <div class="text-xs text-slate-500">
          <?php if ($st === 'pending'): ?>
            บัญชีอยู่ระหว่างการตรวจสอบ จะใช้งานได้เต็มรูปแบบหลังอนุมัติ
          <?php elseif ($st === 'active'): ?>
            ✓ ได้รับอนุมัติแล้ว สามารถใช้งานได้เต็มรูปแบบ
          <?php endif; ?>
        </div>
        <hr class="my-2">
        <div class="text-xs">% ส่วนลดที่ตกลง: <strong><?= number_format(($owner['discount_agreement'] ?? 0), 2) ?>%</strong></div>
        <div class="text-xs">% Commission: <strong><?= number_format(($owner['commission_rate'] ?? 0), 2) ?>%</strong></div>
      </div>

      <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 text-xs text-blue-700">
        <i data-lucide="info" class="w-4 h-4 inline"></i> ข้อมูลธนาคารใช้สำหรับโอนเงินเท่านั้น มีการเข้ารหัสและจะไม่แสดงต่อสาธารณะ
      </div>
    </div>
  </aside>
</form>
