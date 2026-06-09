<?php
/**
 * มือถือ: แถบสลับแบบย่อ / มากขึ้น + รายการ property-card-horizontal (ใช้ Alpine mode จาก parent ของการ์ด)
 *
 * @var array          $properties
 * @var string|null    $wrapperClass คลาสครอบนอก (เช่น max-w-2xl mx-auto w-full)
 * @var bool|null      $showTabs     false = ไม่แสดงปุ่มสลับ (ค่าเริ่มต้น true)
 * @var string|null    $listClass    เพิ่มที่ space-y-3 (เช่น mb-4)
 */
$wrapperClass = $wrapperClass ?? 'w-full';
$showTabs = $showTabs ?? true;
$listClass = $listClass ?? '';
?>
<div class="<?= e($wrapperClass) ?>" x-data="{ mode: 'compact' }" @card-mode.window="mode = $event.detail.value">
  <?php if ($showTabs): ?>
    <div class="mb-3 flex items-center justify-end">
      <div class="inline-grid grid-cols-2 rounded-lg border border-slate-200 bg-white p-0.5 shadow-sm">
        <button type="button" @click="mode='compact'"
                :class="mode === 'compact' ? 'bg-slate-100 text-slate-900' : 'text-slate-400 hover:text-slate-600'"
                class="inline-flex items-center justify-center gap-1 rounded-md px-3 py-1.5 font-semibold transition text-xs">
          <i data-lucide="layout-list" class="w-3.5 h-3.5"></i>
          <span>ย่อ</span>
        </button>
        <button type="button" @click="mode='detail'"
                :class="mode === 'detail' ? 'bg-slate-100 text-slate-900' : 'text-slate-400 hover:text-slate-600'"
                class="inline-flex items-center justify-center gap-1 rounded-md px-3 py-1.5 font-semibold transition text-xs">
          <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i>
          <span>ละเอียด</span>
        </button>
      </div>
    </div>
  <?php endif; ?>
  <div class="space-y-3 <?= e($listClass) ?>">
    <?php foreach ($properties as $property):
      \App\Core\View::partial('partials/property-card-horizontal', ['property' => $property]);
    endforeach; ?>
  </div>
</div>
