<?php
/**
 * @var string  $page_title
 * @var string  $meta_desc
 * @var array[] $rows
 * @var string  $targetDate
 * @var string  $dateLabel
 * @var string  $mode        'today' | 'weekend'
 * @var array   $weekend     ['saturday'=>'Y-m-d','sunday'=>'Y-m-d']
 * @var string|null $filterType
 */
use App\Services\AvailablePropertiesService as APS;
use App\Support\UnitPricing;

$typeOptions = [
    ''          => 'ทุกประเภท',
    'raft'      => 'แพพัก',
    'resort'    => 'รีสอร์ท',
    'pool_villa'=> 'บ้านพูลวิลล่า',
    'homestay'  => 'โฮมสเตย์',
    'camping'   => 'แคมป์ปิ้ง',
    'house'     => 'บ้านพัก',
];
?>

<main class="bg-slate-50 min-h-screen pb-16">

  <!-- Hero header -->
  <section class="bg-gradient-to-br from-forest-900 via-forest-800 to-forest-700 text-white pt-10 pb-8 px-4">
    <div class="max-w-5xl mx-auto">
      <div class="flex items-center gap-2 text-emerald-300 text-xs font-semibold mb-3 uppercase tracking-wide">
        <i data-lucide="calendar-check-2" class="w-4 h-4"></i>
        ความพร้อมแบบเรียลไทม์
      </div>
      <h1 class="text-2xl sm:text-3xl font-extrabold leading-snug">
        ที่พักว่าง <?= e($dateLabel) ?>
      </h1>
      <p class="mt-2 text-forest-200 text-sm">
        <?= count($rows) ?> ที่พักที่ยังว่างอยู่ — จองได้เลยทันที
      </p>

      <!-- Date tabs -->
      <div class="flex flex-wrap gap-2 mt-5">
        <?php
        $today = date('Y-m-d');
        $isToday = $targetDate === $today;
        $isSat   = $targetDate === $weekend['saturday'];
        $isSun   = $targetDate === $weekend['sunday'];
        ?>
        <a href="<?= url('/available-today' . ($filterType ? '?type=' . urlencode($filterType) : '')) ?>"
           class="px-4 py-2 rounded-xl text-sm font-bold transition <?= $isToday ? 'bg-white text-forest-900' : 'bg-white/15 text-white hover:bg-white/25' ?>">
          <i data-lucide="sun" class="w-4 h-4 inline-block mr-1 -mt-0.5"></i>วันนี้
        </a>
        <a href="<?= url('/available-weekend' . ($filterType ? '?type=' . urlencode($filterType) : '')) ?>"
           class="px-4 py-2 rounded-xl text-sm font-bold transition <?= $isSat ? 'bg-white text-forest-900' : 'bg-white/15 text-white hover:bg-white/25' ?>">
          <i data-lucide="calendar" class="w-4 h-4 inline-block mr-1 -mt-0.5"></i>เสาร์ <?= APS::thaiDate($weekend['saturday']) ?>
        </a>
        <a href="<?= url('/available-weekend?day=sunday' . ($filterType ? '&type=' . urlencode($filterType) : '')) ?>"
           class="px-4 py-2 rounded-xl text-sm font-bold transition <?= $isSun ? 'bg-white text-forest-900' : 'bg-white/15 text-white hover:bg-white/25' ?>">
          <i data-lucide="calendar" class="w-4 h-4 inline-block mr-1 -mt-0.5"></i>อาทิตย์ <?= APS::thaiDate($weekend['sunday']) ?>
        </a>
      </div>
    </div>
  </section>

  <!-- Type filter strip -->
  <div class="sticky top-0 z-20 bg-white border-b border-slate-200 shadow-sm overflow-x-auto">
    <div class="flex gap-1.5 px-4 py-2.5 max-w-5xl mx-auto min-w-0 w-max">
      <?php foreach ($typeOptions as $tv => $tl):
        $href = ($mode === 'today')
            ? url('/available-today' . ($tv !== '' ? '?type=' . urlencode($tv) : ''))
            : url('/available-weekend' . ($tv !== '' ? '?type=' . urlencode($tv) : '') . (isset($_GET['day']) ? ($tv !== '' ? '&day=' : '?day=') . htmlspecialchars($_GET['day']) : ''));
        $active = ($filterType ?? '') === $tv;
      ?>
      <a href="<?= $href ?>"
         class="whitespace-nowrap px-3 py-1.5 rounded-full text-xs font-bold transition <?= $active ? 'bg-forest-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
        <?= e($tl) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Grid -->
  <div class="max-w-5xl mx-auto px-4 pt-7">
    <?php if (empty($rows)): ?>
    <div class="py-20 text-center">
      <i data-lucide="search-x" class="w-14 h-14 mx-auto text-slate-300 mb-4"></i>
      <h2 class="text-lg font-bold text-slate-600">ไม่พบที่พักว่างในวันนี้</h2>
      <p class="text-sm text-slate-400 mt-1">ลองดูวันอื่น หรือเลือกประเภทที่พักต่างออกไป</p>
      <a href="<?= url('/properties') ?>" class="mt-5 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-forest-700 text-white text-sm font-bold hover:bg-forest-800 transition">
        <i data-lucide="search" class="w-4 h-4"></i>ค้นหาทั้งหมด
      </a>
    </div>
    <?php else: ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach ($rows as $property):
        // ปรับ property array ให้ property-card.php ใช้ได้
        $property['listing_unit_id']     = null;
        $property['listing_unit_name']   = null;
        $property['listing_unit_cover']  = null;
        $property['listing_unit_price']  = null;

        // badge พิเศษ "ว่างวันนี้" + "ปฏิทินอัปเดตล่าสุด"
        $avCount  = (int)($property['available_unit_count'] ?? 0);
        $calFresh = !empty($property['calendar_updated_at']);
      ?>
      <div class="relative">
        <?php require __DIR__ . '/../partials/property-card.php'; ?>
        <!-- Available + freshness badges overlay -->
        <div class="absolute bottom-[4.5rem] left-3 z-[8] pointer-events-none flex flex-col gap-1 items-start">
          <span class="px-2 py-1 bg-emerald-600 text-white text-[10px] font-bold rounded-lg shadow-md inline-flex items-center gap-1 ring-1 ring-white/30">
            <i data-lucide="check-circle-2" class="w-3 h-3 shrink-0"></i>
            ว่าง<?= $avCount > 1 ? " {$avCount} ยูนิต" : '' ?>
          </span>
          <?php if ($calFresh): ?>
          <span class="px-2 py-1 bg-sky-600 text-white text-[10px] font-bold rounded-lg shadow-md inline-flex items-center gap-1 ring-1 ring-white/30">
            <i data-lucide="calendar-check" class="w-3 h-3 shrink-0"></i>
            ปฏิทินอัปเดตล่าสุด
          </span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <p class="mt-8 text-center text-xs text-slate-400">
      แสดง <?= count($rows) ?> ที่พัก · ข้อมูลอัปเดตแบบเรียลไทม์จากระบบจอง
    </p>
    <?php endif; ?>
  </div>

  <!-- CTA Bottom -->
  <div class="max-w-5xl mx-auto px-4 mt-12">
    <div class="bg-gradient-to-r from-forest-800 to-forest-700 rounded-2xl p-6 flex flex-col sm:flex-row items-center gap-4 text-white">
      <div class="flex-1">
        <div class="font-bold text-lg">ไม่เจอที่พักที่ใช่?</div>
        <div class="text-forest-200 text-sm mt-0.5">ค้นหาและกรองตามโซน งบประมาณ จำนวนห้องได้เลย</div>
      </div>
      <a href="<?= url('/properties') ?>"
         class="shrink-0 px-5 py-2.5 bg-white text-forest-900 font-bold rounded-xl text-sm hover:bg-forest-50 transition">
        ค้นหาที่พักทั้งหมด →
      </a>
    </div>
  </div>

</main>
