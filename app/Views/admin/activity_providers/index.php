<?php
/** @var array<int,array<string,mixed>> $rows */
/** @var array<string,string> $types */
use App\Models\ActivityProvider;

$filter = trim((string)($_GET['partner_status'] ?? ''));
$filtered = $rows;
if ($filter !== '' && in_array($filter, ['pending', 'active', 'paused', 'terminated'], true)) {
    $filtered = array_values(array_filter($rows, fn ($r) => ($r['partner_status'] ?? $r['partner_status_display'] ?? '') === $filter));
}
$pendingCount = ActivityProvider::pendingCount();
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
  <div>
    <h1 class="text-xl font-bold text-primary-800">ผู้ให้บริการกิจกรรม</h1>
    <p class="text-sm text-slate-600 mt-0.5">บริษัททัวร์ รถเช่า รถนำเที่ยว เรือ และบริการท่องเที่ยว</p>
  </div>
  <a href="<?= url('/admin/activity-providers/create') ?>" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-accent-500 hover:bg-accent-600 text-white rounded-xl font-semibold text-sm">
    <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มผู้ให้บริการ
  </a>
</div>

<div class="flex flex-wrap gap-2 mb-4">
  <a href="<?= url('/admin/activity-providers') ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium <?= $filter === '' ? 'bg-primary-700 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>">ทั้งหมด</a>
  <a href="<?= url('/admin/activity-providers?partner_status=pending') ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium <?= $filter === 'pending' ? 'bg-amber-600 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>">
    รออนุมัติ<?= $pendingCount > 0 ? ' (' . $pendingCount . ')' : '' ?>
  </a>
  <a href="<?= url('/admin/activity-providers?partner_status=active') ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium <?= $filter === 'active' ? 'bg-emerald-600 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>">ใช้งานได้</a>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-200">
        <tr class="text-left">
          <th class="px-4 py-3 font-semibold">ชื่อ</th>
          <th class="px-4 py-3 font-semibold">ประเภท</th>
          <th class="px-4 py-3 font-semibold">พื้นที่</th>
          <th class="px-4 py-3 font-semibold">ติดต่อ</th>
          <th class="px-4 py-3 font-semibold">Partner</th>
          <th class="px-4 py-3 font-semibold">คอมมิชชัน</th>
          <th class="px-4 py-3 w-52"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php if ($filtered === []): ?>
        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">ยังไม่มีผู้ให้บริการ</td></tr>
      <?php endif; ?>
      <?php foreach ($filtered as $r):
        $ps = (string)($r['partner_status'] ?? $r['partner_status_display'] ?? 'active');
        $psCls = match ($ps) {
            'pending' => 'text-amber-600',
            'active' => 'text-emerald-600',
            'paused' => 'text-slate-500',
            'terminated' => 'text-rose-600',
            default => 'text-slate-600',
        };
      ?>
        <tr class="hover:bg-slate-50/80">
          <td class="px-4 py-3 font-semibold">
            <?= e($r['name']) ?>
            <?php if (!empty($r['user_id'])): ?><div class="text-[10px] text-sky-600 font-normal">สมัครออนไลน์</div><?php endif; ?>
          </td>
          <td class="px-4 py-3"><?= e($types[$r['type']] ?? $r['type']) ?></td>
          <td class="px-4 py-3 text-slate-600"><?= e(trim(($r['district'] ?? '') . ' ' . ($r['zone'] ?? '')) ?: '—') ?></td>
          <td class="px-4 py-3 text-slate-600">
            <?= e($r['phone'] ?: '—') ?>
            <?php if (!empty($r['line_id'])): ?><div class="text-xs">LINE: <?= e($r['line_id']) ?></div><?php endif; ?>
          </td>
          <td class="px-4 py-3"><span class="<?= $psCls ?> font-semibold"><?= e(ActivityProvider::partnerStatusLabel($ps)) ?></span></td>
          <td class="px-4 py-3"><?= $r['commission_type'] === 'fixed' ? format_money($r['commission_value']) : number_format((float)$r['commission_value'], 2) . '%' ?></td>
          <td class="px-4 py-3">
            <div class="flex flex-col gap-1.5">
              <a href="<?= url('/admin/activity-providers/' . $r['id'] . '/edit') ?>" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs text-center font-medium">แก้ไข</a>
              <?php if ($ps === 'pending'): ?>
              <form method="post" action="<?= url('/admin/activity-providers/' . $r['id'] . '/partner-status') ?>"><?= csrf() ?>
                <input type="hidden" name="partner_status" value="active">
                <button class="w-full px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg text-xs font-medium">อนุมัติ</button>
              </form>
              <?php endif; ?>
              <form method="post" action="<?= url('/admin/activity-providers/' . $r['id'] . '/delete') ?>" onsubmit="return confirm('ลบผู้ให้บริการนี้ถาวร?<?= !empty($r['user_id']) ? ' บัญชี login ที่สมัครออนไลน์จะถูกลบด้วย' : '' ?>')"><?= csrf() ?>
                <button class="w-full px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-medium inline-flex items-center justify-center gap-1">
                  <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> ลบ
                </button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

