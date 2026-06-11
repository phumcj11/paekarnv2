<?php
/**
 * @var array[]  $properties
 * @var int      $propertyId
 * @var array    $templates    [event_type => row]
 * @var array    $eventTypes   [event_type => meta]
 * @var bool     $hasTable
 */
$colorMap = [
    'emerald' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'icon' => 'text-emerald-600', 'badge' => 'bg-emerald-100 text-emerald-800'],
    'blue'    => ['bg' => 'bg-blue-50',    'border' => 'border-blue-200',    'icon' => 'text-blue-600',    'badge' => 'bg-blue-100 text-blue-800'],
    'amber'   => ['bg' => 'bg-amber-50',   'border' => 'border-amber-200',   'icon' => 'text-amber-600',   'badge' => 'bg-amber-100 text-amber-800'],
    'rose'    => ['bg' => 'bg-rose-50',    'border' => 'border-rose-200',    'icon' => 'text-rose-600',    'badge' => 'bg-rose-100 text-rose-800'],
    'violet'  => ['bg' => 'bg-violet-50',  'border' => 'border-violet-200',  'icon' => 'text-violet-600',  'badge' => 'bg-violet-100 text-violet-800'],
    'teal'    => ['bg' => 'bg-teal-50',    'border' => 'border-teal-200',    'icon' => 'text-teal-600',    'badge' => 'bg-teal-100 text-teal-800'],
];
$saveUrl = url('/owner/automation/save');
?>

<?php if (!$hasTable): ?>
<div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-amber-800 text-sm">
  <div class="flex items-center gap-2 font-bold mb-1">
    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
    ยังไม่ได้รัน migration
  </div>
  รัน <code class="bg-amber-100 px-1.5 py-0.5 rounded font-mono text-xs">scripts/migrate_message_templates.sh</code> ก่อนเพื่อเปิดใช้งาน Automation
</div>
<?php else: ?>

<!-- Property selector -->
<?php if (count($properties) > 1): ?>
<div class="mb-5">
  <form method="get" action="<?= url('/owner/automation') ?>" class="flex items-center gap-2">
    <label class="text-sm font-semibold text-slate-600">ที่พัก:</label>
    <select name="property_id" onchange="this.form.submit()"
            class="px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-accent-400 bg-white">
      <?php foreach ($properties as $pr): ?>
        <option value="<?= (int)$pr['id'] ?>" <?= (int)$pr['id'] === $propertyId ? 'selected' : '' ?>>
          <?= e($pr['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
</div>
<?php endif; ?>

<?php if (!$propertyId): ?>
<div class="text-center py-20 text-slate-400">
  <i data-lucide="settings" class="w-12 h-12 mx-auto mb-3 opacity-30"></i>
  <p class="text-sm">เลือกที่พักเพื่อตั้งค่า Automation</p>
</div>
<?php else: ?>

<!-- Intro card -->
<div class="bg-gradient-to-r from-violet-50 to-white border border-violet-200 rounded-2xl p-5 mb-6">
  <h2 class="font-bold text-slate-800 flex items-center gap-2 mb-1.5">
    <i data-lucide="zap" class="w-5 h-5 text-violet-500"></i>
    ส่งข้อความ LINE อัตโนมัติ
  </h2>
  <p class="text-sm text-slate-600 leading-relaxed">
    ตั้งค่าข้อความที่ระบบจะส่งให้ลูกค้าโดยอัตโนมัติในแต่ละสถานการณ์
    ใช้ตัวแปร <code class="bg-violet-100 text-violet-800 px-1 rounded text-xs">&#123;&#123;guest_name&#125;&#125;</code>
    <code class="bg-violet-100 text-violet-800 px-1 rounded text-xs">&#123;&#123;property_name&#125;&#125;</code>
    <code class="bg-violet-100 text-violet-800 px-1 rounded text-xs">&#123;&#123;check_in_date&#125;&#125;</code>
    <code class="bg-violet-100 text-violet-800 px-1 rounded text-xs">&#123;&#123;booking_code&#125;&#125;</code>
    <code class="bg-violet-100 text-violet-800 px-1 rounded text-xs">&#123;&#123;review_url&#125;&#125;</code>
    ในข้อความ
  </p>
</div>

<!-- Cron dry-run preview -->
<?php if ($propertyId): ?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 mb-5"
     x-data="{ loading: false, preview: null, error: '', today: '' }">
  <div class="flex items-center justify-between mb-3">
    <h3 class="font-bold text-slate-800 flex items-center gap-2">
      <i data-lucide="clock" class="w-4 h-4 text-slate-500"></i>
      ดูตัวอย่าง — ข้อความที่จะส่งวันนี้
    </h3>
    <button type="button"
            @click="loading=true; preview=null; error='';
                    fetch('<?= url('/owner/automation/cron-preview?property_id=' . $propertyId) ?>').then(r=>r.json()).then(d=>{ loading=false; if(d.ok){ preview=d.preview; today=d.today; } else error=d.error||'ผิดพลาด'; }).catch(()=>{ loading=false; error='ไม่สามารถเชื่อมต่อ'; })"
            class="px-3 py-1.5 bg-slate-700 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition flex items-center gap-1.5"
            :disabled="loading">
      <i data-lucide="play" class="w-3.5 h-3.5"></i>
      <span x-text="loading ? 'กำลังโหลด...' : 'Dry-run'"></span>
    </button>
  </div>
  <p class="text-xs text-slate-500 mb-3">ระบบแสดงการจองที่จะถูกส่งข้อความอัตโนมัติวันนี้ (ไม่มีการส่งจริง)</p>
  <div x-show="preview !== null">
    <p class="text-xs text-slate-400 mb-2" x-text="'วันที่ ' + today"></p>
    <template x-if="preview.length === 0">
      <div class="text-sm text-slate-400 italic py-2">ไม่มีการจองที่จะ trigger วันนี้</div>
    </template>
    <template x-if="preview.length > 0">
      <div class="divide-y divide-slate-100 rounded-xl border border-slate-200 overflow-hidden text-sm">
        <template x-for="(item, i) in preview" :key="i">
          <div class="flex items-start gap-3 px-4 py-3 bg-slate-50/50">
            <i data-lucide="send" class="w-4 h-4 text-emerald-500 mt-0.5 shrink-0"></i>
            <div class="min-w-0">
              <div class="font-semibold text-slate-700" x-text="item.label"></div>
              <div class="text-xs text-slate-500 mt-0.5">
                #<span x-text="item.booking_code"></span>
                · <span x-text="item.guest_name"></span>
                · <span x-text="item.date"></span>
              </div>
            </div>
            <span class="ml-auto shrink-0 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-700 self-center" x-text="item.status"></span>
          </div>
        </template>
      </div>
    </template>
  </div>
  <div x-show="error" class="text-sm text-rose-600 bg-rose-50 rounded-xl p-3 border border-rose-100" x-text="error"></div>
</div>
<?php endif; ?>

<!-- AI Campaign from availability -->
<?php if ($propertyId): ?>
<div class="bg-white rounded-2xl border border-teal-200 shadow-soft p-5 mb-5"
     x-data="{ loading: false, text: '', error: '' }">
  <div class="flex items-center justify-between mb-3">
    <h3 class="font-bold text-slate-800 flex items-center gap-2">
      <i data-lucide="calendar-plus" class="w-4 h-4 text-teal-600"></i>
      AI สร้าง Campaign จากวันว่าง
    </h3>
    <button type="button"
            @click="loading=true; text=''; error='';
                    fetch('<?= url('/owner/automation/ai-campaign?property_id=' . $propertyId) ?>').then(r=>r.json()).then(d=>{ loading=false; if(d.ok) text=d.text; else error=d.error||'ผิดพลาด'; }).catch(()=>{ loading=false; error='ไม่สามารถเชื่อมต่อ'; })"
            class="px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold rounded-xl transition flex items-center gap-1.5"
            :disabled="loading">
      <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
      <span x-text="loading ? 'กำลังสร้าง...' : 'สร้างข้อความ'"></span>
    </button>
  </div>
  <p class="text-xs text-slate-500 mb-3">AI จะดูวันว่างใน 30 วันข้างหน้า แล้วร่างข้อความโปรโมทให้ใช้กับ LINE broadcast</p>
  <div x-show="text" class="space-y-2">
    <textarea class="w-full px-3 py-2 rounded-xl border border-teal-300 text-sm resize-none bg-teal-50" rows="4" x-model="text"></textarea>
    <a :href="'<?= url('/owner/line-contacts?property_id=' . $propertyId) ?>'"
       class="inline-flex items-center gap-1.5 text-xs text-teal-700 font-semibold hover:underline">
      <i data-lucide="send" class="w-3.5 h-3.5"></i>
      ไปที่ LINE Contacts เพื่อ Broadcast
    </a>
  </div>
  <div x-show="error" class="text-sm text-rose-600 bg-rose-50 rounded-xl p-3 border border-rose-100" x-text="error"></div>
</div>
<?php endif; ?>

<!-- Template cards -->
<div class="space-y-4">
  <?php foreach ($eventTypes as $eventType => $meta):
    $saved      = $templates[$eventType] ?? null;
    $enabled    = (int)($saved['is_enabled'] ?? 0);
    $text       = $saved['message_text'] ?? $meta['default'];
    $delayHrs   = (int)($saved['send_delay_hours'] ?? 0);
    $c          = $colorMap[$meta['color']] ?? $colorMap['emerald'];
  ?>
  <div x-data="automationCard(<?= json_encode([
    'propertyId'  => $propertyId,
    'eventType'   => $eventType,
    'enabled'     => (bool)$enabled,
    'text'        => $text,
    'defaultText' => $meta['default'],
    'delayHours'  => $delayHrs,
    'saveUrl'     => $saveUrl,
  ], JSON_UNESCAPED_UNICODE) ?>)"
       class="bg-white rounded-2xl border <?= $c['border'] ?> shadow-soft overflow-hidden">

    <!-- Header bar -->
    <div class="flex items-center gap-4 p-5 <?= $enabled ? $c['bg'] : '' ?>">
      <div class="w-10 h-10 rounded-xl <?= $c['bg'] ?> border <?= $c['border'] ?> grid place-items-center shrink-0">
        <i data-lucide="<?= e($meta['icon']) ?>" class="w-5 h-5 <?= $c['icon'] ?>"></i>
      </div>
      <div class="flex-1 min-w-0">
        <div class="font-bold text-slate-800 text-sm"><?= e($meta['label']) ?></div>
        <div class="text-xs text-slate-500 mt-0.5"><?= e($meta['description']) ?></div>
      </div>

      <!-- Toggle switch -->
      <div class="flex items-center gap-3 shrink-0">
        <span class="text-xs font-semibold" :class="enabled ? '<?= $c['icon'] ?>' : 'text-slate-400'" x-text="enabled ? 'เปิดอยู่' : 'ปิดอยู่'"></span>
        <button type="button" @click="toggleEnabled()"
                class="relative w-11 h-6 rounded-full transition-colors duration-200 focus:outline-none"
                :class="enabled ? 'bg-[#06C755]' : 'bg-slate-200'">
          <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200"
                :class="enabled ? 'translate-x-5' : 'translate-x-0'"></span>
        </button>
      </div>
    </div>

    <!-- Message editor (shown when expanded) -->
    <div x-show="expanded || enabled" x-cloak class="px-5 pb-5 pt-3 border-t border-slate-100 space-y-3">
      <div class="flex items-center justify-between">
        <label class="text-xs font-semibold text-slate-600">ข้อความ</label>
        <button type="button" @click="text = defaultText"
                class="text-xs text-slate-400 hover:text-slate-600 flex items-center gap-1">
          <i data-lucide="rotate-ccw" class="w-3 h-3"></i> รีเซ็ตเป็นค่าเริ่มต้น
        </button>
      </div>
      <textarea x-model="text" rows="5" maxlength="2000"
                class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm resize-y focus:border-[#06C755] outline-none font-mono leading-relaxed"
                :class="enabled ? '' : 'bg-slate-50 text-slate-500'"></textarea>

      <!-- Delay hours -->
      <div class="flex items-center gap-3">
        <label class="text-xs font-semibold text-slate-600 shrink-0">ส่งหลัง event</label>
        <div class="flex items-center gap-1.5">
          <input type="number" x-model.number="delayHours" min="0" max="720"
                 class="w-20 px-2 py-1.5 rounded-lg border border-slate-200 text-sm text-center outline-none focus:border-[#06C755]">
          <span class="text-xs text-slate-500">ชั่วโมง</span>
        </div>
        <span class="text-[10px] text-slate-400" x-show="delayHours === 0">ส่งทันที</span>
        <span class="text-[10px] text-slate-400" x-show="delayHours > 0" x-text="'(หน่วงเวลา ' + delayHours + ' ชม.)'"></span>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <button type="button" @click="aiDraft()"
                :disabled="aiLoading"
                class="inline-flex items-center gap-2 px-3 py-1.5 bg-violet-100 hover:bg-violet-200 text-violet-700 text-xs font-semibold rounded-lg transition disabled:opacity-60">
          <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
          <span x-show="!aiLoading">AI ช่วยเขียน</span>
          <span x-show="aiLoading">กำลังร่าง…</span>
        </button>
        <div x-show="aiLoading === false && false" x-cloak class="flex items-center gap-1">
          <input type="text" x-model="aiContext" maxlength="200" placeholder="บอก AI เพิ่มเติม เช่น 'เน้นการเช็คอิน 14:00'"
                 class="w-48 px-2 py-1 rounded-lg border border-violet-200 text-xs outline-none focus:border-violet-400 bg-violet-50">
        </div>
        <button type="button" @click="save()"
                :disabled="saving"
                class="inline-flex items-center gap-2 px-4 py-2 bg-[#06C755] hover:bg-[#04a844] text-white text-sm font-semibold rounded-xl transition disabled:opacity-60">
          <i data-lucide="save" class="w-4 h-4"></i>
          <span x-show="!saving">บันทึก</span>
          <span x-show="saving">กำลังบันทึก…</span>
        </button>
        <p x-show="saveMsg" x-text="saveMsg"
           :class="saveOk ? 'text-emerald-700' : 'text-rose-600'"
           class="text-xs"></p>
        <button type="button" @click="expanded = !expanded"
                class="ml-auto text-xs text-slate-400 hover:text-slate-600">
          <span x-text="expanded ? '▲ ย่อ' : '▼ ขยาย'"></span>
        </button>
      </div>
    </div>

    <!-- Collapsed (not enabled, not expanded) -->
    <div x-show="!expanded && !enabled" class="px-5 py-2.5 border-t border-slate-100">
      <button type="button" @click="expanded = true"
              class="text-xs text-slate-400 hover:text-slate-600 flex items-center gap-1">
        <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
        ดู/แก้ไขข้อความ
      </button>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php endif; ?>
<?php endif; ?>

<script>
function automationCard(cfg) {
  return {
    propertyId:  cfg.propertyId,
    eventType:   cfg.eventType,
    enabled:     cfg.enabled,
    text:        cfg.text,
    defaultText: cfg.defaultText,
    delayHours:  cfg.delayHours ?? 0,
    expanded:    false,
    saving:      false,
    saveMsg:     '',
    saveOk:      true,
    aiLoading:   false,
    aiContext:   '',

    toggleEnabled() {
      this.enabled = !this.enabled;
      this.expanded = this.enabled;
      this.save();
    },

    async aiDraft() {
      if (this.aiLoading) return;
      this.aiLoading = true;
      try {
        const fd = new FormData();
        fd.set('property_id', String(this.propertyId));
        fd.set('event_type',  this.eventType);
        fd.set('context',     this.aiContext);
        const r = await fetch('<?= url('/owner/automation/ai-draft') ?>', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.ok && d.text) {
          this.text     = d.text;
          this.expanded = true;
          this.saveMsg  = '✨ AI ร่างข้อความแล้ว — ตรวจสอบและบันทึก';
          this.saveOk   = true;
          setTimeout(() => this.saveMsg = '', 4000);
        } else {
          this.saveMsg = d.error || 'AI ไม่พร้อม';
          this.saveOk  = false;
        }
      } catch(e) { this.saveOk = false; this.saveMsg = 'เกิดข้อผิดพลาด'; }
      this.aiLoading = false;
    },

    async save() {
      this.saving = true;
      this.saveMsg = '';
      try {
        const fd = new FormData();
        fd.set('property_id',      String(this.propertyId));
        fd.set('event_type',       this.eventType);
        fd.set('is_enabled',       this.enabled ? '1' : '0');
        fd.set('message_text',     this.text);
        fd.set('send_delay_hours', String(Math.max(0, parseInt(this.delayHours) || 0)));
        const r = await fetch('<?= $saveUrl ?>', { method: 'POST', body: fd });
        const d = await r.json();
        this.saveOk  = !!d.ok;
        this.saveMsg = d.ok ? 'บันทึกแล้ว ✓' : (d.error || 'บันทึกไม่สำเร็จ');
        if (d.ok) setTimeout(() => this.saveMsg = '', 2000);
      } catch(e) { this.saveOk = false; this.saveMsg = 'เกิดข้อผิดพลาด'; }
      this.saving = false;
    },
  };
}
</script>
