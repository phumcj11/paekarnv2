<?php
/**
 * @var string   $tab            'calendar'|'groups'|'leads'
 * @var array[]  $properties
 * @var int      $year
 * @var int      $month
 * @var string   $monthLabel
 * @var int      $daysInMonth
 * @var int      $firstDow
 * @var array    $calMap          date→plan[]
 * @var array[]  $plans
 * @var array    $counts
 * @var array    $prevMonth
 * @var array    $nextMonth
 * @var string   $today
 * @var array[]  $groups
 * @var array[]  $leads
 */
use App\Models\ContentPlan;

$statusColors = ContentPlan::STATUS_COLORS;
$statusLabels = ContentPlan::STATUS_LABELS;
$platformLabels = ContentPlan::PLATFORM_LABELS;
$platformIcons  = ['facebook' => '📘', 'line' => '💚', 'instagram' => '📸', 'other' => '📣'];

$prevUrl = url('/owner/content-plans?month=' . sprintf('%04d-%02d', $prevMonth['year'], $prevMonth['month']));
$nextUrl = url('/owner/content-plans?month=' . sprintf('%04d-%02d', $nextMonth['year'], $nextMonth['month']));
$todayY  = (int)date('Y');
$todayM  = (int)date('n');
$todayD  = (int)date('j');

$cells = array_fill(0, $firstDow - 1, null);
for ($d = 1; $d <= $daysInMonth; $d++) $cells[] = $d;
while (count($cells) % 7 !== 0) $cells[] = null;

$leadStatusLabels = ['new' => 'ใหม่', 'replied' => 'ตอบแล้ว', 'got_lead' => 'ได้ lead', 'closed' => 'ปิดการขาย', 'lost' => 'ไม่สำเร็จ'];
$leadStatusColors = [
    'new'      => 'bg-blue-100 text-blue-700',
    'replied'  => 'bg-amber-100 text-amber-700',
    'got_lead' => 'bg-emerald-100 text-emerald-700',
    'closed'   => 'bg-violet-100 text-violet-700',
    'lost'     => 'bg-slate-100 text-slate-500',
];

// Social columns available
$hasSocialCols = !empty($properties) && array_key_exists('instagram_url', $properties[0]);
?>

<!-- Tab bar -->
<div class="flex items-center gap-1 mb-5 bg-white rounded-2xl border border-slate-200 p-1.5 shadow-soft">
  <?php
  $mainTabs = [
    'calendar' => ['icon' => 'calendar-days', 'label' => 'ปฏิทินโพสต์'],
    'groups'   => ['icon' => 'users',         'label' => 'กลุ่ม Facebook'],
    'leads'    => ['icon' => 'target',        'label' => 'หา Lead'],
  ];
  foreach ($mainTabs as $key => $t): ?>
  <a href="<?= url('/owner/content-plans?tab=' . $key . '&month=' . sprintf('%04d-%02d', $year, $month)) ?>"
     class="flex items-center gap-1.5 px-3 py-2 text-sm font-semibold rounded-xl transition whitespace-nowrap flex-1 justify-center sm:flex-none sm:px-4 <?= $tab === $key ? 'bg-primary-600 text-white shadow' : 'text-slate-500 hover:bg-slate-100' ?>">
    <i data-lucide="<?= $t['icon'] ?>" class="w-4 h-4 shrink-0"></i>
    <span class="hidden sm:inline"><?= $t['label'] ?></span>
    <span class="sm:hidden text-xs"><?= ['calendar'=>'โพสต์','groups'=>'กลุ่ม','leads'=>'Lead'][$key] ?></span>
    <?php if ($key === 'leads' && !empty($leads)): ?>
    <span class="<?= $tab === $key ? 'bg-white/30 text-white' : 'bg-blue-100 text-blue-700' ?> text-[10px] font-bold px-1.5 py-0.5 rounded-full">
      <?= count(array_filter($leads, fn($l) => $l['status'] === 'new')) ?>
    </span>
    <?php endif; ?>
  </a>
  <?php endforeach; ?>
  <!-- Settings: icon-only button, clearly visible on all screen sizes -->
  <a href="<?= url('/owner/content-plans?tab=settings&month=' . sprintf('%04d-%02d', $year, $month)) ?>"
     title="ตั้งค่า Social Media"
     class="ml-auto flex items-center gap-1.5 px-3 py-2 text-sm font-semibold rounded-xl transition whitespace-nowrap <?= $tab === 'settings' ? 'bg-primary-600 text-white shadow' : 'text-slate-400 hover:bg-slate-100 hover:text-slate-700' ?>">
    <i data-lucide="settings-2" class="w-4 h-4 shrink-0"></i>
    <span class="hidden sm:inline text-xs">ตั้งค่า</span>
  </a>
</div>

<?php if (empty($hasMarketingTables) && in_array($tab, ['groups', 'leads'], true)): ?>
<div class="mb-4 bg-amber-50 border border-amber-200 rounded-2xl p-4 text-sm text-amber-800">
  ระบบกำลังอัปเดตฐานข้อมูล Marketing — แท็บนี้จะใช้งานได้หลัง deploy เสร็จ (รอประมาณ 2–5 นาที) แท็บปฏิทินโพสต์ใช้ได้ปกติ
</div>
<?php endif; ?>

<?php if ($tab === 'calendar'): ?>
<!-- ═══════════════════════════════════════════════════════
     TAB 1: Calendar + AI Content Planner
═══════════════════════════════════════════════════════ -->
<div x-data="contentPlanApp()" x-init="init()" class="space-y-5">

  <!-- Header -->
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="text-xl font-bold text-slate-800">ปฏิทินโพสต์การตลาด</h1>
      <p class="text-sm text-slate-500 mt-0.5">วางแผนและสร้างโพสต์ด้วย AI สำหรับที่พักของคุณ</p>
    </div>
    <button type="button" @click="openCreate()" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 hover:bg-primary-700 px-4 py-2 text-sm font-semibold text-white shadow transition">
      <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มโพสต์ใหม่
    </button>
  </div>

  <!-- Quick Template Buttons -->
  <div class="bg-white rounded-2xl border border-slate-200 p-4">
    <p class="text-xs font-semibold text-slate-500 mb-3">สร้างด่วน — เลือกธีมแล้ว AI เขียนให้ทันที</p>
    <div class="flex flex-wrap gap-2">
      <?php
      $quickTemplates = [
        ['label' => '📅 โปรวันหยุด',      'prompt' => 'โปรโมชั่นพิเศษช่วงวันหยุด ลดราคาห้องพัก',       'type' => 'page'],
        ['label' => '🛖 ห้องว่างวันนี้',   'prompt' => 'ห้องว่างพร้อมเข้าพักวันนี้/สุดสัปดาห์นี้',       'type' => 'group'],
        ['label' => '👨‍👩‍👧 รับกรุ๊ปใหญ่',    'prompt' => 'เหมาแพรับกลุ่มใหญ่ ครบทุกสิ่งอำนวยความสะดวก', 'type' => 'group'],
        ['label' => '🌅 วิวสวย',           'prompt' => 'แนะนำวิวสวย ถ่ายรูปได้ทุกมุม',                  'type' => 'page'],
        ['label' => '🗺️ ใกล้ที่เที่ยว',    'prompt' => 'ที่พักใกล้แหล่งท่องเที่ยว แพ็กเกจเที่ยว',       'type' => 'page'],
        ['label' => '📣 LINE Broadcast',   'prompt' => 'ข้อความแจ้งเตือนโปรสั้นๆ ส่งทาง LINE',           'type' => 'line_broadcast'],
      ];
      foreach ($quickTemplates as $qt): ?>
      <button type="button"
              data-prompt="<?= e($qt['prompt']) ?>"
              data-type="<?= e($qt['type']) ?>"
              @click="openCreateWithTemplate($el.dataset.prompt, $el.dataset.type)"
              class="px-3 py-2 bg-slate-50 hover:bg-primary-50 hover:text-primary-700 hover:border-primary-200 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 transition">
        <?= e($qt['label']) ?>
      </button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Month stats -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    <?php
    $statDef = [
      ['draft','bg-slate-50 text-slate-600','✏️','ร่าง'],
      ['scheduled','bg-blue-50 text-blue-700','🕐','ตั้งเวลา'],
      ['published','bg-emerald-50 text-emerald-700','✅','โพสต์แล้ว'],
      ['cancelled','bg-rose-50 text-rose-600','❌','ยกเลิก'],
    ];
    foreach ($statDef as [$key, $cls, $emoji, $label]):
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

  <!-- Calendar -->
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
    <div class="grid grid-cols-7 border-b border-slate-100">
      <?php foreach (['จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส', 'อา'] as $d): ?>
      <div class="text-center text-xs font-semibold text-slate-400 py-2"><?= $d ?></div>
      <?php endforeach; ?>
    </div>
    <div class="grid grid-cols-7 divide-x divide-y divide-slate-100">
      <?php foreach ($cells as $day): ?>
      <?php
        if ($day === null) { echo '<div class="min-h-[80px] bg-slate-50/60"></div>'; continue; }
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
    <div class="p-8 text-center text-slate-400 text-sm">ยังไม่มีโพสต์ในเดือนนี้</div>
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

  <!-- ── Content Plan Modal (must stay inside contentPlanApp scope) ── -->
  <div x-show="modal" x-cloak x-transition.opacity
     class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
     @click.self="modal=false">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
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

      <!-- Post Type (for AI) -->
      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">ประเภท (ช่วย AI เขียนให้เหมาะสม)</label>
        <div class="flex flex-wrap gap-2">
          <label class="flex items-center gap-1.5 cursor-pointer px-3 py-1.5 rounded-lg border text-xs font-semibold transition"
                 :class="form.post_type === 'page' ? 'bg-primary-600 text-white border-primary-600' : 'border-slate-300 text-slate-600 hover:bg-slate-50'">
            <input type="radio" x-model="form.post_type" value="page" class="sr-only">
            📘 Facebook Page
          </label>
          <label class="flex items-center gap-1.5 cursor-pointer px-3 py-1.5 rounded-lg border text-xs font-semibold transition"
                 :class="form.post_type === 'group' ? 'bg-primary-600 text-white border-primary-600' : 'border-slate-300 text-slate-600 hover:bg-slate-50'">
            <input type="radio" x-model="form.post_type" value="group" class="sr-only">
            👥 กลุ่ม Facebook
          </label>
          <label class="flex items-center gap-1.5 cursor-pointer px-3 py-1.5 rounded-lg border text-xs font-semibold transition"
                 :class="form.post_type === 'line_broadcast' ? 'bg-[#06C755] text-white border-[#06C755]' : 'border-slate-300 text-slate-600 hover:bg-slate-50'">
            <input type="radio" x-model="form.post_type" value="line_broadcast" class="sr-only">
            💚 LINE Broadcast
          </label>
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
          <span class="text-[10px] text-violet-500 ml-auto" x-text="'สไตล์: ' + (form.post_type === 'group' ? 'กลุ่ม FB' : form.post_type === 'line_broadcast' ? 'LINE Broadcast' : 'Facebook Page')"></span>
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
        <div class="flex items-center gap-2 mt-1.5">
          <button type="button" @click="copyBody()"
                  class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-primary-600 transition px-2 py-1 rounded-lg hover:bg-primary-50">
            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
            <span x-text="copied ? 'คัดลอกแล้ว!' : 'คัดลอกข้อความ'"></span>
          </button>
          <?php if (!empty($properties)): ?>
          <button type="button" @click="openFbPage()"
                  title="เปิด Facebook Page ในแท็บใหม่"
                  class="inline-flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-800 transition px-2 py-1 rounded-lg hover:bg-blue-50">
            <i data-lucide="external-link" class="w-3.5 h-3.5"></i> เปิด FB Page
          </button>
          <?php endif; ?>
        </div>
      </div>

      <!-- Hashtags -->
      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Hashtags</label>
        <input type="text" x-model="form.hashtags" placeholder="#แพกาญ #กาญจนบุรี #ที่พัก"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-100">
      </div>

      <!-- Multi-Image Section -->
      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1.5 flex items-center gap-1.5">
          <i data-lucide="images" class="w-3.5 h-3.5"></i> รูปภาพประกอบโพสต์
          <span class="font-normal text-slate-400">(ไม่จำเป็น · เลือกได้หลายรูป)</span>
        </label>

        <!-- Selected images grid -->
        <template x-if="form.images.length > 0">
          <div class="grid grid-cols-3 gap-2 mb-2">
            <template x-for="(img, idx) in form.images" :key="img.url">
              <div class="relative aspect-square rounded-xl overflow-hidden border-2 border-slate-200 bg-slate-50 group">
                <img :src="img.url" :alt="img.name" class="w-full h-full object-cover">
                <!-- Filename overlay -->
                <div class="absolute inset-x-0 bottom-0 bg-black/60 px-1.5 py-1 text-white text-[9px] truncate">
                  <span x-text="img.name"></span>
                </div>
                <!-- Remove button -->
                <button type="button" @click="removeImage(idx)"
                        class="absolute top-1 right-1 w-5 h-5 bg-red-500 hover:bg-red-600 text-white rounded-full grid place-items-center opacity-0 group-hover:opacity-100 transition">
                  <i data-lucide="x" class="w-3 h-3"></i>
                </button>
                <!-- Check badge -->
                <div class="absolute top-1 left-1 w-4 h-4 bg-emerald-500 rounded-full grid place-items-center">
                  <i data-lucide="check" class="w-2.5 h-2.5 text-white"></i>
                </div>
              </div>
            </template>
            <!-- Upload more slot -->
            <label class="aspect-square rounded-xl border-2 border-dashed border-primary-300 bg-primary-50 hover:bg-primary-100 flex flex-col items-center justify-center cursor-pointer transition"
                   :class="imgUploading ? 'opacity-50 pointer-events-none' : ''">
              <i data-lucide="plus" class="w-5 h-5 text-primary-400 mb-0.5"></i>
              <span class="text-[9px] text-primary-500 font-semibold">เพิ่มรูป</span>
              <input type="file" accept="image/*" multiple class="sr-only" :disabled="imgUploading" @change="uploadFiles($event)">
            </label>
          </div>
        </template>

        <!-- Upload progress -->
        <template x-if="imgUploading">
          <div class="mb-2 flex items-center gap-2 px-3 py-2 bg-primary-50 rounded-lg border border-primary-200">
            <i data-lucide="loader-circle" class="w-4 h-4 text-primary-500 animate-spin shrink-0"></i>
            <span class="text-xs text-primary-700 font-semibold" x-text="'กำลังอัปโหลด ' + imgUploadingCount + ' รูป...'"></span>
          </div>
        </template>

        <!-- Empty state: action buttons -->
        <template x-if="form.images.length === 0 && !imgUploading">
          <div class="flex flex-wrap gap-2 mb-2">
            <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-primary-50 hover:bg-primary-100 text-primary-700 text-xs font-semibold transition cursor-pointer whitespace-nowrap border border-primary-200">
              <i data-lucide="camera" class="w-3.5 h-3.5"></i> อัปโหลดรูป
              <input type="file" accept="image/*" multiple class="sr-only" @change="uploadFiles($event)">
            </label>
            <button type="button" @click="openLibrary()"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-violet-50 hover:bg-violet-100 text-violet-700 text-xs font-semibold transition whitespace-nowrap border border-violet-200">
              <i data-lucide="library-big" class="w-3.5 h-3.5"></i>
              คลังรูป <span class="text-[10px] opacity-70" x-text="'(' + imgLibrary.length + ')'"></span>
            </button>
            <?php if (!empty($properties)): ?>
            <button type="button" @click="openImagePicker()" :disabled="!form.property_id"
                    :class="form.property_id ? 'bg-slate-100 hover:bg-slate-200 text-slate-600 cursor-pointer' : 'bg-slate-50 text-slate-300 cursor-not-allowed'"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition whitespace-nowrap border border-slate-200">
              <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
              <span x-text="form.property_id ? 'รูปที่พัก' : 'เลือกที่พักก่อน'"></span>
            </button>
            <?php endif; ?>
          </div>
        </template>

        <!-- Library panel -->
        <div x-show="imgLibraryOpen" x-cloak class="mb-2 bg-violet-50 rounded-xl border border-violet-200 p-3">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-violet-700 flex items-center gap-1.5">
              <i data-lucide="library-big" class="w-3.5 h-3.5"></i> คลังรูปของฉัน
            </span>
            <div class="flex gap-2">
              <button type="button" @click="clearLibrary()" x-show="imgLibrary.length > 0"
                      class="text-[10px] text-red-400 hover:text-red-600">ล้างทั้งหมด</button>
              <button type="button" @click="imgLibraryOpen=false" class="text-slate-400 hover:text-slate-700">
                <i data-lucide="x" class="w-4 h-4"></i>
              </button>
            </div>
          </div>
          <template x-if="imgLibrary.length === 0">
            <div class="text-xs text-violet-400 text-center py-3">ยังไม่มีรูปในคลัง — อัปโหลดรูปเพื่อบันทึกไว้ใช้ซ้ำ</div>
          </template>
          <div class="grid grid-cols-4 gap-2 max-h-48 overflow-y-auto">
            <template x-for="lib in imgLibrary" :key="lib.url">
              <button type="button" @click="addFromLibrary(lib)"
                      :class="form.images.some(i=>i.url===lib.url) ? 'border-primary-500 ring-1 ring-primary-400' : 'border-transparent hover:border-violet-300'"
                      class="relative aspect-square rounded-lg overflow-hidden border-2 transition group">
                <img :src="lib.url" :alt="lib.name" class="w-full h-full object-cover">
                <div x-show="form.images.some(i=>i.url===lib.url)"
                     class="absolute inset-0 bg-primary-600/30 flex items-center justify-center">
                  <i data-lucide="check-circle" class="w-5 h-5 text-white"></i>
                </div>
                <div class="absolute inset-x-0 bottom-0 bg-black/50 px-1 py-0.5 text-white text-[8px] truncate opacity-0 group-hover:opacity-100 transition">
                  <span x-text="lib.name"></span>
                </div>
              </button>
            </template>
          </div>
          <p class="text-[10px] text-violet-400 mt-2">คลังรูปเก็บไว้ในเครื่อง สูงสุด 20 รูป</p>
        </div>

        <!-- Property Image Picker Panel -->
        <div x-show="imgPickerOpen" x-cloak class="mb-2 bg-slate-50 rounded-xl border border-slate-200 p-3">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-600">รูปภาพของที่พัก</span>
            <button type="button" @click="imgPickerOpen=false" class="text-slate-400 hover:text-slate-700">
              <i data-lucide="x" class="w-4 h-4"></i>
            </button>
          </div>
          <template x-if="imgPickerLoading">
            <div class="text-xs text-slate-400 text-center py-4">กำลังโหลดรูป...</div>
          </template>
          <template x-if="!imgPickerLoading && imgPickerImages.length === 0">
            <div class="text-xs text-slate-400 text-center py-4">ไม่มีรูปภาพ — <a href="<?= url('/owner/properties') ?>" class="underline text-primary-600">ไปอัปโหลดรูปที่พัก</a></div>
          </template>
          <div class="grid grid-cols-4 gap-2 max-h-44 overflow-y-auto">
            <template x-for="img in imgPickerImages" :key="img.id">
              <button type="button" @click="addFromPicker(img)"
                      :class="form.images.some(i=>i.url===img.src) ? 'border-primary-500' : 'border-transparent hover:border-primary-300'"
                      class="relative aspect-square rounded-lg overflow-hidden border-2 transition">
                <img :src="img.src" class="w-full h-full object-cover">
                <div x-show="form.images.some(i=>i.url===img.src)"
                     class="absolute inset-0 bg-primary-600/30 flex items-center justify-center">
                  <i data-lucide="check-circle" class="w-5 h-5 text-white"></i>
                </div>
              </button>
            </template>
          </div>
        </div>

        <!-- URL input (manual paste) -->
        <div class="flex gap-2">
          <input type="url" x-model="manualUrl" placeholder="วาง URL รูปภาพ แล้วกด +"
                 @keydown.enter.prevent="addManualUrl()"
                 class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-100">
          <button type="button" @click="addManualUrl()"
                  class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-semibold transition">+</button>
        </div>
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

      <!-- Facebook Post Button (only for existing posts with FB-connected property) -->
      <template x-if="editId && fbPageConnected()">
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-3 space-y-2">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 fill-[#1877F2] shrink-0" viewBox="0 0 24 24"><path d="M24 12.073C24 5.406 18.627 0 12 0S0 5.406 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.514c-1.491 0-1.956.93-1.956 1.885v2.27h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
            <span class="text-sm font-semibold text-blue-800">โพสต์ไปยัง Facebook Page ทันที</span>
            <span class="text-xs text-blue-500 ml-auto" x-text="fbPageName()"></span>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <button type="button" @click="postNow('facebook')" :disabled="socialPosting"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#1877F2] hover:bg-[#166fe5] text-white text-sm font-semibold transition disabled:opacity-50">
              <i data-lucide="send" class="w-3.5 h-3.5" x-show="!socialPosting"></i>
              <i data-lucide="loader-circle" class="w-3.5 h-3.5 animate-spin" x-show="socialPosting" x-cloak></i>
              <span x-text="socialPosting ? 'กำลังโพสต์...' : 'โพสต์เลย'"></span>
            </button>
            <span x-show="socialResult?.platform==='facebook'" x-cloak class="text-xs font-semibold"
                  :class="socialResult?.ok ? 'text-emerald-700' : 'text-red-600'"
                  x-text="socialResult?.ok ? '✓ โพสต์แล้ว' : ('⚠ ' + socialResult?.error)"></span>
            <a x-show="socialResult?.platform==='facebook' && socialResult?.url" x-cloak :href="socialResult?.url" target="_blank" rel="noopener"
               class="text-xs text-blue-600 underline">ดูโพสต์</a>
          </div>
        </div>
      </template>

      <!-- LINE Broadcast -->
      <template x-if="editId && lineConnected()">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 space-y-2">
          <div class="flex items-center gap-2">
            <span class="text-base">💚</span>
            <span class="text-sm font-semibold text-emerald-800">ส่ง Broadcast ไปผู้ติดตาม LINE</span>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <button type="button" @click="postNow('line')" :disabled="socialPosting"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#06C755] hover:bg-[#05b34c] text-white text-sm font-semibold transition disabled:opacity-50">
              <span x-text="socialPosting ? 'กำลังส่ง...' : 'ส่ง Broadcast'"></span>
            </button>
            <span x-show="socialResult?.platform==='line'" x-cloak class="text-xs font-semibold"
                  :class="socialResult?.ok ? 'text-emerald-700' : 'text-red-600'"
                  x-text="socialResult?.ok ? ('✓ ส่ง ' + (socialResult.sent||0) + '/' + (socialResult.total||0) + ' คน') : ('⚠ ' + socialResult?.error)"></span>
          </div>
          <p class="text-[11px] text-emerald-700">ส่งถึงผู้ติดตาม LINE ทุกคนของที่พักนี้ — ใช้ token จาก LINE Hub</p>
        </div>
      </template>

      <!-- Instagram Post -->
      <template x-if="editId && fbPageConnected() && formHasImages()">
        <div class="rounded-xl border border-pink-200 bg-pink-50 p-3 space-y-2">
          <div class="flex items-center gap-2">
            <span class="text-base">📸</span>
            <span class="text-sm font-semibold text-pink-800">โพสต์ไปยัง Instagram (ผ่าน FB Page ที่เชื่อม)</span>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <button type="button" @click="postNow('instagram')" :disabled="socialPosting"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-purple-600 to-pink-500 hover:opacity-90 text-white text-sm font-semibold transition disabled:opacity-50">
              <span x-text="socialPosting ? 'กำลังโพสต์...' : 'โพสต์ Instagram'"></span>
            </button>
            <span x-show="socialResult?.platform==='instagram'" x-cloak class="text-xs font-semibold"
                  :class="socialResult?.ok ? 'text-emerald-700' : 'text-red-600'"
                  x-text="socialResult?.ok ? '✓ โพสต์แล้ว' : ('⚠ ' + socialResult?.error)"></span>
          </div>
          <p class="text-[11px] text-pink-700">ต้องมีรูปอย่างน้อย 1 รูป และ Page ต้องผูก Instagram Business ใน Meta</p>
        </div>
      </template>

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

</div><!-- end contentPlanApp -->

<?php endif; /* end calendar tab */ ?>

<?php if ($tab === 'groups'): ?>
<!-- ═══════════════════════════════════════════════════════
     TAB 2: Group Posting Helper
═══════════════════════════════════════════════════════ -->
<div x-data="groupPostingApp()" x-init="init()" class="space-y-5">

  <!-- Header -->
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="text-xl font-bold text-slate-800">กลุ่ม Facebook</h1>
      <p class="text-sm text-slate-500 mt-0.5">บันทึกกลุ่มที่ใช้ประจำ เลือกโพสต์ → copy → เปิดกลุ่ม → บันทึกว่าโพสต์แล้ว</p>
    </div>
    <button type="button" @click="openAddGroup()"
            class="inline-flex items-center gap-2 rounded-xl bg-primary-600 hover:bg-primary-700 px-4 py-2 text-sm font-semibold text-white shadow transition">
      <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มกลุ่ม
    </button>
  </div>

  <!-- How it works -->
  <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 flex items-start gap-3">
    <i data-lucide="info" class="w-5 h-5 text-blue-500 shrink-0 mt-0.5"></i>
    <div class="text-sm text-blue-800 space-y-1">
      <p class="font-semibold">วิธีใช้: 4 ขั้นตอน</p>
      <ol class="text-xs space-y-0.5 list-decimal list-inside text-blue-700">
        <li>เลือกโพสต์จากปฏิทิน แล้วกด "เลือกโพสต์"</li>
        <li>เลือกกลุ่มที่ต้องการ — ระบบปรับข้อความให้เหมาะกับกติกา</li>
        <li>กด Copy → เปิดกลุ่ม → วางข้อความ → โพสต์เอง</li>
        <li>กลับมากด "บันทึกว่าโพสต์แล้ว" เพื่อ log</li>
      </ol>
    </div>
  </div>

  <!-- 2-column layout: Groups + Post picker -->
  <div class="grid lg:grid-cols-2 gap-5">

    <!-- My Groups -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-semibold text-slate-700">กลุ่มของฉัน</h2>
        <span class="text-xs text-slate-400" x-text="groups.length + ' กลุ่ม'"></span>
      </div>
      <template x-if="groups.length === 0">
        <div class="p-8 text-center text-slate-400 text-sm">ยังไม่มีกลุ่ม — กดเพิ่มกลุ่มเพื่อเริ่ม</div>
      </template>
      <div class="divide-y divide-slate-100">
        <template x-for="g in groups" :key="g.id">
          <div class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 transition"
               :class="selectedGroup?.id === g.id ? 'bg-primary-50 border-l-2 border-primary-500' : ''">
            <div class="w-9 h-9 rounded-xl bg-blue-100 grid place-items-center text-blue-700 shrink-0">
              <i data-lucide="users" class="w-4 h-4"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-slate-800 text-sm truncate" x-text="g.name"></div>
              <a :href="g.url" target="_blank" rel="noopener"
                 class="text-xs text-blue-500 hover:underline truncate block" x-text="g.url"></a>
              <p x-show="g.rules" class="text-xs text-amber-700 bg-amber-50 rounded px-1.5 py-0.5 mt-1 line-clamp-2" x-text="'กติกา: ' + g.rules"></p>
            </div>
            <div class="flex flex-col gap-1 shrink-0">
              <button type="button" @click="selectGroup(g)"
                      class="text-xs px-2 py-1 rounded-lg font-semibold transition"
                      :class="selectedGroup?.id === g.id ? 'bg-primary-600 text-white' : 'bg-slate-100 hover:bg-primary-100 text-slate-700'">
                <span x-text="selectedGroup?.id === g.id ? '✓ เลือกแล้ว' : 'เลือก'"></span>
              </button>
              <button type="button" @click="openEditGroup(g)"
                      class="text-xs text-slate-400 hover:text-slate-700 px-2 py-1 rounded-lg hover:bg-slate-100">แก้ไข</button>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Post Picker + Post to Group -->
    <div class="space-y-4">

      <!-- Recent plans -->
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100">
          <h2 class="font-semibold text-slate-700">เลือกโพสต์</h2>
        </div>
        <?php if (empty($plans)): ?>
        <div class="p-6 text-center text-slate-400 text-sm">
          ยังไม่มีโพสต์ — <a href="<?= url('/owner/content-plans?tab=calendar') ?>" class="text-primary-600 hover:underline">ไปสร้างโพสต์ก่อน</a>
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
          <?php foreach ($plans as $p): ?>
          <div class="flex items-start gap-2 px-3 py-2.5 hover:bg-slate-50 transition cursor-pointer"
               :class="selectedPlan?.id === <?= (int)$p['id'] ?> ? 'bg-primary-50 border-l-2 border-primary-500' : ''"
               @click="selectPlan(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)">
            <span class="text-base shrink-0 mt-0.5"><?= $platformIcons[$p['platform']] ?? '📣' ?></span>
            <div class="flex-1 min-w-0">
              <div class="text-xs text-slate-500"><?= e(date('j M', strtotime($p['post_date']))) ?> · <span class="<?= $statusColors[$p['status']] ?> px-1.5 rounded-full"><?= $statusLabels[$p['status']] ?></span></div>
              <div class="text-sm font-semibold text-slate-800 truncate"><?= e($p['title'] ?: mb_substr($p['body'], 0, 40)) ?></div>
            </div>
            <div x-show="selectedPlan?.id === <?= (int)$p['id'] ?>" class="w-5 h-5 rounded-full bg-primary-600 text-white grid place-items-center shrink-0">
              <i data-lucide="check" class="w-3 h-3"></i>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Compose for group -->
      <div x-show="selectedPlan && selectedGroup" x-cloak class="bg-white rounded-2xl border border-violet-200 p-4 space-y-3">
        <div class="flex items-center gap-2">
          <i data-lucide="send" class="w-4 h-4 text-violet-600"></i>
          <span class="font-semibold text-sm text-slate-800">โพสต์ลงกลุ่ม: <span class="text-violet-600" x-text="selectedGroup?.name"></span></span>
        </div>
        <textarea x-model="adaptedBody" rows="5"
                  class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm resize-y focus:border-violet-400 outline-none"></textarea>
        <template x-if="selectedGroup?.rules">
          <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
            ⚠️ กติกากลุ่ม: <span x-text="selectedGroup.rules"></span>
          </p>
        </template>
        <div class="flex flex-wrap gap-2">
          <button type="button" @click="adaptForGroup()" :disabled="adaptLoading"
                  class="inline-flex items-center gap-1.5 px-3 py-2 bg-violet-100 hover:bg-violet-200 text-violet-700 text-xs font-semibold rounded-xl transition disabled:opacity-60">
            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
            <span x-text="adaptLoading ? 'ปรับข้อความ...' : 'AI ปรับข้อความให้เหมาะกลุ่ม'"></span>
          </button>
          <button type="button" @click="copyAdapted()"
                  class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-100 hover:bg-emerald-200 text-emerald-700 text-xs font-semibold rounded-xl transition">
            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
            <span x-text="copiedAdapted ? 'คัดลอกแล้ว ✓' : 'Copy ข้อความ'"></span>
          </button>
          <a :href="selectedGroup?.url" target="_blank" rel="noopener"
             class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-semibold rounded-xl transition">
            <i data-lucide="external-link" class="w-3.5 h-3.5"></i> เปิดกลุ่ม
          </a>
          <button type="button" @click="logPosted()"
                  class="inline-flex items-center gap-1.5 px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold rounded-xl transition">
            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> โพสต์แล้ว — บันทึก
          </button>
        </div>
        <p x-show="logMsg" x-text="logMsg" class="text-xs text-emerald-700 bg-emerald-50 rounded-lg px-3 py-2 border border-emerald-200"></p>
      </div>

    </div><!-- end right column -->
  </div>

</div><!-- end groupPostingApp -->

<!-- Group add/edit modal (listens for openGroupModal event) -->
<div x-data="groupModalApp()" x-init="init()" x-show="modal" x-cloak x-transition.opacity
     class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
     @click.self="modal=false">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
    <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-100">
      <h2 class="font-bold text-slate-800" x-text="editId ? 'แก้ไขกลุ่ม' : 'เพิ่มกลุ่ม Facebook'"></h2>
      <button type="button" @click="modal=false" class="text-slate-400 hover:text-slate-700 p-1"><i data-lucide="x" class="w-5 h-5"></i></button>
    </div>
    <form @submit.prevent="save()" class="p-5 space-y-3">
      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">ชื่อกลุ่ม <span class="text-red-500">*</span></label>
        <input type="text" x-model="form.name" required maxlength="200" placeholder="เช่น กลุ่มหาที่พักกาญ"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 outline-none">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">URL กลุ่ม <span class="text-red-500">*</span></label>
        <input type="url" x-model="form.url" required placeholder="https://facebook.com/groups/..."
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 outline-none">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">กติกากลุ่ม <span class="font-normal text-slate-400">(ไม่จำเป็น)</span></label>
        <input type="text" x-model="form.rules" maxlength="1000" placeholder="เช่น ห้ามระบุราคา, ห้ามโพสต์เกิน 1 ครั้ง/วัน"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 outline-none">
      </div>
      <div class="flex items-center justify-between gap-3 pt-2">
        <template x-if="editId">
          <button type="button" @click="deleteGroup()" class="inline-flex items-center gap-1.5 text-sm text-red-500 hover:text-red-700">
            <i data-lucide="trash-2" class="w-4 h-4"></i> ลบ
          </button>
        </template>
        <template x-if="!editId"><div></div></template>
        <div class="flex gap-2">
          <button type="button" @click="modal=false" class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50">ยกเลิก</button>
          <button type="submit" :disabled="saving" class="px-5 py-2 text-sm rounded-lg bg-primary-600 hover:bg-primary-700 text-white font-semibold shadow disabled:opacity-50">
            <span x-text="saving ? 'บันทึก...' : (editId ? 'บันทึก' : 'เพิ่มกลุ่ม')"></span>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endif; /* end groups tab */ ?>

<?php if ($tab === 'leads'): ?>
<!-- ═══════════════════════════════════════════════════════
     TAB 3: Lead Watchlist
═══════════════════════════════════════════════════════ -->
<div x-data="leadWatchlistApp()" x-init="init()">

  <!-- Header -->
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="text-xl font-bold text-slate-800">หา Lead จากกลุ่ม</h1>
      <p class="text-sm text-slate-500 mt-0.5">เก็บลิงก์โพสต์หาที่พัก — AI ช่วยร่างคอมเมนต์ตอบ</p>
    </div>
    <button type="button" @click="openAdd()"
            class="inline-flex items-center gap-2 rounded-xl bg-primary-600 hover:bg-primary-700 px-4 py-2 text-sm font-semibold text-white shadow transition">
      <i data-lucide="plus" class="w-4 h-4"></i> เพิ่ม Lead
    </button>
  </div>

  <!-- Filter -->
  <div class="flex flex-wrap gap-2">
    <?php $allStatuses = ['', 'new', 'replied', 'got_lead', 'closed', 'lost']; ?>
    <?php foreach ($allStatuses as $s): ?>
    <button type="button" @click="filterStatus = '<?= $s ?>'"
            :class="filterStatus === '<?= $s ?>' ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
            class="px-3 py-1.5 rounded-xl border text-xs font-semibold transition">
      <?= $s === '' ? 'ทั้งหมด (' . count($leads) . ')' : ($leadStatusLabels[$s] ?? $s) . ' (' . count(array_filter($leads, fn($l) => $l['status'] === $s)) . ')' ?>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- Lead list -->
  <template x-if="filteredLeads.length === 0">
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
      <i data-lucide="target" class="w-12 h-12 mx-auto mb-3 text-slate-300"></i>
      <p class="text-slate-400 text-sm">ยังไม่มี lead ในสถานะนี้</p>
      <p class="text-xs text-slate-400 mt-1">เมื่อเจอคนโพสต์หาที่พักในกลุ่ม กด "เพิ่ม Lead" เพื่อบันทึก</p>
    </div>
  </template>

  <div class="space-y-3">
    <template x-for="lead in filteredLeads" :key="lead.id">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
        <div class="flex items-start gap-3 p-4">
          <!-- Status badge -->
          <div class="shrink-0 mt-0.5">
            <span class="text-[10px] font-bold px-2 py-1 rounded-full"
                  :class="{
                    'bg-blue-100 text-blue-700':    lead.status === 'new',
                    'bg-amber-100 text-amber-700':  lead.status === 'replied',
                    'bg-emerald-100 text-emerald-700': lead.status === 'got_lead',
                    'bg-violet-100 text-violet-700': lead.status === 'closed',
                    'bg-slate-100 text-slate-500':  lead.status === 'lost',
                  }"
                  x-text="statusLabels[lead.status] || lead.status"></span>
          </div>
          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-1">
              <span class="text-xs font-semibold text-slate-500" x-text="formatDate(lead.found_at)"></span>
              <span x-show="lead.pax" class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full" x-text="lead.pax + ' คน'"></span>
              <span x-show="lead.checkin_date" class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full" x-text="formatDate(lead.checkin_date)"></span>
              <span x-show="lead.budget" class="text-xs bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full" x-text="'งบ: ' + lead.budget"></span>
              <span x-show="lead.property_name" class="text-xs bg-primary-50 text-primary-700 px-2 py-0.5 rounded-full" x-text="lead.property_name"></span>
            </div>
            <p class="text-sm text-slate-800 line-clamp-3" x-text="lead.customer_text"></p>
            <div x-show="lead.ai_comment" class="mt-2 bg-violet-50 border border-violet-200 rounded-xl p-3">
              <p class="text-xs font-semibold text-violet-600 mb-1">✨ AI ร่างคอมเมนต์:</p>
              <p class="text-sm text-slate-700" x-text="lead.ai_comment"></p>
              <button type="button" @click="copyComment(lead)"
                      class="mt-2 inline-flex items-center gap-1 text-xs text-violet-600 hover:text-violet-800">
                <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                <span x-text="copiedLead === lead.id ? 'คัดลอกแล้ว ✓' : 'Copy คอมเมนต์'"></span>
              </button>
            </div>
          </div>
          <!-- Actions -->
          <div class="flex flex-col gap-1.5 shrink-0">
            <a x-show="lead.fb_post_url" :href="lead.fb_post_url" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1 px-2 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-semibold rounded-lg transition">
              <i data-lucide="external-link" class="w-3 h-3"></i> โพสต์
            </a>
            <button type="button" @click="aiComment(lead)" :disabled="aiLoadingId === lead.id"
                    class="inline-flex items-center gap-1 px-2 py-1.5 bg-violet-50 hover:bg-violet-100 text-violet-700 text-xs font-semibold rounded-lg transition disabled:opacity-60">
              <i data-lucide="sparkles" class="w-3 h-3"></i>
              <span x-text="aiLoadingId === lead.id ? '...' : 'AI ตอบ'"></span>
            </button>
            <button type="button" @click="openEdit(lead)"
                    class="inline-flex items-center gap-1 px-2 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-600 text-xs font-semibold rounded-lg transition">
              <i data-lucide="pencil" class="w-3 h-3"></i> แก้ไข
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>

  <!-- Lead Modal (inside same Alpine.js scope) -->
  <div x-show="modal" x-cloak x-transition.opacity
       class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
       @click.self="modal=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
      <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-100">
        <h2 class="font-bold text-slate-800" x-text="editId ? 'แก้ไข Lead' : 'เพิ่ม Lead ใหม่'"></h2>
        <button type="button" @click="modal=false" class="text-slate-400 hover:text-slate-700 p-1"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
      <form @submit.prevent="save()" class="p-5 space-y-3">
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">ข้อความที่ลูกค้าโพสต์ <span class="text-red-500">*</span></label>
          <textarea x-model="form.customer_text" rows="4" required placeholder='เช่น: "หาที่พักแพกาญ 3 คืน 20 คน ช่วงปลายเดือนนี้ งบ 5,000 ต่อคืน"'
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 outline-none resize-y"></textarea>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">ลิงก์โพสต์ Facebook <span class="font-normal text-slate-400">(ไม่จำเป็น)</span></label>
          <input type="url" x-model="form.fb_post_url" placeholder="https://facebook.com/groups/..."
                 class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 outline-none">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">วันที่พบโพสต์</label>
            <input type="date" x-model="form.found_at" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 outline-none">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">จำนวนคน</label>
            <input type="number" x-model="form.pax" min="1" max="999" placeholder="เช่น 20"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 outline-none">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">วันเช็คอิน</label>
            <input type="date" x-model="form.checkin_date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 outline-none">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">วันเช็คเอาท์</label>
            <input type="date" x-model="form.checkout_date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 outline-none">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">งบประมาณ</label>
            <input type="text" x-model="form.budget" placeholder='เช่น "5,000/คืน"'
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 outline-none">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">ที่พัก</label>
            <select x-model="form.property_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 outline-none bg-white">
              <option value="">— ไม่ระบุ —</option>
              <?php foreach ($properties as $p): ?>
              <option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">สถานะ</label>
          <select x-model="form.status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-400 outline-none bg-white">
            <option value="new">ใหม่ — ยังไม่ตอบ</option>
            <option value="replied">ตอบแล้ว</option>
            <option value="got_lead">ได้ lead — กำลังคุย</option>
            <option value="closed">ปิดการขายแล้ว</option>
            <option value="lost">ไม่สำเร็จ</option>
          </select>
        </div>
        <div class="flex items-center justify-between gap-3 pt-2">
          <template x-if="editId">
            <button type="button" @click="deleteLead()" class="inline-flex items-center gap-1.5 text-sm text-red-500 hover:text-red-700 transition">
              <i data-lucide="trash-2" class="w-4 h-4"></i> ลบ
            </button>
          </template>
          <template x-if="!editId"><div></div></template>
          <div class="flex gap-2">
            <button type="button" @click="modal=false" class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition">ยกเลิก</button>
            <button type="submit" :disabled="saving" class="px-5 py-2 text-sm rounded-lg bg-primary-600 hover:bg-primary-700 text-white font-semibold shadow transition disabled:opacity-50">
              <span x-text="saving ? 'กำลังบันทึก...' : (editId ? 'บันทึก' : 'เพิ่ม Lead')"></span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

</div><!-- end leadWatchlistApp -->
<?php endif; /* end leads tab */ ?>

<?php if ($tab === 'settings'): ?>
<!-- ═══════════════════════════════════════════════════════
     TAB 4: Social Channel Settings
═══════════════════════════════════════════════════════ -->
<div x-data="socialSettingsApp()" x-init="init()" class="space-y-5">

  <div>
    <h1 class="text-xl font-bold text-slate-800">ตั้งค่า Social Media</h1>
    <p class="text-sm text-slate-500 mt-0.5">กรอก URL ช่องทาง Social ของที่พัก — ระบบจะใช้ลิงก์เหล่านี้ตอนสร้างโพสต์และเปิด Facebook</p>
  </div>

  <!-- Tip banner -->
  <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 flex items-start gap-3">
    <i data-lucide="lightbulb" class="w-5 h-5 text-blue-500 shrink-0 mt-0.5"></i>
    <div class="text-sm text-blue-800">
      <p class="font-semibold mb-1">ทำไมต้องตั้งค่า?</p>
      <ul class="text-xs space-y-1 text-blue-700 list-disc list-inside">
        <li>ปุ่ม "เปิด FB Page" ในโพสต์ใช้ Facebook URL จากที่นี่</li>
        <li>AI ใช้ข้อมูลที่พักเพื่อเขียนโพสต์ได้แม่นขึ้น</li>
        <li>LINE ID ใช้เป็น CTA ใน Caption</li>
      </ul>
    </div>
  </div>

  <?php if (empty($properties)): ?>
  <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
    <i data-lucide="building-2" class="w-12 h-12 mx-auto mb-3 text-slate-300"></i>
    <p class="text-slate-400 text-sm">ยังไม่มีที่พัก — <a href="<?= url('/owner/properties/create') ?>" class="text-primary-600 hover:underline">สร้างที่พักก่อน</a></p>
  </div>
  <?php else: ?>

  <!-- Property cards -->
  <?php foreach ($properties as $prop): ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden"
       x-data="propertySettingsCard(<?= htmlspecialchars(json_encode($prop), ENT_QUOTES) ?>)">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-primary-100 grid place-items-center">
          <i data-lucide="building-2" class="w-5 h-5 text-primary-600"></i>
        </div>
        <h2 class="font-bold text-slate-800"><?= e($prop['name']) ?></h2>
      </div>
      <span x-show="saved" x-cloak class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full">
        ✓ บันทึกแล้ว
      </span>
    </div>
    <div class="p-5 space-y-3">
      <div class="grid sm:grid-cols-2 gap-3">

        <!-- Facebook -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1 flex items-center gap-1.5">
            <span class="text-base">📘</span> Facebook Page URL
          </label>
          <input type="url" x-model="form.facebook_url"
                 placeholder="https://facebook.com/YourPage"
                 class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100 transition">
          <template x-if="form.facebook_url">
            <a :href="form.facebook_url" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1 mt-1 text-xs text-blue-500 hover:underline">
              <i data-lucide="external-link" class="w-3 h-3"></i> เปิดดู
            </a>
          </template>
        </div>

        <!-- LINE -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1 flex items-center gap-1.5">
            <span class="text-base">💚</span> LINE ID / LINE OA
          </label>
          <input type="text" x-model="form.line_id"
                 placeholder="@username หรือ line.me/ti/p/..."
                 class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-100 transition">
        </div>

        <!-- Instagram -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1 flex items-center gap-1.5">
            <span class="text-base">📸</span> Instagram URL
          </label>
          <input type="url" x-model="form.instagram_url"
                 placeholder="https://instagram.com/yourpage"
                 class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-pink-400 focus:outline-none focus:ring-2 focus:ring-pink-100 transition">
        </div>

        <!-- TikTok -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1 flex items-center gap-1.5">
            <span class="text-base">🎵</span> TikTok URL
          </label>
          <input type="url" x-model="form.tiktok_url"
                 placeholder="https://tiktok.com/@yourpage"
                 class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-100 transition">
        </div>

      </div>

      <!-- Save button + error -->
      <div class="flex items-center gap-3 pt-1">
        <button type="button" @click="save()" :disabled="saving"
                class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold shadow transition disabled:opacity-50">
          <i data-lucide="save" class="w-4 h-4" x-show="!saving"></i>
          <i data-lucide="loader-circle" class="w-4 h-4 animate-spin" x-show="saving" x-cloak></i>
          <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึก Social'"></span>
        </button>
        <a href="<?= url('/owner/properties/' . (int)$prop['id'] . '/edit') ?>"
           class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-primary-600 transition px-3 py-2 rounded-xl hover:bg-primary-50">
          <i data-lucide="settings" class="w-3.5 h-3.5"></i> ตั้งค่าที่พักอื่นๆ
        </a>
        <p x-show="errorMsg" x-cloak x-text="errorMsg" class="text-xs text-red-600"></p>
      </div>

      <!-- Facebook Page Connection -->
      <div class="pt-3 border-t border-slate-100">
        <p class="text-xs font-semibold text-slate-600 mb-2 flex items-center gap-1.5">
          <span class="text-base">📘</span> เชื่อมต่อ Facebook Page (Auto-Post)
        </p>
        <?php if (!empty($prop['facebook_page_id'])): ?>
        <div class="flex items-center gap-3 px-3 py-2.5 bg-emerald-50 border border-emerald-200 rounded-xl">
          <span class="text-emerald-600 text-lg">✅</span>
          <div class="flex-1">
            <p class="text-sm font-semibold text-emerald-800"><?= e($prop['facebook_page_name'] ?? $prop['facebook_page_id']) ?></p>
            <p class="text-xs text-emerald-600">เชื่อมต่อแล้ว — สามารถโพสต์ได้จาก Marketing Center</p>
          </div>
          <form method="post" action="<?= url('/owner/facebook/disconnect/' . (int)$prop['id']) ?>"
                onsubmit="return confirm('ยืนยันตัดการเชื่อมต่อ Facebook Page?')"
                class="inline">
            <?= csrf() ?>
            <button type="submit" class="text-xs text-red-400 hover:text-red-600 px-2 py-1 rounded-lg hover:bg-red-50 transition">ตัดเชื่อมต่อ</button>
          </form>
        </div>
        <?php else: ?>
        <?php if (\App\Services\FacebookService::isConfigured()): ?>
        <a href="<?= url('/owner/facebook/connect/' . (int)$prop['id']) ?>"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#1877F2] hover:bg-[#166fe5] text-white text-sm font-semibold transition">
          <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M24 12.073C24 5.406 18.627 0 12 0S0 5.406 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.514c-1.491 0-1.956.93-1.956 1.885v2.27h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
          เชื่อมต่อ Facebook Page
        </a>
        <p class="text-xs text-slate-400 mt-1.5">จะ redirect ไป Facebook Login เพื่อขอ permission</p>
        <?php else: ?>
        <div class="px-3 py-2.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-700">
          ⚠️ ยังไม่ได้ตั้งค่า Facebook App ID — Admin ต้องไปที่ <a href="<?= url('/admin/settings?tab=seo') ?>" class="underline font-semibold">Admin → ตั้งค่า → SEO/Social</a> ก่อน
        </div>
        <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <?php endif; ?>

</div><!-- end socialSettingsApp -->
<?php endif; /* end settings tab */ ?>

<script>
const __CSRF__        = '<?= \App\Core\Csrf::token() ?>';
const __UPLOAD_BASE__ = '<?= e(url('uploads/')) ?>';
const __CP_PLANS__    = <?= json_encode(array_values($plans), JSON_UNESCAPED_UNICODE) ?>;
const __CP_PROPS__    = <?= json_encode(array_values($properties ?? []), JSON_UNESCAPED_UNICODE) ?>;
const __CP_GROUPS__   = <?= json_encode(array_values($groups ?? []), JSON_UNESCAPED_UNICODE) ?>;
const __CP_LEADS__    = <?= json_encode(array_values($leads ?? []), JSON_UNESCAPED_UNICODE) ?>;
const __CP_ENDPOINTS__ = {
  store:           '<?= e(url('/owner/content-plans')) ?>',
  update:          '<?= e(url('/owner/content-plans')) ?>/{id}/update',
  destroy:         '<?= e(url('/owner/content-plans')) ?>/{id}/delete',
  aiGenerate:      '<?= e(url('/owner/content-plans/ai-generate')) ?>',
  groupSave:       '<?= e(url('/owner/content-plans/groups/save')) ?>',
  groupDelete:     '<?= e(url('/owner/content-plans/groups')) ?>/{id}/delete',
  logPost:         '<?= e(url('/owner/content-plans')) ?>/{id}/log-post',
  leadSave:        '<?= e(url('/owner/content-plans/leads/save')) ?>',
  leadDelete:      '<?= e(url('/owner/content-plans/leads')) ?>/{id}/delete',
  leadAiComment:   '<?= e(url('/owner/content-plans/leads')) ?>/{id}/ai-comment',
  propertyImages:  '<?= e(url('/owner/content-plans/property-images')) ?>',
  socialSave:      '<?= e(url('/owner/content-plans/social-save')) ?>',
  uploadImage:     '<?= e(url('/owner/content-plans/upload-image')) ?>',
  postFacebook:    '<?= e(url('/owner/content-plans')) ?>/{id}/post-facebook',
  postLine:        '<?= e(url('/owner/content-plans')) ?>/{id}/post-line',
  postInstagram:   '<?= e(url('/owner/content-plans')) ?>/{id}/post-instagram',
};

// ── Calendar + AI Content Planner ─────────────────────────
function contentPlanApp() {
  return {
    modal: false,
    editId: null,
    saving: false,
    aiLoading: false,
    aiPrompt: '',
    aiError: '',
    copied: false,
    // Social posting
    socialPosting: false,
    socialResult: null,
    // multi-image
    imgPickerOpen: false,
    imgPickerLoading: false,
    imgPickerImages: [],
    imgUploading: false,
    imgUploadingCount: 0,
    imgLibraryOpen: false,
    imgLibrary: [],   // loaded from localStorage
    manualUrl: '',
    form: {
      post_date: '', platform: 'facebook', post_type: 'page', property_id: '',
      title: '', body: '', hashtags: '',
      images: [],   // [{url, name}]
      status: 'draft',
    },
    plans: __CP_PLANS__,
    props: __CP_PROPS__,

    init() {
      if (window.lucide) lucide.createIcons();
      // Load image library from localStorage
      try {
        const saved = localStorage.getItem('mkImgLib');
        if (saved) this.imgLibrary = JSON.parse(saved);
      } catch(e) { this.imgLibrary = []; }
    },

    _resetForm(date) {
      this.form = {
        post_date: date || new Date().toISOString().slice(0, 10),
        platform: 'facebook', post_type: 'page', property_id: '',
        title: '', body: '', hashtags: '',
        images: [],
        status: 'draft',
      };
      this.aiPrompt          = '';
      this.aiError           = '';
      this.imgPickerOpen     = false;
      this.imgPickerImages   = [];
      this.imgUploading      = false;
      this.imgUploadingCount = 0;
      this.imgLibraryOpen    = false;
      this.manualUrl         = '';
      this.socialPosting = false;
      this.socialResult  = null;
    },

    fbPageConnected() {
      const propId = this.form.property_id;
      if (!propId) return false;
      const p = this.props.find(x => x.id == propId);
      return !!(p?.facebook_page_id && p?.facebook_page_token);
    },

    fbPageName() {
      const propId = this.form.property_id;
      if (!propId) return '';
      const p = this.props.find(x => x.id == propId);
      return p?.facebook_page_name || '';
    },

    lineConnected() {
      const propId = this.form.property_id;
      if (!propId) return false;
      const p = this.props.find(x => x.id == propId);
      return !!(p?.line_channel_access_token && String(p.line_channel_access_token).trim());
    },

    formHasImages() {
      return Array.isArray(this.form.images) && this.form.images.length > 0;
    },

    async postNow(platform) {
      if (!this.editId || this.socialPosting) return;
      const endpoints = {
        facebook:  __CP_ENDPOINTS__.postFacebook,
        line:      __CP_ENDPOINTS__.postLine,
        instagram: __CP_ENDPOINTS__.postInstagram,
      };
      const urlTpl = endpoints[platform];
      if (!urlTpl) return;

      this.socialPosting = true;
      this.socialResult  = null;
      try {
        const fd = new FormData();
        fd.append('_csrf', __CSRF__);
        const url = urlTpl.replace('{id}', this.editId);
        const r   = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' });
        const j   = await r.json();
        this.socialResult = { ...j, platform, url: j.post_url || j.url || '' };
        if (j.ok) {
          const idx = this.plans.findIndex(p => p.id == this.editId);
          if (idx >= 0) this.plans[idx].status = 'published';
        }
      } catch(e) {
        this.socialResult = { ok: false, error: 'เชื่อมต่อไม่สำเร็จ', platform };
      } finally {
        this.socialPosting = false;
        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
      }
    },

    async postToFacebook() { return this.postNow('facebook'); },

    _parseImages(raw) {
      if (!raw) return [];
      if (typeof raw === 'string' && raw.trimStart().startsWith('[')) {
        try {
          const arr = JSON.parse(raw);
          if (Array.isArray(arr)) return arr.map(u => ({ url: u, name: u.split('/').pop() }));
        } catch(e) {}
      }
      // single URL fallback
      return raw.trim() ? [{ url: raw.trim(), name: raw.trim().split('/').pop() }] : [];
    },

    _saveLibrary() {
      try { localStorage.setItem('mkImgLib', JSON.stringify(this.imgLibrary.slice(0, 20))); } catch(e) {}
    },

    _addToLibrary(img) {
      if (!img.url) return;
      if (!this.imgLibrary.some(i => i.url === img.url)) {
        this.imgLibrary.unshift(img);
        if (this.imgLibrary.length > 20) this.imgLibrary.pop();
        this._saveLibrary();
      }
    },

    async uploadFiles(event) {
      const files = Array.from(event.target.files || []);
      if (!files.length) return;
      this.imgUploading = true;
      this.imgUploadingCount = files.length;
      const results = [];
      for (const file of files) {
        try {
          const fd = new FormData();
          fd.append('_csrf', __CSRF__);
          fd.append('image', file);
          const r = await fetch(__CP_ENDPOINTS__.uploadImage, { method: 'POST', body: fd, credentials: 'same-origin' });
          const j = await r.json();
          if (j.ok && j.url) {
            const img = { url: j.url, name: file.name };
            results.push(img);
            this._addToLibrary(img);
          } else {
            alert(file.name + ': ' + (j.error || 'อัปโหลดไม่สำเร็จ'));
          }
        } catch(e) {
          alert(file.name + ': เชื่อมต่อไม่สำเร็จ');
        }
        this.imgUploadingCount--;
      }
      this.form.images.push(...results);
      this.imgUploading = false;
      this.imgPickerOpen = false;
      event.target.value = '';
      this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    removeImage(idx) {
      this.form.images.splice(idx, 1);
    },

    addManualUrl() {
      const u = this.manualUrl.trim();
      if (!u) return;
      if (!this.form.images.some(i => i.url === u)) {
        const img = { url: u, name: u.split('/').pop() || u };
        this.form.images.push(img);
        this._addToLibrary(img);
      }
      this.manualUrl = '';
    },

    openLibrary() {
      this.imgLibraryOpen = !this.imgLibraryOpen;
      this.imgPickerOpen  = false;
      this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    clearLibrary() {
      if (!confirm('ล้างคลังรูปทั้งหมด?')) return;
      this.imgLibrary = [];
      this._saveLibrary();
    },

    addFromLibrary(lib) {
      if (this.form.images.some(i => i.url === lib.url)) {
        // Toggle off
        this.form.images = this.form.images.filter(i => i.url !== lib.url);
      } else {
        this.form.images.push({ url: lib.url, name: lib.name });
      }
    },

    addFromPicker(img) {
      if (this.form.images.some(i => i.url === img.src)) {
        this.form.images = this.form.images.filter(i => i.url !== img.src);
      } else {
        const item = { url: img.src, name: img.src.split('/').pop() };
        this.form.images.push(item);
        this._addToLibrary(item);
      }
    },

    async openImagePicker() {
      if (!this.form.property_id) return;
      this.imgPickerOpen    = true;
      this.imgPickerImages  = [];
      this.imgPickerLoading = true;
      this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
      try {
        const r = await fetch(__CP_ENDPOINTS__.propertyImages + '?property_id=' + this.form.property_id, { credentials: 'same-origin' });
        const j = await r.json();
        if (j.ok && j.images) {
          this.imgPickerImages = j.images.map(img => ({
            id: img.id,
            src: img.path
              ? (img.path.startsWith('http') ? img.path : __UPLOAD_BASE__ + img.path.replace(/^\/+/, ''))
              : '',
          }));
        }
      } catch(e) { /* silent */ }
      this.imgPickerLoading = false;
      this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },


    openCreate(date) {
      this.editId = null;
      this._resetForm(date || '');
      this.modal = true;
      this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    openCreateWithTemplate(prompt, postType) {
      this.editId = null;
      this._resetForm('');
      this.aiPrompt       = prompt;
      this.form.post_type = postType;
      this.form.platform  = postType === 'line_broadcast' ? 'line' : 'facebook';
      this.modal = true;
      this.$nextTick(() => {
        if (window.lucide) lucide.createIcons();
        this.aiGenerate();
      });
    },

    openEdit(id) {
      const p = this.plans.find(x => x.id == id);
      if (!p) return;
      this.editId = id;
      this.form = {
        post_date:   p.post_date,
        platform:    p.platform,
        post_type:   'page',
        property_id: p.property_id || '',
        title:       p.title || '',
        body:        p.body || '',
        hashtags:    p.hashtags || '',
        images:      this._parseImages(p.image_url),
        status:      p.status,
      };
      this.aiPrompt          = '';
      this.aiError           = '';
      this.imgPickerOpen     = false;
      this.imgPickerImages   = [];
      this.manualUrl         = '';
      this.modal = true;
      this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    copyBody() {
      if (!this.form.body) return;
      const text = this.form.body + (this.form.hashtags ? '\n\n' + this.form.hashtags : '');
      navigator.clipboard?.writeText(text);
      this.copied = true;
      setTimeout(() => this.copied = false, 2000);
    },

    openFbPage() {
      const propId = this.form.property_id;
      const prop   = this.props.find(p => p.id == propId);
      if (prop?.facebook_url) { window.open(prop.facebook_url, '_blank'); return; }
      window.open('https://www.facebook.com/', '_blank');
    },

    async save() {
      if (this.saving) return;
      this.saving = true;
      try {
        const fd = new FormData();
        fd.append('_csrf', __CSRF__);
        // Serialize images array → image_url JSON string
        const { images, ...rest } = this.form;
        for (const [k, v] of Object.entries(rest)) { if (v !== '') fd.append(k, v); }
        const urls = images.map(i => i.url).filter(Boolean);
        if (urls.length) fd.append('image_url', JSON.stringify(urls));
        const url = this.editId
          ? __CP_ENDPOINTS__.update.replace('{id}', this.editId)
          : __CP_ENDPOINTS__.store;
        const r = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' });
        const j = await r.json();
        if (!j.ok) { alert(j.error || 'เกิดข้อผิดพลาด'); return; }
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
      const fd = new FormData(); fd.append('_csrf', __CSRF__);
      await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' });
      window.location.reload();
    },

    async aiGenerate() {
      if (this.aiLoading) return;
      this.aiLoading = true;
      this.aiError   = '';
      try {
        const fd = new FormData();
        fd.append('_csrf',     __CSRF__);
        fd.append('platform',  this.form.platform);
        fd.append('post_type', this.form.post_type);
        fd.append('prompt',    this.aiPrompt);
        fd.append('post_date', this.form.post_date);
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
        } else {
          this.aiError = j.error || 'AI ไม่สามารถสร้างได้';
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

// ── Group Posting Helper ───────────────────────────────────
function groupPostingApp() {
  return {
    groups: [...__CP_GROUPS__],
    selectedGroup: null,
    selectedPlan: null,
    adaptedBody: '',
    adaptLoading: false,
    copiedAdapted: false,
    logMsg: '',

    init() { if (window.lucide) lucide.createIcons(); },

    selectGroup(g) {
      this.selectedGroup = g;
      this._updateAdapted();
    },

    selectPlan(p) {
      this.selectedPlan = p;
      this._updateAdapted();
    },

    _updateAdapted() {
      if (!this.selectedPlan) return;
      this.adaptedBody = (this.selectedPlan.body || '')
        + (this.selectedPlan.hashtags ? '\n\n' + this.selectedPlan.hashtags : '');
    },

    async adaptForGroup() {
      if (!this.selectedPlan || !this.selectedGroup) return;
      this.adaptLoading = true;
      try {
        const fd = new FormData();
        fd.append('_csrf',      __CSRF__);
        fd.append('platform',   'facebook');
        fd.append('post_type',  'group');
        fd.append('prompt',     this.adaptedBody);
        fd.append('group_rules', this.selectedGroup.rules || '');
        const r = await fetch(__CP_ENDPOINTS__.aiGenerate, { method: 'POST', body: fd, credentials: 'same-origin' });
        const j = await r.json();
        if (j.ok && j.body) this.adaptedBody = j.body + (j.hashtags ? '\n\n' + j.hashtags : '');
      } catch(e) { /* silent */ }
      this.adaptLoading = false;
    },

    copyAdapted() {
      if (!this.adaptedBody) return;
      navigator.clipboard?.writeText(this.adaptedBody);
      this.copiedAdapted = true;
      setTimeout(() => this.copiedAdapted = false, 2500);
    },

    async logPosted() {
      if (!this.selectedPlan || !this.selectedGroup) return;
      const fd = new FormData();
      fd.append('_csrf', __CSRF__);
      fd.append('group_id', this.selectedGroup.id);
      fd.append('note', '');
      const url = __CP_ENDPOINTS__.logPost.replace('{id}', this.selectedPlan.id);
      try {
        const r = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' });
        const j = await r.json();
        if (j.ok) {
          this.logMsg = `บันทึกแล้ว ✓ โพสต์ไปกลุ่ม "${this.selectedGroup.name}" แล้ว`;
          // Update plan status locally
          const idx = __CP_PLANS__.findIndex(p => p.id === this.selectedPlan.id);
          if (idx >= 0) __CP_PLANS__[idx].status = 'published';
          this.selectedPlan = null;
          this.selectedGroup = null;
          this.adaptedBody   = '';
        }
      } catch(e) { /* silent */ }
    },

    openAddGroup() {
      document.dispatchEvent(new CustomEvent('openGroupModal', { detail: { group: null } }));
    },

    openEditGroup(g) {
      document.dispatchEvent(new CustomEvent('openGroupModal', { detail: { group: g } }));
    },
  };
}

// ── Group Modal (shared) ───────────────────────────────────
function groupModalApp() {
  return {
    modal: false,
    editId: null,
    saving: false,
    form: { name: '', url: '', rules: '' },

    init() {
      document.addEventListener('openGroupModal', (e) => {
        const g = e.detail?.group;
        this.editId = g?.id || null;
        this.form   = { name: g?.name || '', url: g?.url || '', rules: g?.rules || '' };
        this.modal  = true;
        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
      });
    },

    async save() {
      if (this.saving) return;
      this.saving = true;
      try {
        const fd = new FormData();
        fd.append('_csrf', __CSRF__);
        if (this.editId) fd.append('id', this.editId);
        fd.append('name', this.form.name);
        fd.append('url',  this.form.url);
        fd.append('rules', this.form.rules);
        const r = await fetch(__CP_ENDPOINTS__.groupSave, { method: 'POST', body: fd, credentials: 'same-origin' });
        const j = await r.json();
        if (j.ok) { window.location.reload(); return; }
        alert(j.error || 'เกิดข้อผิดพลาด');
      } catch(e) { alert('เชื่อมต่อไม่สำเร็จ'); }
      this.saving = false;
    },

    async deleteGroup() {
      if (!this.editId || !confirm('ลบกลุ่มนี้?')) return;
      const url = __CP_ENDPOINTS__.groupDelete.replace('{id}', this.editId);
      const fd = new FormData(); fd.append('_csrf', __CSRF__);
      await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' });
      window.location.reload();
    },
  };
}

// ── Lead Watchlist ─────────────────────────────────────────
function leadWatchlistApp() {
  return {
    modal: false,
    editId: null,
    saving: false,
    filterStatus: '',
    aiLoadingId: null,
    copiedLead: null,
    leads: [...__CP_LEADS__],
    statusLabels: {
      new: 'ใหม่', replied: 'ตอบแล้ว', got_lead: 'ได้ lead', closed: 'ปิดการขาย', lost: 'ไม่สำเร็จ',
    },
    form: {
      customer_text: '', fb_post_url: '', found_at: new Date().toISOString().slice(0, 10),
      pax: '', checkin_date: '', checkout_date: '', budget: '', zone: '', property_id: '', status: 'new',
    },

    get filteredLeads() {
      if (!this.filterStatus) return this.leads;
      return this.leads.filter(l => l.status === this.filterStatus);
    },

    init() { if (window.lucide) lucide.createIcons(); },

    formatDate(d) {
      if (!d) return '';
      const dt = new Date(d);
      return dt.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });
    },

    openAdd() {
      this.editId = null;
      this.form = {
        customer_text: '', fb_post_url: '', found_at: new Date().toISOString().slice(0, 10),
        pax: '', checkin_date: '', checkout_date: '', budget: '', zone: '', property_id: '', status: 'new',
      };
      this.modal = true;
      this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    openEdit(lead) {
      this.editId = lead.id;
      this.form = {
        customer_text:  lead.customer_text || '',
        fb_post_url:    lead.fb_post_url   || '',
        found_at:       lead.found_at      || new Date().toISOString().slice(0, 10),
        pax:            lead.pax           || '',
        checkin_date:   lead.checkin_date  || '',
        checkout_date:  lead.checkout_date || '',
        budget:         lead.budget        || '',
        zone:           lead.zone          || '',
        property_id:    lead.property_id   || '',
        status:         lead.status        || 'new',
      };
      this.modal = true;
      this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    async save() {
      if (this.saving) return;
      this.saving = true;
      try {
        const fd = new FormData();
        fd.append('_csrf', __CSRF__);
        if (this.editId) fd.append('id', this.editId);
        for (const [k, v] of Object.entries(this.form)) { if (v !== '') fd.append(k, String(v)); }
        const r = await fetch(__CP_ENDPOINTS__.leadSave, { method: 'POST', body: fd, credentials: 'same-origin' });
        const j = await r.json();
        if (j.ok) {
          if (this.editId) {
            const idx = this.leads.findIndex(l => l.id === this.editId);
            if (idx >= 0) this.leads[idx] = j.lead;
          } else {
            this.leads.unshift(j.lead);
          }
          this.modal = false;
          this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
          return;
        }
        alert(j.error || 'เกิดข้อผิดพลาด');
      } catch(e) { alert('เชื่อมต่อไม่สำเร็จ'); }
      this.saving = false;
    },

    async deleteLead() {
      if (!this.editId || !confirm('ลบ lead นี้?')) return;
      const url = __CP_ENDPOINTS__.leadDelete.replace('{id}', this.editId);
      const fd = new FormData(); fd.append('_csrf', __CSRF__);
      await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' });
      this.leads = this.leads.filter(l => l.id !== this.editId);
      this.modal = false;
    },

    async aiComment(lead) {
      this.aiLoadingId = lead.id;
      try {
        const url = __CP_ENDPOINTS__.leadAiComment.replace('{id}', lead.id);
        const r   = await fetch(url, { credentials: 'same-origin' });
        const j   = await r.json();
        if (j.ok && j.comment) {
          const idx = this.leads.findIndex(l => l.id === lead.id);
          if (idx >= 0) this.leads[idx] = { ...this.leads[idx], ai_comment: j.comment };
          this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        } else {
          alert(j.error || 'AI ไม่พร้อม');
        }
      } catch(e) { alert('เชื่อมต่อไม่สำเร็จ'); }
      this.aiLoadingId = null;
    },

    copyComment(lead) {
      if (!lead.ai_comment) return;
      navigator.clipboard?.writeText(lead.ai_comment);
      this.copiedLead = lead.id;
      setTimeout(() => this.copiedLead = null, 2500);
    },
  };
}

// ── Social Settings ────────────────────────────────────────
function socialSettingsApp() {
  return {
    init() { if (window.lucide) lucide.createIcons(); },
  };
}

function propertySettingsCard(prop) {
  return {
    saving:   false,
    saved:    false,
    errorMsg: '',
    form: {
      facebook_url:  prop.facebook_url  || '',
      line_id:       prop.line_id       || '',
      instagram_url: prop.instagram_url || '',
      tiktok_url:    prop.tiktok_url    || '',
    },
    propertyId: prop.id,

    async save() {
      if (this.saving) return;
      this.saving   = true;
      this.errorMsg = '';
      this.saved    = false;
      try {
        const fd = new FormData();
        fd.append('_csrf',         __CSRF__);
        fd.append('property_id',   this.propertyId);
        fd.append('facebook_url',  this.form.facebook_url  || '');
        fd.append('line_id',       this.form.line_id       || '');
        fd.append('instagram_url', this.form.instagram_url || '');
        fd.append('tiktok_url',    this.form.tiktok_url    || '');
        const r = await fetch(__CP_ENDPOINTS__.socialSave, { method: 'POST', body: fd, credentials: 'same-origin' });
        const j = await r.json();
        if (j.ok) {
          this.saved = true;
          setTimeout(() => this.saved = false, 3000);
        } else {
          this.errorMsg = j.error || 'เกิดข้อผิดพลาด';
        }
      } catch(e) {
        this.errorMsg = 'เชื่อมต่อไม่สำเร็จ';
      } finally {
        this.saving = false;
      }
    },
  };
}
</script>
