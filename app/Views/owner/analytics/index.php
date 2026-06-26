<?php
/**
 * @var array[]     $properties
 * @var int         $propertyId
 * @var int         $range
 * @var array       $clicks       ['phone','line','coupon','book','map']
 * @var array       $clicksMonth
 * @var int         $views
 * @var int         $viewsMonth
 * @var array[]     $dailyClicks
 * @var array[]     $dailyViews
 * @var float       $clickRate
 * @var array[]     $topReferrers
 * @var string|null $aiSummaryUrl
 * @var bool        $hasLeadTable
 * @var bool        $hasViewTable
 */
$property = null;
foreach ($properties as $p) {
    if ((int)$p['id'] === $propertyId) { $property = $p; break; }
}
$totalClicks = array_sum($clicks);
$totalClicksMonth = array_sum($clicksMonth);

$chartLabels     = json_encode(array_column($dailyClicks, 'date'));
$chartPhone      = json_encode(array_column($dailyClicks, 'phone'));
$chartLine       = json_encode(array_column($dailyClicks, 'line'));
$chartBook       = json_encode(array_column($dailyClicks, 'book'));
$chartViews      = json_encode(array_column($dailyViews,  'cnt'));
$chartViewLabels = json_encode(array_column($dailyViews,  'date'));
?>

<!-- Property selector + range -->
<div class="flex flex-wrap items-center gap-3 mb-6">
  <?php if (count($properties) > 1): ?>
  <form method="get" action="<?= url('/owner/analytics') ?>" class="flex items-center gap-2">
    <select name="property_id" onchange="this.form.submit()"
            class="px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none bg-white">
      <?php foreach ($properties as $pr): ?>
        <option value="<?= (int)$pr['id'] ?>" <?= (int)$pr['id'] === $propertyId ? 'selected' : '' ?>>
          <?= e($pr['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <input type="hidden" name="range" value="<?= $range ?>">
  </form>
  <?php endif; ?>

  <!-- Range tabs -->
  <div class="flex gap-1">
    <?php foreach ([7 => '7 วัน', 14 => '14 วัน', 30 => '30 วัน', 90 => '90 วัน'] as $v => $label): ?>
    <a href="<?= url('/owner/analytics?' . http_build_query(['property_id' => $propertyId, 'range' => $v])) ?>"
       class="px-3 py-1.5 rounded-xl text-sm font-medium transition <?= $range === $v ? 'bg-accent-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
      <?= $label ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if ($property): ?>
  <span class="text-xs text-slate-500 bg-slate-100 rounded-full px-3 py-1.5">
    <?= e($property['name']) ?>
  </span>
  <?php endif; ?>
</div>

<?php if (!$propertyId): ?>
<div class="text-center py-20 text-slate-400">
  <i data-lucide="bar-chart-2" class="w-12 h-12 mx-auto mb-3 opacity-30"></i>
  <p class="text-sm">เลือกที่พักด้านบนเพื่อดูสถิติ</p>
</div>
<?php elseif (!$hasLeadTable && !$hasViewTable): ?>
<div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-amber-800 text-sm">
  <div class="flex items-center gap-2 font-semibold mb-1">
    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
    ยังไม่มีข้อมูล Analytics
  </div>
  ตารางบันทึกสถิติยังไม่ถูกสร้าง — รัน migration ก่อนเพื่อเปิดใช้งาน
</div>
<?php else: ?>

<!-- Summary KPI cards -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
  <!-- Views -->
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-4">
    <div class="flex items-center gap-2 text-slate-500 text-xs mb-2">
      <i data-lucide="eye" class="w-4 h-4 text-blue-500 shrink-0"></i>
      ดูหน้าที่พัก
    </div>
    <div class="text-2xl font-bold text-slate-800"><?= number_format($views) ?></div>
    <div class="text-[10px] text-slate-400 mt-1"><?= $range ?> วัน · เดือนนี้ <?= number_format($viewsMonth) ?></div>
  </div>

  <!-- Phone -->
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-4">
    <div class="flex items-center gap-2 text-slate-500 text-xs mb-2">
      <i data-lucide="phone" class="w-4 h-4 text-emerald-500 shrink-0"></i>
      กดโทร
    </div>
    <div class="text-2xl font-bold text-slate-800"><?= number_format($clicks['phone']) ?></div>
    <div class="text-[10px] text-slate-400 mt-1"><?= $range ?> วัน · เดือนนี้ <?= number_format($clicksMonth['phone']) ?></div>
  </div>

  <!-- LINE -->
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-4">
    <div class="flex items-center gap-2 text-slate-500 text-xs mb-2">
      <svg class="w-4 h-4 text-[#06C755] shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.03 2 11c0 2.98 1.6 5.6 4.08 7.27L5.5 22l4.15-2.05A10.94 10.94 0 0 0 12 20c5.52 0 10-4.03 10-9S17.52 2 12 2z"/></svg>
      กด LINE
    </div>
    <div class="text-2xl font-bold text-slate-800"><?= number_format($clicks['line']) ?></div>
    <div class="text-[10px] text-slate-400 mt-1"><?= $range ?> วัน · เดือนนี้ <?= number_format($clicksMonth['line']) ?></div>
  </div>

  <!-- Book -->
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-4">
    <div class="flex items-center gap-2 text-slate-500 text-xs mb-2">
      <i data-lucide="calendar-check" class="w-4 h-4 text-accent-600 shrink-0"></i>
      กดจอง
    </div>
    <div class="text-2xl font-bold text-slate-800"><?= number_format($clicks['book']) ?></div>
    <div class="text-[10px] text-slate-400 mt-1"><?= $range ?> วัน · เดือนนี้ <?= number_format($clicksMonth['book']) ?></div>
  </div>

  <!-- Map -->
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-4">
    <div class="flex items-center gap-2 text-slate-500 text-xs mb-2">
      <i data-lucide="map-pin" class="w-4 h-4 text-orange-500 shrink-0"></i>
      ดูแผนที่
    </div>
    <div class="text-2xl font-bold text-slate-800"><?= number_format($clicks['map']) ?></div>
    <div class="text-[10px] text-slate-400 mt-1"><?= $range ?> วัน · เดือนนี้ <?= number_format($clicksMonth['map']) ?></div>
  </div>

  <!-- CTR -->
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-4 sm:col-span-1 col-span-2">
    <div class="flex items-center gap-2 text-slate-500 text-xs mb-2">
      <i data-lucide="mouse-pointer-click" class="w-4 h-4 text-violet-500 shrink-0"></i>
      Click Rate
    </div>
    <div class="text-2xl font-bold text-slate-800"><?= $clickRate ?>%</div>
    <div class="text-[10px] text-slate-400 mt-1">คนที่ดูแล้วกด contact</div>
  </div>
</div>

<!-- Conversion funnel -->
<?php if ($hasViewTable || $hasLeadTable): ?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 mb-6">
  <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
    <i data-lucide="filter" class="w-4 h-4 text-accent-600"></i>
    Funnel การตลาด (<?= $range ?> วัน)
  </h3>
  <div class="grid sm:grid-cols-4 gap-3 text-center">
    <div class="rounded-xl bg-blue-50 border border-blue-100 p-3">
      <div class="text-[10px] text-blue-600 font-semibold uppercase tracking-wide">เข้าชม</div>
      <div class="text-xl font-bold text-slate-800 mt-1"><?= number_format($views) ?></div>
    </div>
    <div class="rounded-xl bg-violet-50 border border-violet-100 p-3">
      <div class="text-[10px] text-violet-600 font-semibold uppercase tracking-wide">กด Contact</div>
      <div class="text-xl font-bold text-slate-800 mt-1"><?= number_format($contactClicks ?? 0) ?></div>
      <div class="text-[10px] text-violet-500 mt-1"><?= $viewToContact ?? 0 ?>% จากผู้เข้าชม</div>
    </div>
    <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-3">
      <div class="text-[10px] text-emerald-600 font-semibold uppercase tracking-wide">จองยืนยัน</div>
      <div class="text-xl font-bold text-slate-800 mt-1"><?= number_format($bookingsInRange['confirmed'] ?? 0) ?></div>
      <div class="text-[10px] text-emerald-500 mt-1"><?= $contactToBook ?? 0 ?>% จาก contact</div>
    </div>
    <div class="rounded-xl bg-amber-50 border border-amber-100 p-3">
      <div class="text-[10px] text-amber-600 font-semibold uppercase tracking-wide">รายได้จอง</div>
      <div class="text-xl font-bold text-slate-800 mt-1">฿<?= number_format($bookingsInRange['revenue'] ?? 0) ?></div>
      <div class="text-[10px] text-amber-500 mt-1">ยืนยัน/เสร็จแล้ว</div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($dailyClicks) && $hasLeadTable): ?>
<!-- Daily clicks chart -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 mb-5">
  <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
    <i data-lucide="trending-up" class="w-4 h-4 text-accent-600"></i>
    การกด Contact รายวัน (<?= $range ?> วัน)
  </h3>
  <div class="overflow-x-auto">
    <canvas id="clickChart" height="80"></canvas>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($dailyViews) && $hasViewTable): ?>
<!-- Page views chart -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 mb-5">
  <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
    <i data-lucide="eye" class="w-4 h-4 text-blue-500"></i>
    ยอดเข้าชมหน้าที่พักรายวัน (<?= $range ?> วัน)
  </h3>
  <div class="overflow-x-auto">
    <canvas id="viewChart" height="60"></canvas>
  </div>
</div>
<?php endif; ?>

<!-- Insight card -->
<div class="bg-gradient-to-br from-accent-50 to-white rounded-2xl border border-accent-200 p-5">
  <h3 class="text-sm font-bold text-slate-700 mb-3 flex items-center gap-2">
    <i data-lucide="lightbulb" class="w-4 h-4 text-accent-600"></i>
    สรุปช่วง <?= $range ?> วัน
  </h3>
  <ul class="text-sm text-slate-700 space-y-1.5">
    <?php if ($views > 0): ?>
    <li class="flex items-center gap-2">
      <span class="w-2 h-2 rounded-full bg-blue-400 shrink-0"></span>
      มีคนเข้าชมหน้าที่พัก <strong><?= number_format($views) ?></strong> ครั้ง
    </li>
    <?php endif; ?>
    <?php if ($clicks['phone'] > 0): ?>
    <li class="flex items-center gap-2">
      <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
      กดโทร <strong><?= number_format($clicks['phone']) ?></strong> ครั้ง — มีโอกาสรับจอง
    </li>
    <?php endif; ?>
    <?php if ($clicks['line'] > 0): ?>
    <li class="flex items-center gap-2">
      <span class="w-2 h-2 rounded-full bg-[#06C755] shrink-0"></span>
      กด LINE <strong><?= number_format($clicks['line']) ?></strong> ครั้ง — ควรเช็ครายชื่อแชทด้วย
    </li>
    <?php endif; ?>
    <?php if ($clicks['book'] > 0): ?>
    <li class="flex items-center gap-2">
      <span class="w-2 h-2 rounded-full bg-accent-500 shrink-0"></span>
      กดจองออนไลน์ <strong><?= number_format($clicks['book']) ?></strong> ครั้ง
    </li>
    <?php endif; ?>
    <?php if ($clicks['map'] > 0): ?>
    <li class="flex items-center gap-2">
      <span class="w-2 h-2 rounded-full bg-orange-400 shrink-0"></span>
      เปิดแผนที่ <strong><?= number_format($clicks['map']) ?></strong> ครั้ง — ลูกค้าสนใจเส้นทาง
    </li>
    <?php endif; ?>
    <?php if ($totalClicks === 0 && $views === 0): ?>
    <li class="text-slate-400">ยังไม่มีข้อมูลในช่วงนี้ — ข้อมูลเริ่มเก็บตั้งแต่ที่ติดตั้ง tracking</li>
    <?php elseif ($clickRate < 5 && $views > 20): ?>
    <li class="flex items-center gap-2">
      <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>
      Click rate ต่ำ — ลองเพิ่มรูปหรืออัปเดตคำอธิบายให้น่าสนใจขึ้น
    </li>
    <?php elseif ($clickRate >= 10): ?>
    <li class="flex items-center gap-2">
      <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
      Click rate ดีมาก! คนดูแล้วสนใจติดต่อสูง
    </li>
    <?php endif; ?>
  </ul>
</div>

<?php endif; ?>

<?php if (!$canDeep): ?>
<!-- Upsell: analytics_deep locked -->
<div class="bg-violet-50 border border-violet-200 rounded-2xl p-5 mb-5 flex items-start gap-3">
  <i data-lucide="lock" class="w-5 h-5 text-violet-400 shrink-0 mt-0.5"></i>
  <div>
    <p class="text-sm font-semibold text-violet-800">ฟีเจอร์นี้ต้องใช้แพ็กเกจ Standard ขึ้นไป</p>
    <p class="text-xs text-violet-600 mt-0.5">แหล่งที่มาผู้เข้าชม (Referrer) และ AI วิเคราะห์สถิติ พร้อมให้ใช้เมื่ออัปเกรด</p>
    <a href="<?= url('/owner/membership') ?>" class="inline-block mt-2 px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-semibold rounded-xl transition">ดูแพ็กเกจ →</a>
  </div>
</div>
<?php endif; ?>

<?php if ($aiSummaryUrl): ?>
<!-- AI Weekly Summary -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 mb-5"
     x-data="{ loading: false, summary: '', error: '' }">
  <div class="flex items-center justify-between mb-3">
    <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
      <i data-lucide="sparkles" class="w-4 h-4 text-violet-500"></i>
      AI วิเคราะห์สถิติ
    </h3>
    <button type="button" @click="loading=true; summary=''; error='';
            fetch('<?= e($aiSummaryUrl) ?>').then(r=>r.json()).then(d=>{ loading=false; if(d.ok) summary=d.summary; else error=d.error||'ผิดพลาด'; }).catch(()=>{ loading=false; error='ไม่สามารถเชื่อมต่อ'; })"
            class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-semibold rounded-xl transition flex items-center gap-1.5"
            :disabled="loading">
      <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
      <span x-text="loading ? 'กำลังวิเคราะห์...' : 'วิเคราะห์ด้วย AI'"></span>
    </button>
  </div>
  <div x-show="summary" class="text-sm text-slate-700 leading-relaxed bg-violet-50 rounded-xl p-4 border border-violet-100" x-text="summary"></div>
  <div x-show="error" class="text-sm text-rose-600 bg-rose-50 rounded-xl p-3 border border-rose-100" x-text="error"></div>
  <p x-show="!summary && !error && !loading" class="text-xs text-slate-400">กดปุ่มเพื่อให้ AI สรุปสถิติ <?= $range ?> วัน พร้อมคำแนะนำ</p>
</div>
<?php endif; ?>

<?php if ($canDeep && !empty($topReferrers) && $hasViewTable): ?>
<!-- Referrer breakdown -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 mb-5">
  <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
    <i data-lucide="globe" class="w-4 h-4 text-blue-500"></i>
    แหล่งที่มาของผู้เข้าชม (<?= $range ?> วัน)
  </h3>
  <div class="space-y-2">
    <?php
    $maxRef = max(array_column($topReferrers, 'cnt') ?: [1]);
    foreach ($topReferrers as $ref):
        $pct = round((int)$ref['cnt'] / $maxRef * 100);
        $isFb = str_contains((string)$ref['referrer'], 'facebook') || str_contains((string)$ref['referrer'], 'fb.com');
        $isDirect = $ref['referrer'] === '(direct)';
    ?>
    <div class="flex items-center gap-3 text-sm">
      <div class="w-5 text-center shrink-0">
        <?php if ($isFb): ?>
          <span class="text-[#1877F2] font-bold text-xs">f</span>
        <?php elseif ($isDirect): ?>
          <i data-lucide="bookmark" class="w-3.5 h-3.5 text-slate-400 inline-block"></i>
        <?php else: ?>
          <i data-lucide="globe" class="w-3.5 h-3.5 text-slate-400 inline-block"></i>
        <?php endif; ?>
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between mb-0.5">
          <span class="truncate text-slate-700 text-xs font-medium"><?= e($ref['referrer']) ?></span>
          <span class="text-slate-500 text-xs ml-2 shrink-0"><?= number_format((int)$ref['cnt']) ?></span>
        </div>
        <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
          <div class="h-full rounded-full <?= $isFb ? 'bg-[#1877F2]' : 'bg-accent-500' ?>"
               style="width:<?= $pct ?>%"></div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const labels  = <?= $chartLabels ?>;
  const phone   = <?= $chartPhone ?>;
  const lineC   = <?= $chartLine ?>;
  const book    = <?= $chartBook ?>;
  const views   = <?= $chartViews ?>;
  const vLabels = <?= $chartViewLabels ?>;

  const clickEl = document.getElementById('clickChart');
  if (clickEl && labels.length) {
    new Chart(clickEl, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          { label: 'กดโทร',  data: phone,  backgroundColor: '#10b98188', borderRadius: 4 },
          { label: 'กด LINE', data: lineC, backgroundColor: '#06C75588', borderRadius: 4 },
          { label: 'กดจอง',  data: book,   backgroundColor: '#6366f188', borderRadius: 4 },
        ],
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
        scales: { x: { stacked: true, ticks: { font: { size: 10 } } }, y: { stacked: true, beginAtZero: true } },
      },
    });
  }

  const viewEl = document.getElementById('viewChart');
  if (viewEl && vLabels.length) {
    new Chart(viewEl, {
      type: 'line',
      data: {
        labels: vLabels,
        datasets: [{
          label: 'ยอดเข้าชม',
          data: views,
          borderColor: '#3b82f6',
          backgroundColor: '#3b82f620',
          fill: true,
          tension: 0.3,
          pointRadius: 2,
        }],
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { x: { ticks: { font: { size: 10 } } }, y: { beginAtZero: true } },
      },
    });
  }
});
</script>
