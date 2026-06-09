<?php
/**
 * Checkout step indicator.
 *
 * @var int $active 1=เลือก, 2=ชำระเงิน, 3=ยืนยัน
 */
$active = isset($active) ? (int) $active : 1;
$steps = [
    1 => ['label' => 'เลือก / กรอกข้อมูล', 'icon' => 'clipboard-list'],
    2 => ['label' => 'ชำระเงิน',          'icon' => 'credit-card'],
    3 => ['label' => 'รับการยืนยัน',      'icon' => 'check-circle'],
];
?>
<nav aria-label="ขั้นตอนการสั่งซื้อ" class="mb-6">
  <ol class="flex items-center gap-2 sm:gap-3 overflow-x-auto">
    <?php foreach ($steps as $n => $s):
        $isActive = $n === $active;
        $isDone   = $n < $active;
    ?>
      <li class="flex items-center gap-2 flex-shrink-0">
        <span class="w-8 h-8 rounded-full grid place-items-center text-sm font-bold transition
                     <?= $isActive ? 'bg-accent-500 text-white shadow-md ring-4 ring-accent-100' :
                        ($isDone   ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-500') ?>">
          <?php if ($isDone): ?>
            <i data-lucide="check" class="w-4 h-4"></i>
          <?php else: ?>
            <?= $n ?>
          <?php endif; ?>
        </span>
        <span class="text-xs sm:text-sm <?= $isActive ? 'font-bold text-slate-900' : 'text-slate-500' ?>">
          <?= e($s['label']) ?>
        </span>
        <?php if ($n < count($steps)): ?>
          <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 ml-1"></i>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
</nav>
