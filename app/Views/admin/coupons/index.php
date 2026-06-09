<?php /** @var array $rows @var array $stats @var int $page @var int $totalPages @var int $total */ ?>
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3 mb-5">
  <?php
  $cards = [
      ['ticket', 'รวมทั้งหมด', $stats['total'], 'bg-slate-50 text-slate-700'],
      ['circle-dot', 'พร้อมใช้', $stats['unused'], 'bg-emerald-50 text-emerald-700'],
      ['bookmark', 'จองไว้', $stats['reserved'], 'bg-amber-50 text-amber-700'],
      ['check-square', 'ใช้แล้ว', $stats['used'], 'bg-blue-50 text-blue-700'],
      ['clock', 'หมดอายุ', $stats['expired'], 'bg-rose-50 text-rose-700'],
      ['shield-off', 'เพิกถอน', $stats['revoked'], 'bg-rose-50 text-rose-800'],
      ['ban', 'ยกเลิก', $stats['cancelled'], 'bg-slate-100 text-slate-700'],
      ['banknote', 'รายได้รวม', '฿' . number_format($stats['revenue']), 'bg-teal-50 text-teal-700'],
  ];
foreach ($cards as $c): ?>
  <div class="bg-white border border-slate-200 rounded-xl p-4">
    <div class="w-9 h-9 rounded-lg <?= $c[3] ?> grid place-items-center"><i data-lucide="<?= $c[0] ?>" class="w-4 h-4"></i></div>
    <div class="mt-2 text-xl font-extrabold"><?= $c[2] ?></div>
    <div class="text-xs text-slate-500"><?= e($c[1]) ?></div>
  </div>
<?php endforeach; ?>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
    <div>
      <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="ticket" class="w-5 h-5 text-rose-600"></i> คูปอง</h2>
      <a href="<?= url('/admin/coupons/orders') ?>" class="text-xs text-primary-700 hover:underline">→ ดูคำสั่งซื้อ</a>
    </div>
    <div class="flex flex-wrap gap-2 items-center">
      <a href="<?= url('/admin/coupons/create') ?>" class="px-4 py-2 bg-accent-500 text-white rounded-lg text-sm inline-flex items-center gap-1"><i data-lucide="plus" class="w-4 h-4"></i> ออกคูปอง</a>
      <a href="<?= url('/admin/coupons/export.csv') ?>" class="px-4 py-2 border border-slate-300 rounded-lg text-sm hover:bg-slate-50 inline-flex items-center gap-1"><i data-lucide="download" class="w-4 h-4"></i> CSV</a>
    <form method="get" class="flex gap-2 flex-wrap">
      <input type="text" name="q" placeholder="ค้นหา code/เบอร์..." value="<?= e($_GET['q'] ?? '') ?>" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
      <select name="status" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
        <option value="">ทุกสถานะ</option>
        <?php foreach (['unused', 'reserved', 'used', 'expired', 'revoked', 'cancelled'] as $st): ?>
          <option value="<?= $st ?>" <?= ($_GET['status'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
        <?php endforeach; ?>
      </select>
      <button class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">กรอง</button>
    </form>
    </div>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-600">
        <tr>
          <th class="text-left px-5 py-3">CODE</th>
          <th class="text-left px-5 py-3">เบอร์</th>
          <th class="text-left px-5 py-3">มูลค่า</th>
          <th class="text-left px-5 py-3">ออกเมื่อ</th>
          <th class="text-left px-5 py-3">หมดอายุ</th>
          <th class="text-left px-5 py-3">สถานะ</th>
          <th class="text-right px-5 py-3">จัดการ</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php $colors = ['unused' => 'emerald', 'reserved' => 'amber', 'used' => 'blue', 'expired' => 'rose', 'revoked' => 'rose', 'cancelled' => 'slate'];
foreach ($rows as $c):
    $cl = $colors[$c['status']] ?? 'slate'; ?>
        <tr class="hover:bg-slate-50">
          <td class="px-5 py-3 font-mono font-semibold text-primary-700"><?= e($c['code']) ?></td>
          <td class="px-5 py-3"><?= e($c['phone']) ?></td>
          <td class="px-5 py-3 font-semibold">฿<?= number_format($c['face_value']) ?> <span class="text-xs text-slate-400">/ ฿<?= number_format($c['sale_price']) ?></span></td>
          <td class="px-5 py-3 text-xs"><?= format_date_th($c['issued_at']) ?></td>
          <td class="px-5 py-3 text-xs"><?= format_date_th($c['expires_at']) ?></td>
          <td class="px-5 py-3"><span class="text-xs font-semibold bg-<?= $cl ?>-100 text-<?= $cl ?>-700 px-2 py-1 rounded-full"><?= e($c['status']) ?></span></td>
          <td class="px-5 py-3 text-right">
            <div class="inline-flex flex-wrap gap-1 justify-end">
              <a href="<?= url('/admin/coupons/' . (int)$c['id']) ?>" class="text-xs px-2 py-1 bg-primary-600 text-white rounded">ดู</a>
              <a href="<?= url('/admin/coupons/' . (int)$c['id'] . '/edit') ?>" class="text-xs px-2 py-1 border border-slate-300 rounded">แก้</a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="p-4">
    <?php $q = $_GET;
unset($q['page']);
\App\Core\View::partial('partials/pagination', ['page' => $page, 'totalPages' => $totalPages, 'baseUrl' => url('/admin/coupons'), 'query' => $q]); ?>
  </div>
</div>
