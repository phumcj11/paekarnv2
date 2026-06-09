<?php
use App\Models\ContentPlan;
$statusColors = ContentPlan::STATUS_COLORS;
$statusLabels = ContentPlan::STATUS_LABELS;
$platformLabels = ContentPlan::PLATFORM_LABELS;
$platformIcons = ['facebook' => '📘', 'line' => '💚', 'instagram' => '📸', 'other' => '📣'];

$prevUrl = url('/owner/content-plans?month=' . sprintf('%04d-%02d', $prevMonth['year'], $prevMonth['month']));
$nextUrl = url('/owner/content-plans?month=' . sprintf('%04d-%02d', $nextMonth['year'], $nextMonth['month']));
$todayY  = (int)date('Y');
$todayM  = (int)date('n');
$todayD  = (int)date('j');

// Build calendar cells
$cells = array_fill(0, $firstDow - 1, null); // empty leading cells (Mon=1 → 0 empties)
for ($d = 1; $d <= $daysInMonth; $d++) $cells[] = $d;
// Pad to complete last row
while (count($cells) % 7 !== 0) $cells[] = null;
?>

<div x-data="contentPlanApp()" x-init="init()" class="space-y-5">

  <!-- Header row -->
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="text-xl font-bold text-slate-800">ปฏิทินโพสต์การตลาด</h1>
      <p class="text-sm text-slate-500 mt-0.5">วางแผนและสร้างโพสต์ด้วย AI สำหรับที่พักของคุณ</p>
    </div>
    <button type="button" @click="openCreate()" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 hover:bg-primary-700 px-4 py-2 text-sm font-semibold text-white shadow transition">
      <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มโพสต์ใหม่
    </button>
  </div>

  <!-- Month stats -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    <?php
    $statDef = [
      ['draft',     '✏️', 'ร่าง'],
      ['scheduled', '🕐', 'ตั้งเวลา'],
      ['published', '✅', 'โพสต์แล้ว'],
      ['cancelled', '❌', 'ยกเลิก'],
    ];
    foreach ($statDef as [$key, $emoji, $label]):
    ?>
    <div class="bg-white rounded-xl border border-slate-200 p-3 flex items-center gap-3">
      <span class="text-2xl"><?= $emoji ?></span>
      <div>
        <div class="text-xl font-bold text-slate-800"><?= (int)($counts[$key] ?? 0) ?></div>
        <div class="text-xs text-slate-500"><?= $label ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Calendar nav -->
  <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100">
      <a href="<?= e($prevUrl) ?>" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-500 transition">
        <i data-lucide="chevron-left" class="w-5 h-5"></i>
      </a>
      <h2 class="text-base font-bold text-slate-800"><?= e($monthLabel) ?></h2>
      <a href="<?= e($nextUrl) ?>" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-500 transition">
        <i data-lucide="chevron-right" class="w-5 h-5"></i>
      </a>
    </div>

    <!-- Day headers -->
    <div class="grid grid-cols-7 border-b border-slate-100">
      <?php foreach (['จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส', 'อา'] as $d): ?>
      <div class="text-center text-xs font-semibold text-slate-400 py-2"><?= $d ?></div>
      <?php endforeach; ?>
    </div>

    <!-- Calendar cells -->
    <div class="grid grid-cols-7 divide-x divide-y divide-slate-100">
      <?php foreach ($cells as $day): ?>
      <?php
        if ($day === null) {
          echo '<div class="min-h-[80px] bg-slate-50/60"></div>';
          continue;
        }
        $dateStr  = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $isToday  = ($year === $todayY && $month === $todayM && $day === $todayD);
        $dayPlans = $calMap[$dateStr] ?? [];
      ?>
      <div class="min-h-[80px] p-1.5 relative <?= $isToday ? 'bg-primary-50/70' : 'hover:bg-slate-50' ?> cursor-pointer transition group"
           @click="openCreate(<?= htmlspecialchars(json_encode($dateStr), ENT_QUOTES) ?>)">
        <div class="flex items-center justify-between mb-1">
          <span class="text-xs font-semibold <?= $isToday ? 'w-6 h-6 rounded-full bg-primary-600 text-white grid place-items-center' : 'text-slate-600' ?>">
            <?= $day ?>
          </span>
          <?php if (!empty($dayPlans)): ?>
          <span class="text-[10px] text-slate-400"><?= count($dayPlans) ?></span>
          <?php endif; ?>
        </div>
        <?php foreach (array_slice($dayPlans, 0, 3) as $plan): ?>
        <div class="text-[10px] rounded px-1 py-0.5 mb-0.5 truncate <?= $statusColors[$plan['status']] ?> cursor-pointer"
             @click.stop="openEdit(<?= (int)$plan['id'] ?>)"
             title="<?= e($plan['title'] ?: mb_substr($plan['body'], 0, 40)) ?>">
          <?= e($platformIcons[$plan['platform']] ?? '📣') ?>
          <?= e(mb_substr($plan['title'] ?: $plan['body'], 0, 18)) ?>
        </div>
        <?php endforeach; ?>
        <?php if (count($dayPlans) > 3): ?>
        <div class="text-[9px] text-slate-400 text-center">+<?= count($dayPlans) - 3 ?> เพิ่มเติม</div>
        <?php endif; ?>
        <div class="absolute inset-0 border-2 border-primary-400 rounded pointer-events-none opacity-0 group-hover:opacity-40 transition"></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Recent list -->
  <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
      <h3 class="font-semibold text-slate-700">รายการโพสต์เดือนนี้</h3>
      <span class="text-xs text-slate-400"><?= count($plans) ?> รายการ</span>
    </div>
    <?php if (empty($plans)): ?>
    <div class="p-8 text-center text-slate-400 text-sm">ยังไม่มีโพสต์ในเดือนนี้ — กด "เพิ่มโพสต์ใหม่" เพื่อเริ่ม</div>
    <?php else: ?>
    <div class="divide-y divide-slate-100">
      <?php foreach ($plans as $plan): ?>
      <div class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 transition">
        <div class="w-8 h-8 rounded-lg bg-slate-100 grid place-items-center text-base shrink-0 mt-0.5">
          <?= $platformIcons[$plan['platform']] ?? '📣' ?>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs font-bold text-slate-700"><?= e(date('j M', strtotime($plan['post_date']))) ?></span>
            <span class="text-xs px-2 py-0.5 rounded-full <?= $statusColors[$plan['status']] ?>"><?= $statusLabels[$plan['status']] ?></span>
            <?php if ($plan['ai_generated']): ?><span class="text-[10px] px-1.5 py-0.5 rounded bg-violet-100 text-violet-700">✨ AI</span><?php endif; ?>
            <?php if ($plan['property_name']): ?><span class="text-[10px] text-slate-400 truncate max-w-[120px]"><?= e($plan['property_name']) ?></span><?php endif; ?>
          </div>
          <?php if ($plan['title']): ?>
          <div class="text-sm font-semibold text-slate-800 mt-0.5 truncate"><?= e($plan['title']) ?></div>
          <?php endif; ?>
          <div class="text-xs text-slate-500 mt-0.5 line-clamp-2"><?= e($plan['body']) ?></div>
        </div>
        <button type="button" @click="openEdit(<?= (int)$plan['id'] ?>)"
                class="shrink-0 p-1.5 rounded-lg text-slate-400 hover:text-primary-600 hover:bg-primary-50 transition">
          <i data-lucide="pencil" class="w-4 h-4"></i>
        </button>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- Modal -->
<div x-show="modal" x-cloak x-transition.opacity
     class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
     @click.self="modal=false">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto"
       @click.stop>
    <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-100">
      <h2 class="font-bold text-slate-800" x-text="editId ? 'แก้ไขโพสต์' : 'เพิ่มโพสต์ใหม่'"></h2>
      <button type="button" @click="modal=false" class="text-slate-400 hover:text-slate-700 p-1"><i data-lucide="x" class="w-5 h-5"></i></button>
    </div>

    <form @submit.prevent="save()" class="p-5 space-y-4">

      <!-- Date + Platform -->
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">วันที่โพสต์</label>
          <input type="date" x-model="form.post_date" required
                 class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-100">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Platform</label>
          <select x-model="form.platform"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-100">
            <?php foreach (ContentPlan::PLATFORM_LABELS as $val => $lbl): ?>
            <option value="<?= e($val) ?>"><?= e($lbl) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Property -->
      <?php if (!empty($properties)): ?>
      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">ที่พัก <span class="font-normal text-slate-400">(ไม่จำเป็น)</span></label>
        <select x-model="form.property_id"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-100">
          <option value="">— ทั้งหมด / ไม่ระบุ —</option>
          <?php foreach ($properties as $p): ?>
          <option value="<?= (int)$p['id'] ?>" data-name="<?= e($p['name']) ?>"><?= e($p['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <!-- AI Generate -->
      <div class="rounded-xl border border-violet-200 bg-violet-50 p-3 space-y-2">
        <div class="flex items-center gap-2">
          <i data-lucide="sparkles" class="w-4 h-4 text-violet-500 shrink-0"></i>
          <span class="text-xs font-semibold text-violet-700">AI ช่วยเขียนโพสต์</span>
        </div>
        <input type="text" x-model="aiPrompt" placeholder="เช่น: โปรโมชั่นวันหยุด, แนะนำวิวสวยๆ, รับกลุ่มใหญ่"
               class="w-full rounded-lg border border-violet-200 bg-white px-3 py-2 text-sm focus:border-violet-400 focus:outline-none">
        <button type="button" @click="aiGenerate()" :disabled="aiLoading"
                class="w-full flex items-center justify-center gap-2 rounded-lg bg-violet-600 hover:bg-violet-700 disabled:opacity-50 px-3 py-2 text-sm font-semibold text-white transition">
          <i data-lucide="wand-sparkles" class="w-4 h-4" x-show="!aiLoading"></i>
          <i data-lucide="loader-circle" class="w-4 h-4 animate-spin" x-show="aiLoading" x-cloak></i>
          <span x-text="aiLoading ? 'กำลังสร้าง...' : 'สร้างด้วย AI'"></span>
        </button>
        <div x-show="aiError" x-cloak class="text-xs text-red-600 mt-1" x-text="aiError"></div>
      </div>

      <!-- Title -->
      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">หัวข้อโพสต์ <span class="font-normal text-slate-400">(ไม่จำเป็น)</span></label>
        <input type="text" x-model="form.title" maxlength="200" placeholder="หัวข้อสั้นๆ สำหรับอ้างอิงในแอป"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-100">
      </div>

      <!-- Body -->
      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">เนื้อหาโพสต์ <span class="text-red-500">*</span></label>
        <textarea x-model="form.body" rows="5" required placeholder="เนื้อหาที่จะโพสต์..."
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-100 resize-y"></textarea>
      </div>

      <!-- Hashtags -->
      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Hashtags</label>
        <input type="text" x-model="form.hashtags" placeholder="#แพกาญ #กาญจนบุรี #ที่พัก"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-100">
      </div>

      <!-- Status -->
      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">สถานะ</label>
        <div class="flex flex-wrap gap-2">
          <?php foreach (ContentPlan::STATUSES as $s): ?>
          <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="radio" x-model="form.status" value="<?= e($s) ?>" class="accent-primary-600">
            <span class="text-sm text-slate-700"><?= $statusLabels[$s] ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between gap-3 pt-2">
        <template x-if="editId">
          <button type="button" @click="deletePlan()"
                  class="inline-flex items-center gap-1.5 text-sm text-red-500 hover:text-red-700 transition">
            <i data-lucide="trash-2" class="w-4 h-4"></i> ลบ
          </button>
        </template>
        <template x-if="!editId"><div></div></template>
        <div class="flex gap-2">
          <button type="button" @click="modal=false"
                  class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition">ยกเลิก</button>
          <button type="submit" :disabled="saving"
                  class="px-5 py-2 text-sm rounded-lg bg-primary-600 hover:bg-primary-700 text-white font-semibold shadow transition disabled:opacity-50">
            <span x-text="saving ? 'กำลังบันทึก...' : (editId ? 'บันทึก' : 'เพิ่มโพสต์')"></span>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
const __CP_PLANS__  = <?= json_encode(array_values($plans), JSON_UNESCAPED_UNICODE) ?>;
const __CP_PROPS__  = <?= json_encode(array_values($properties ?? []), JSON_UNESCAPED_UNICODE) ?>;
const __CP_ENDPOINTS__ = {
  store:      '<?= e(url('/owner/content-plans')) ?>',
  update:     '<?= e(url('/owner/content-plans')) ?>/{id}/update',
  destroy:    '<?= e(url('/owner/content-plans')) ?>/{id}/delete',
  aiGenerate: '<?= e(url('/owner/content-plans/ai-generate')) ?>',
};

function contentPlanApp() {
  return {
    modal: false,
    editId: null,
    saving: false,
    aiLoading: false,
    aiPrompt: '',
    aiError: '',
    form: {
      post_date: '', platform: 'facebook', property_id: '',
      title: '', body: '', hashtags: '', status: 'draft',
    },
    plans: __CP_PLANS__,
    props: __CP_PROPS__,

    init() {
      if (window.lucide) lucide.createIcons();
    },

    _resetForm(date) {
      this.form = {
        post_date: date || new Date().toISOString().slice(0, 10),
        platform: 'facebook', property_id: '',
        title: '', body: '', hashtags: '', status: 'draft',
      };
      this.aiPrompt = '';
      this.aiError  = '';
    },

    openCreate(date) {
      this.editId = null;
      this._resetForm(date || '');
      this.modal = true;
      this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    openEdit(id) {
      const p = this.plans.find(x => x.id == id);
      if (!p) return;
      this.editId = id;
      this.form = {
        post_date:   p.post_date,
        platform:    p.platform,
        property_id: p.property_id || '',
        title:       p.title || '',
        body:        p.body || '',
        hashtags:    p.hashtags || '',
        status:      p.status,
      };
      this.aiPrompt = '';
      this.aiError  = '';
      this.modal = true;
      this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    async save() {
      if (this.saving) return;
      this.saving = true;
      try {
        const fd = new FormData();
        for (const [k, v] of Object.entries(this.form)) { if (v !== '') fd.append(k, v); }

        let url = this.editId
          ? __CP_ENDPOINTS__.update.replace('{id}', this.editId)
          : __CP_ENDPOINTS__.store;

        const r = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' });
        const j = await r.json();
        if (!j.ok) { alert(j.error || 'เกิดข้อผิดพลาด'); return; }

        // Reload page to refresh calendar
        window.location.reload();
      } catch (e) {
        alert('เชื่อมต่อไม่สำเร็จ');
      } finally {
        this.saving = false;
      }
    },

    async deletePlan() {
      if (!this.editId) return;
      if (!confirm('ลบโพสต์นี้?')) return;
      const url = __CP_ENDPOINTS__.destroy.replace('{id}', this.editId);
      await fetch(url, { method: 'POST', credentials: 'same-origin' });
      window.location.reload();
    },

    async aiGenerate() {
      if (this.aiLoading) return;
      this.aiLoading = true;
      this.aiError = '';
      try {
        const fd = new FormData();
        fd.append('platform',  this.form.platform);
        fd.append('prompt',    this.aiPrompt);
        fd.append('post_date', this.form.post_date);

        // Find property info
        const propId = this.form.property_id;
        if (propId) {
          const el = document.querySelector(`[data-name][value="${propId}"]`);
          if (el) fd.append('property_name', el.dataset.name || '');
        }

        const r = await fetch(__CP_ENDPOINTS__.aiGenerate, { method: 'POST', body: fd, credentials: 'same-origin' });
        const j = await r.json();
        if (j.ok) {
          if (j.title)    this.form.title    = j.title;
          if (j.body)     this.form.body     = j.body;
          if (j.hashtags) this.form.hashtags = j.hashtags;
          this.form.status = 'draft';
          // Mark as AI generated on save
          this.$nextTick(() => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'ai_generated';
            hiddenInput.value = '1';
            this._aiGenerated = true;
          });
        } else {
          this.aiError = j.error || 'AI ไม่สามารถสร้างได้ในขณะนี้';
        }
      } catch (e) {
        this.aiError = 'เชื่อมต่อไม่สำเร็จ';
      } finally {
        this.aiLoading = false;
        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
      }
    },
  };
}
</script>
