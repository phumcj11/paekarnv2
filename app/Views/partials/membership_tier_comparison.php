<?php
/** @var bool $compact */
use App\Services\OwnerTier;

$compact = $compact ?? false;
$rows    = OwnerTier::comparisonRows();
$tierCols = [
    'none'     => ['label' => 'ฟรี', 'head' => 'bg-slate-100 text-slate-700'],
    'standard' => ['label' => 'Standard', 'head' => 'bg-sky-100 text-sky-800'],
    'vip'      => ['label' => 'VIP', 'head' => 'bg-amber-100 text-amber-900'],
];
?>
<div class="<?= $compact ? '' : 'bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden' ?>">
  <?php if (!$compact): ?>
  <div class="p-5 border-b border-slate-100">
    <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="table-2" class="w-5 h-5 text-primary-600"></i> เปรียบเทียบสิทธิ์แต่ละระดับ</h2>
    <p class="text-sm text-slate-600 mt-1">ฟรี: จัดการที่พักและแสดงบนเว็บ — อัปเกรดเพื่อปฏิทิน, LINE Hub, การตลาด, Analytics และ CRM</p>
  </div>
  <?php endif; ?>
  <div class="overflow-x-auto <?= $compact ? '' : 'p-5 pt-0' ?>">
    <table class="w-full text-sm min-w-[520px]">
      <thead>
        <tr class="text-xs uppercase">
          <th class="text-left px-3 py-2.5 text-slate-600 font-semibold">ฟีเจอร์</th>
          <?php foreach ($tierCols as $col): ?>
            <th class="text-center px-3 py-2.5 font-semibold rounded-t-lg <?= $col['head'] ?>"><?= e($col['label']) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($rows as $row): ?>
        <tr class="hover:bg-slate-50/80">
          <td class="px-3 py-2.5 text-slate-700"><?= e($row['label']) ?></td>
          <?php foreach (array_keys($tierCols) as $tierKey):
            $cell = OwnerTier::comparisonCell($row, $tierKey);
          ?>
          <td class="px-3 py-2.5 text-center">
            <?php if ($cell === true): ?>
              <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-700" title="มีสิทธิ์"><i data-lucide="check" class="w-3.5 h-3.5"></i></span>
            <?php elseif ($cell === false): ?>
              <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-400" title="ไม่มีสิทธิ์"><i data-lucide="minus" class="w-3.5 h-3.5"></i></span>
            <?php else: ?>
              <span class="text-xs font-bold text-primary-700"><?= e((string)$cell) ?></span>
            <?php endif; ?>
          </td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
