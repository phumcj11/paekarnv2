<?php
/** @var array $rows @var array $stats @var array $statuses @var int $page @var int $totalPages @var int $total */
$labels = [
    'new'               => 'ใหม่',
    'contacted'         => 'ติดต่อแล้ว',
    'coupon_purchased'  => 'ซื้อคูปองแล้ว',
    'sent_to_owner'     => 'ส่งต่อเจ้าของแพ',
    'confirmed'         => 'ยืนยันเข้าพัก',
    'stayed'            => 'เข้าพักแล้ว',
    'lost'              => 'ปิดดีล',
    'qualified'       => 'มีโอกาส (เก่า)',
    'booked'          => 'จองแล้ว (เก่า)',
];
$colors = [
    'new'               => 'amber',
    'contacted'         => 'blue',
    'coupon_purchased'  => 'violet',
    'sent_to_owner'     => 'cyan',
    'confirmed'         => 'emerald',
    'stayed'            => 'teal',
    'lost'              => 'rose',
    'qualified'         => 'slate',
    'booked'            => 'slate',
];
?>
<div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2 mb-5">
  <?php foreach ($statuses as $st): ?>
  <div class="bg-white border border-slate-200 rounded-xl p-3">
    <div class="text-lg font-extrabold"><?= (int)($stats[$st] ?? 0) ?></div>
    <div class="text-[11px] text-slate-500 leading-tight"><?= e($labels[$st] ?? $st) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<p class="text-xs text-slate-500 mb-3">
  <strong>Lead vs การจอง:</strong> Lead = ผู้สนใจติดต่อจากเว็บ/ช่องทางตลาด · การจอง = ระบบจองที่มีเรคคอร์ดใน <code class="bg-slate-100 px-1 rounded">bookings</code> เท่านั้น
</p>

<div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
    <div>
      <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="sparkles" class="w-5 h-5 text-accent-600"></i> CRM / Leads</h2>
      <p class="text-sm text-slate-500">พบ <?= number_format($total) ?> รายการ</p>
    </div>
    <form method="get" class="flex flex-wrap gap-2">
      <input type="text" name="q" placeholder="ชื่อ / เบอร์ / ข้อความ..." value="<?= e($_GET['q'] ?? '') ?>" class="px-3 py-2 rounded-lg border border-slate-300 text-sm min-w-[180px]">
      <select name="status" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
        <option value="">ทุกสถานะ</option>
        <?php foreach ($statuses as $st): ?>
          <option value="<?= e($st) ?>" <?= ($_GET['status'] ?? '') === $st ? 'selected' : '' ?>><?= e($labels[$st] ?? $st) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">กรอง</button>
    </form>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-600">
        <tr>
          <th class="text-left px-5 py-3">ผู้ติดต่อ</th>
          <th class="text-left px-5 py-3">ที่พักสนใจ</th>
          <th class="text-left px-5 py-3">วันที่</th>
          <th class="text-left px-5 py-3">แหล่งที่มา</th>
          <th class="text-left px-5 py-3">สถานะ</th>
          <th class="text-left px-5 py-3">เข้ามา</th>
          <th class="text-right px-5 py-3">จัดการ</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php foreach ($rows as $l):
          $c = $colors[$l['status']] ?? 'slate'; ?>
        <tr class="hover:bg-slate-50 align-top">
          <td class="px-5 py-3">
            <?= e($l['name']) ?>
            <div class="text-xs text-slate-500"><?= e($l['phone'] ?? '-') ?></div>
            <?php if (!empty($l['note'])): ?>
              <div class="text-[11px] text-slate-600 mt-1 italic"><?= e(mb_substr((string)$l['note'], 0, 80)) ?><?= mb_strlen((string)$l['note']) > 80 ? '…' : '' ?></div>
            <?php endif; ?>
          </td>
          <td class="px-5 py-3"><?= e($l['property_name'] ?? '-') ?></td>
          <td class="px-5 py-3 text-xs whitespace-nowrap"><?= format_date_th($l['check_in']) ?> → <?= format_date_th($l['check_out']) ?></td>
          <td class="px-5 py-3 text-xs"><?= e($l['source'] ?? '-') ?></td>
          <td class="px-5 py-3"><span class="text-xs font-semibold bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2 py-1 rounded-full"><?= e($labels[$l['status']] ?? $l['status']) ?></span></td>
          <td class="px-5 py-3 text-xs text-slate-500 whitespace-nowrap"><?= format_date_th($l['created_at']) ?></td>
          <td class="px-5 py-3 text-right">
            <form method="post" action="<?= url('/admin/leads/' . (int)$l['id'] . '/status') ?>" class="inline-flex flex-col gap-1 items-end"><?= csrf() ?>
              <select name="status" class="text-xs px-2 py-1 rounded border border-slate-300 max-w-[140px]">
                <?php if (!in_array($l['status'], $statuses, true)): ?>
                  <option value="<?= e($l['status']) ?>" selected><?= e($l['status']) ?> (รัน migration)</option>
                <?php endif; ?>
                <?php foreach ($statuses as $st): ?>
                  <option value="<?= e($st) ?>" <?= $l['status'] === $st ? 'selected' : '' ?>><?= e($labels[$st] ?? $st) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="text-xs px-2 py-1 bg-primary-600 text-white rounded">บันทึกสถานะ</button>
            </form>
            <details class="mt-2 text-left">
              <summary class="text-xs text-accent-700 cursor-pointer select-none">โน้ต</summary>
              <form method="post" action="<?= url('/admin/leads/' . (int)$l['id'] . '/note') ?>" class="mt-2 space-y-1"><?= csrf() ?>
                <textarea name="note" rows="2" class="w-full text-xs border border-slate-300 rounded px-2 py-1" placeholder="follow-up, LINE, โทร..."><?= e((string)($l['note'] ?? '')) ?></textarea>
                <button type="submit" class="text-xs px-2 py-1 bg-slate-700 text-white rounded">บันทึกโน้ต</button>
              </form>
            </details>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" class="text-center py-10 text-slate-500">ยังไม่มี Lead</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="p-4">
    <?php $q = $_GET;
unset($q['page']);
\App\Core\View::partial('partials/pagination', ['page' => $page, 'totalPages' => $totalPages, 'baseUrl' => url('/admin/leads'), 'query' => $q]); ?>
  </div>
</div>
