<?php
/** @var array $property @var array $units @var array $lineContacts @var int $blockedDays @var int $bookedDays */
$pid = (int)$property['id'];
$lineOn = !empty($property['line_messaging_enabled']);
?>

<div class="flex items-center justify-between mb-4">
  <a href="<?= url('/owner/properties/' . $pid . '/edit') ?>" class="text-sm text-slate-500 hover:text-accent-700 inline-flex items-center gap-1">
    <i data-lucide="arrow-left" class="w-4 h-4"></i> กลับแก้ไขที่พัก
  </a>
  <h2 class="font-bold flex items-center gap-2">
    <svg class="w-5 h-5 text-[#06C755]" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.03 2 11c0 2.98 1.6 5.6 4.08 7.27L5.5 22l4.15-2.05A10.94 10.94 0 0 0 12 20c5.52 0 10-4.03 10-9S17.52 2 12 2z"/></svg>
    LINE & Chatbot — <?= e($property['name']) ?>
  </h2>
  <div></div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
  <!-- ปฏิทินวันว่าง (ข้อมูลที่ลูกค้าเห็นใน LINE) -->
  <div class="xl:col-span-1 space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h3 class="font-bold flex items-center gap-2 mb-2">
        <i data-lucide="calendar-days" class="w-5 h-5 text-accent-600"></i> ปฏิทินวันว่าง
      </h3>
      <p class="text-xs text-slate-500 mb-4 leading-relaxed">
        ตารางที่ลูกค้าเห็นเมื่อกด <strong>«เช็ควันว่าง»</strong> ใน LINE มาจากข้อมูลที่นี่ + การจองจริง
        — ถ้าไม่ตั้งค่า ระบบถือว่า <span class="text-emerald-700 font-semibold">ว่าง</span> จนกว่าจะมีจองครบหรือปิดวัน
      </p>

      <?php if (empty($units)): ?>
      <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3 mb-3">ยังไม่มีห้อง/ยูนิต — เพิ่มก่อนจึงจะเช็ควันว่างได้</p>
      <a href="<?= url('/owner/properties/' . $pid . '/units/create') ?>" class="inline-flex items-center gap-1.5 px-4 py-2 bg-accent-500 text-white text-sm font-semibold rounded-xl">
        <i data-lucide="plus-circle" class="w-4 h-4"></i> เพิ่มห้องพัก
      </a>
      <?php else: ?>
      <div class="grid grid-cols-2 gap-2 mb-4 text-center text-xs">
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3">
          <div class="text-lg font-bold text-emerald-700"><?= count($units) ?></div>
          <div class="text-slate-600">ยูนิตเปิดใช้</div>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3">
          <div class="text-lg font-bold text-amber-700"><?= $bookedDays ?></div>
          <div class="text-slate-600">วันมีจอง (เดือนนี้)</div>
        </div>
      </div>
      <a href="<?= url('/owner/properties/' . $pid . '/availability') ?>"
         class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-accent-600 hover:bg-accent-700 text-white font-semibold rounded-xl transition">
        <i data-lucide="calendar" class="w-5 h-5"></i> จัดการปฏิทินวันว่าง
      </a>
      <p class="text-[11px] text-slate-400 mt-2 text-center">คลิกวัน → ปิด/เปิดได้ทีละหลายวัน</p>
      <?php endif; ?>
    </div>

    <div class="bg-slate-50 rounded-2xl border border-slate-200 p-4 text-xs text-slate-600 space-y-2">
      <div class="font-semibold text-slate-700">Chatbot ตอบอะไรได้บ้าง</div>
      <div>✅ ราคา · ที่อยู่ · เช็คอิน · ดูห้อง · จอง</div>
      <div>✅ ตารางวันว่าง (กดจาก Rich Menu)</div>
      <div>✅ ถามเป็นข้อความ เช่น «เสาร์นี้ ว่างไหม 4 คน»</div>
    </div>
  </div>

  <!-- LINE OA Settings -->
  <div class="xl:col-span-2">
    <form id="lineHubForm" method="post" action="<?= url('/owner/properties/' . $pid . '/line') ?>"
          x-data="lineOASettings()" class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
      <?= csrf() ?>

      <div class="flex items-center justify-between">
        <h3 class="font-bold">LINE Official Account</h3>
        <?php if ($lineOn): ?>
        <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full">เปิดใช้งาน</span>
        <?php else: ?>
        <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full">ปิดอยู่</span>
        <?php endif; ?>
      </div>

      <label class="flex items-center gap-3 cursor-pointer">
        <input type="checkbox" name="line_messaging_enabled" value="1"
               <?= $lineOn ? 'checked' : '' ?> x-model="enabled" class="rounded accent-[#06C755]">
        <span class="text-sm font-semibold">เปิดใช้ LINE OA สำหรับที่พักนี้</span>
      </label>

      <div x-show="enabled" x-cloak class="space-y-3">
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Channel Access Token</label>
          <input type="text" name="line_channel_access_token"
                 value="<?= e($property['line_channel_access_token'] ?? '') ?>"
                 class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Channel Secret</label>
          <input type="text" name="line_channel_secret"
                 value="<?= e($property['line_channel_secret'] ?? '') ?>"
                 class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm font-mono">
        </div>
        <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 text-xs">
          <div class="font-semibold text-slate-600 mb-1">Webhook URL (ใส่ใน LINE Developers)</div>
          <code class="text-primary-700 select-all break-all"><?= url('/line/property/' . $pid . '/webhook') ?></code>
        </div>

        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-accent-600 hover:bg-accent-700 text-white text-sm font-semibold rounded-xl">
          <i data-lucide="save" class="w-4 h-4"></i> บันทึกการตั้งค่า LINE
        </button>

        <div class="border-t border-slate-100 pt-4">
          <div class="text-xs font-semibold text-slate-600 mb-2">Rich Menu (6 ปุ่ม — ขนาดใหญ่)</div>
          <?php if (!empty($property['line_rich_menu_id'])): ?>
          <p class="text-xs text-emerald-700 mb-2">✅ มี Rich Menu: <code class="bg-slate-100 px-1 rounded text-[10px]"><?= e($property['line_rich_menu_id']) ?></code></p>
          <?php endif; ?>
          <div class="flex flex-wrap gap-2">
            <button type="button" @click="richMenuAction('create')" :disabled="rmBusy"
                    class="px-4 py-2 bg-[#06C755] hover:bg-[#05a847] text-white text-xs font-semibold rounded-lg disabled:opacity-50">
              สร้าง / อัปเดต Rich Menu
            </button>
            <button type="button" @click="richMenuAction('delete')" :disabled="rmBusy"
                    class="px-4 py-2 bg-rose-500 text-white text-xs font-semibold rounded-lg disabled:opacity-50">
              ลบ Rich Menu
            </button>
          </div>
          <div x-show="rmResult" x-text="rmResult" :class="rmOk ? 'text-emerald-600' : 'text-rose-600'" class="text-xs mt-1.5"></div>
        </div>

        <div class="border-t border-slate-100 pt-4">
          <div class="text-xs font-semibold text-slate-600 mb-2">ทดสอบส่งข้อความ</div>
          <?php if (!empty($lineContacts)): ?>
          <select @change="if($event.target.value){ testUid=$event.target.value; $event.target.value=''; }"
                  class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs mb-2">
            <option value="">— เลือกลูกค้าที่ทัก OA แล้ว —</option>
            <?php foreach ($lineContacts as $lc): ?>
            <option value="<?= e($lc['line_user_id']) ?>"><?= e(($lc['display_name'] ? $lc['display_name'] . ' — ' : '') . $lc['line_user_id']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php endif; ?>
          <div class="flex gap-2">
            <input type="text" x-model="testUid" placeholder="Uxxxxxxxx..." class="flex-1 px-3 py-2 rounded-lg border text-sm font-mono">
            <button type="button" @click="testPush()" :disabled="pushing"
                    class="px-4 py-2 bg-[#06C755] text-white text-sm font-semibold rounded-lg disabled:opacity-50">ส่งทดสอบ</button>
          </div>
          <div x-show="testResult" x-text="testResult" :class="testOk ? 'text-emerald-600' : 'text-rose-600'" class="text-xs mt-1.5"></div>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function lineOASettings() {
  return {
    enabled: <?= $lineOn ? 'true' : 'false' ?>,
    testUid: '', pushing: false, testResult: '', testOk: false,
    rmBusy: false, rmResult: '', rmOk: false,
    csrf() { return document.querySelector('#lineHubForm [name="_csrf"]')?.value ?? ''; },
    async richMenuAction(action) {
      this.rmBusy = true; this.rmResult = '';
      try {
        const fd = new FormData();
        fd.append('_csrf', this.csrf());
        fd.append('action', action);
        const r = await fetch('<?= url('/owner/properties/' . $pid . '/line-rich-menu') ?>', {method:'POST', body:fd});
        const j = await r.json().catch(() => null);
        this.rmOk = j?.ok ?? false;
        this.rmResult = j?.message ?? (this.rmOk ? 'สำเร็จ' : 'ไม่สำเร็จ');
      } catch(e) { this.rmOk = false; this.rmResult = 'เกิดข้อผิดพลาด'; }
      this.rmBusy = false;
    },
    async testPush() {
      const uid = this.testUid.trim();
      if (!uid || !/^U[0-9a-f]{32}$/i.test(uid)) {
        this.testOk = false; this.testResult = 'กรุณากรอก LINE User ID ที่ถูกต้อง (U...)'; return;
      }
      this.pushing = true;
      const fd = new FormData();
      fd.append('_csrf', this.csrf());
      fd.append('line_user_id', uid);
      const tok = document.querySelector('[name="line_channel_access_token"]')?.value;
      if (tok) fd.append('line_channel_access_token', tok);
      const r = await fetch('<?= url('/owner/properties/' . $pid . '/line-test') ?>', {method:'POST', body:fd});
      const j = await r.json().catch(() => null);
      this.testOk = j?.ok ?? false;
      this.testResult = j?.message ?? 'เกิดข้อผิดพลาด';
      this.pushing = false;
    },
  };
}
</script>
