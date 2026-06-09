<?php
/**
 * @var int    $page
 * @var int    $totalPages
 * @var string $baseUrl    เช่น url('/properties')
 * @var array  $query      query string เพิ่มเติม
 */
if (!isset($totalPages) || $totalPages <= 1) return;
$query = $query ?? [];
$mk = function (int $p) use ($baseUrl, $query) {
    $q = array_merge($query, ['page' => $p]);
    return $baseUrl . '?' . http_build_query($q);
};
$range = 2;
$start = max(1, $page - $range);
$end   = min($totalPages, $page + $range);
?>
<nav class="flex items-center justify-center gap-1 mt-10">
  <a href="<?= $mk(max(1, $page - 1)) ?>"
     class="w-10 h-10 grid place-items-center rounded-lg border border-slate-200 hover:bg-slate-100 <?= $page<=1?'opacity-40 pointer-events-none':'' ?>">
    <i data-lucide="chevron-left" class="w-4 h-4"></i>
  </a>
  <?php if ($start > 1): ?>
    <a href="<?= $mk(1) ?>" class="w-10 h-10 grid place-items-center rounded-lg border border-slate-200 hover:bg-slate-100">1</a>
    <?php if ($start > 2): ?><span class="px-2 text-slate-400">…</span><?php endif; ?>
  <?php endif; ?>
  <?php for ($i = $start; $i <= $end; $i++): ?>
    <a href="<?= $mk($i) ?>"
       class="w-10 h-10 grid place-items-center rounded-lg border <?= $i===$page?'border-primary-600 bg-primary-600 text-white font-semibold':'border-slate-200 hover:bg-slate-100' ?>">
      <?= $i ?>
    </a>
  <?php endfor; ?>
  <?php if ($end < $totalPages): ?>
    <?php if ($end < $totalPages - 1): ?><span class="px-2 text-slate-400">…</span><?php endif; ?>
    <a href="<?= $mk($totalPages) ?>" class="w-10 h-10 grid place-items-center rounded-lg border border-slate-200 hover:bg-slate-100"><?= $totalPages ?></a>
  <?php endif; ?>
  <a href="<?= $mk(min($totalPages, $page + 1)) ?>"
     class="w-10 h-10 grid place-items-center rounded-lg border border-slate-200 hover:bg-slate-100 <?= $page>=$totalPages?'opacity-40 pointer-events-none':'' ?>">
    <i data-lucide="chevron-right" class="w-4 h-4"></i>
  </a>
</nav>
